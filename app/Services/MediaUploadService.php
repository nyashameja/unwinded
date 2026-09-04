<?php

declare(strict_types=1);

namespace Unwinded\Services;

use Unwinded\Core\Database;
use Unwinded\Support\Ref;

/**
 * Handles media file uploads for the admin library.
 *
 * Originals are stored outside the web root (storage/private/media/YYYY/MM/).
 * Web-accessible copies are resized and stored as WebP under public/media/YYYY/MM/.
 * Image resizing uses PHP's built-in GD extension.
 */
class MediaUploadService
{
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
    private const ALLOWED_EXT  = ['jpg', 'jpeg', 'png', 'webp'];
    private const MAX_BYTES    = 20 * 1024 * 1024; // 20 MB

    private const SIZES = [
        'thumb'  => ['w' => 400,  'h' => 400,  'crop' => true],
        'medium' => ['w' => 1000, 'h' => 1000, 'crop' => false],
        'large'  => ['w' => 1800, 'h' => 1800, 'crop' => false],
    ];

    public function __construct(
        private Database $db,
        private string   $privateBase, // absolute path to storage/private/media
        private string   $publicBase,  // absolute path to public/media
        private string   $publicUrl,   // URL prefix e.g. https://example.com/media
    ) {}

    /**
     * Process an uploaded file from $_FILES.
     *
     * @param array       $file       Single entry from $_FILES (with keys: tmp_name, name, size, error, type)
     * @param bool        $isPrivate  If true, only the original is kept (no web-accessible variant)
     * @param int|null    $uploadedBy User ID
     * @return array                  The inserted media row (with id, public_ref, urls, etc.)
     * @throws \RuntimeException on validation or storage failure
     */
    public function store(array $file, bool $isPrivate = false, ?int $uploadedBy = null): array
    {
        $this->validateUpload($file);

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $year     = (int) date('Y');
        $month    = (int) date('n');
        $ref      = Ref::generate('MED');
        $basename = $ref . '.' . $ext;

        // Store original in private storage
        $privateDir = $this->privateBase . '/' . $year . '/' . $month;
        $this->ensureDir($privateDir);
        $privatePath = $privateDir . '/' . $basename;

        if (!move_uploaded_file($file['tmp_name'], $privatePath)) {
            throw new \RuntimeException('Failed to move uploaded file to storage.');
        }

        [$width, $height] = $this->imageDimensions($privatePath, $ext);

        $urls       = [];
        $storagePath = 'storage/private/media/' . $year . '/' . $month . '/' . $basename;

        // For public images, create resized WebP variants
        if (!$isPrivate && $this->isImage($ext)) {
            $publicDir = $this->publicBase . '/' . $year . '/' . $month;
            $this->ensureDir($publicDir);

            foreach (self::SIZES as $size => $dims) {
                $outFile = $publicDir . '/' . $ref . '_' . $size . '.webp';
                $this->resizeToWebp($privatePath, $outFile, $ext, $dims['w'], $dims['h'], $dims['crop']);
                $urls[$size] = $this->publicUrl . '/' . $year . '/' . $month . '/' . $ref . '_' . $size . '.webp';
            }
        }

        $id = $this->db->insert('media', [
            'public_ref'    => $ref,
            'filename'      => $basename,
            'original_name' => substr($file['name'], 0, 500),
            'mime_type'     => $file['type'],
            'extension'     => $ext,
            'size_bytes'    => $file['size'],
            'width'         => $width,
            'height'        => $height,
            'storage_path'  => $storagePath,
            'is_private'    => $isPrivate ? 1 : 0,
            'year'          => $year,
            'month'         => $month,
            'uploaded_by'   => $uploadedBy,
        ]);

        return array_merge(
            $this->db->fetchOne("SELECT * FROM media WHERE id = ?", [$id]),
            ['urls' => $urls],
        );
    }

    /** Soft-delete a media record and remove public files. */
    public function delete(int $id): void
    {
        $row = $this->db->fetchOne("SELECT * FROM media WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$row) {
            return;
        }

        // Remove public variants
        $dir = $this->publicBase . '/' . $row['year'] . '/' . $row['month'];
        foreach (array_keys(self::SIZES) as $size) {
            $f = $dir . '/' . $row['public_ref'] . '_' . $size . '.webp';
            if (file_exists($f)) {
                @unlink($f);
            }
        }

        $this->db->execute(
            "UPDATE media SET deleted_at = NOW() WHERE id = ?",
            [$id]
        );
    }

    /** Return public URLs for a media row. */
    public function urlsFor(array $row): array
    {
        if ($row['is_private']) {
            return [];
        }
        $urls = [];
        foreach (array_keys(self::SIZES) as $size) {
            $urls[$size] = $this->publicUrl . '/' . $row['year'] . '/' . $row['month']
                . '/' . $row['public_ref'] . '_' . $size . '.webp';
        }
        return $urls;
    }

    // ── Private helpers ─────────────────────────────────────────────────────

    private function validateUpload(array $file): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload error code: ' . $file['error']);
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new \RuntimeException('File exceeds the 20 MB limit.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new \RuntimeException("File type {$mime} is not allowed. Accepted: JPEG, PNG, WebP.");
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            throw new \RuntimeException("Extension .{$ext} is not allowed.");
        }
    }

    private function isImage(string $ext): bool
    {
        return in_array($ext, self::ALLOWED_EXT, true) && function_exists('imagecreatefromjpeg');
    }

    private function imageDimensions(string $path, string $ext): array
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return [null, null];
        }
        $info = @getimagesize($path);
        return $info ? [$info[0], $info[1]] : [null, null];
    }

    private function resizeToWebp(
        string $src,
        string $dest,
        string $ext,
        int    $maxW,
        int    $maxH,
        bool   $crop
    ): void {
        if (!function_exists('imagecreatefromjpeg')) {
            return; // GD not available; skip resizing
        }

        $original = match ($ext) {
            'jpg', 'jpeg' => imagecreatefromjpeg($src),
            'png'         => imagecreatefrompng($src),
            'webp'        => imagecreatefromwebp($src),
            default       => false,
        };
        if ($original === false) {
            return;
        }

        $srcW = imagesx($original);
        $srcH = imagesy($original);

        [$dstW, $dstH, $srcX, $srcY, $cropW, $cropH] = $crop
            ? $this->cropCoords($srcW, $srcH, $maxW, $maxH)
            : $this->fitCoords($srcW, $srcH, $maxW, $maxH);

        $canvas = imagecreatetruecolor($dstW, $dstH);
        // Preserve transparency for PNG/WebP
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $dstW - 1, $dstH - 1, $transparent);
        imagealphablending($canvas, true);

        imagecopyresampled($canvas, $original, 0, 0, $srcX, $srcY, $dstW, $dstH, $cropW, $cropH);
        imagewebp($canvas, $dest, 82);

        imagedestroy($original);
        imagedestroy($canvas);
    }

    private function fitCoords(int $srcW, int $srcH, int $maxW, int $maxH): array
    {
        if ($srcW <= $maxW && $srcH <= $maxH) {
            return [$srcW, $srcH, 0, 0, $srcW, $srcH];
        }
        $ratio = min($maxW / $srcW, $maxH / $srcH);
        return [(int) round($srcW * $ratio), (int) round($srcH * $ratio), 0, 0, $srcW, $srcH];
    }

    private function cropCoords(int $srcW, int $srcH, int $maxW, int $maxH): array
    {
        $ratio  = max($maxW / $srcW, $maxH / $srcH);
        $cropW  = (int) round($maxW / $ratio);
        $cropH  = (int) round($maxH / $ratio);
        $srcX   = (int) round(($srcW - $cropW) / 2);
        $srcY   = (int) round(($srcH - $cropH) / 2);
        return [$maxW, $maxH, $srcX, $srcY, $cropW, $cropH];
    }

    private function ensureDir(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException("Could not create directory: {$path}");
        }
    }
}
