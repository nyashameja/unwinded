<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;
use Unwinded\Services\MailService;
use Unwinded\Services\MediaUploadService;
use Unwinded\Support\Ref;
use Unwinded\Support\Token;

class GalleryController
{
    private const STATUSES = ['draft', 'scheduled', 'published', 'private', 'archived'];

    public function __construct(
        private Database           $db,
        private Request            $request,
        private View               $view,
        private ActivityLogger     $activityLogger,
        private MediaUploadService $mediaUpload,
        private MailService        $mail,
    ) {}

    public function index(): Response
    {
        $status  = $this->request->str('status');
        if (!in_array($status, self::STATUSES, true)) {
            $status = '';
        }

        $where  = "ga.deleted_at IS NULL";
        $params = [];
        if ($status) {
            $where  .= " AND ga.status = ?";
            $params[] = $status;
        }

        $albums = $this->db->fetchAll(
            "SELECT ga.*,
                    m.public_url AS cover_url,
                    (SELECT COUNT(*) FROM gallery_images gi WHERE gi.album_id = ga.id) AS image_count,
                    (SELECT COUNT(*) FROM gallery_access_tokens gat WHERE gat.album_id = ga.id AND gat.is_active = 1) AS token_count
               FROM gallery_albums ga
               LEFT JOIN media m ON m.id = ga.cover_image_id AND m.deleted_at IS NULL
              WHERE {$where}
             ORDER BY ga.sort_order ASC, ga.created_at DESC
             LIMIT 300",
            $params
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/gallery/index', [
                'pageTitle' => 'Gallery Albums',
                'albums'    => $albums,
                'status'    => $status,
                'statuses'  => self::STATUSES,
            ])
        );
    }

    public function create(): Response
    {
        $events   = $this->db->fetchAll("SELECT id, title, event_date FROM public_events WHERE deleted_at IS NULL ORDER BY event_date DESC LIMIT 100");
        $bookings = $this->db->fetchAll(
            "SELECT pb.id, pb.public_ref, pb.event_date, c.name AS customer_name
               FROM private_bookings pb
               JOIN customers c ON c.id = pb.customer_id
              WHERE pb.deleted_at IS NULL
             ORDER BY pb.event_date DESC LIMIT 100"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/gallery/create', [
                'pageTitle' => 'New Gallery Album',
                'events'    => $events,
                'bookings'  => $bookings,
                'old'       => [],
                'errors'    => [],
            ])
        );
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validate();
        if ($errors) {
            $events   = $this->db->fetchAll("SELECT id, title, event_date FROM public_events WHERE deleted_at IS NULL ORDER BY event_date DESC LIMIT 100");
            $bookings = $this->db->fetchAll(
                "SELECT pb.id, pb.public_ref, pb.event_date, c.name AS customer_name
                   FROM private_bookings pb
                   JOIN customers c ON c.id = pb.customer_id
                  WHERE pb.deleted_at IS NULL ORDER BY pb.event_date DESC LIMIT 100"
            );
            return Response::make()->html(
                $this->view->renderWithLayout('admin', 'admin/gallery/create', [
                    'pageTitle' => 'New Gallery Album',
                    'events'    => $events,
                    'bookings'  => $bookings,
                    'old'       => $this->request->all(),
                    'errors'    => $errors,
                ])
            );
        }

        $data['public_ref'] = Ref::generate('GAL');
        $data['created_by'] = $_SESSION['user']['id'] ?? null;
        $this->ensureUniqueSlug($data['slug']);

        $this->db->execute(
            "INSERT INTO gallery_albums
                (public_ref,slug,title,description,segment,event_date,event_type,venue,location,
                 linked_event_id,linked_booking_id,is_featured,is_downloadable,status,scheduled_at,
                 meta_title,meta_description,sort_order,created_by,created_at,updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
            [
                $data['public_ref'], $data['slug'], $data['title'], $data['description'],
                $data['segment'], $data['event_date'] ?: null, $data['event_type'],
                $data['venue'], $data['location'],
                $data['linked_event_id'] ?: null, $data['linked_booking_id'] ?: null,
                $data['is_featured'], $data['is_downloadable'], $data['status'], $data['scheduled_at'] ?: null,
                $data['meta_title'], $data['meta_description'], (int) $data['sort_order'],
                $data['created_by'],
            ]
        );
        $id = (int) $this->db->lastInsertId();

        $this->activityLogger->log('gallery.created', 'gallery_album', (string) $id);
        flash('success', 'Album created.');
        return Response::make()->redirect(url('/admin/gallery/' . $id . '/edit'));
    }

    public function edit(string $id): Response
    {
        $album = $this->findAlbum((int) $id);
        if (!$album) {
            flash('error', 'Album not found.');
            return Response::make()->redirect(url('/admin/gallery'));
        }

        $images = $this->db->fetchAll(
            "SELECT gi.*, m.public_url, m.file_name
               FROM gallery_images gi
               JOIN media m ON m.id = gi.media_id AND m.deleted_at IS NULL
              WHERE gi.album_id = ?
             ORDER BY gi.sort_order",
            [(int) $id]
        );

        $tokens = $this->db->fetchAll(
            "SELECT * FROM gallery_access_tokens WHERE album_id = ? ORDER BY created_at DESC",
            [(int) $id]
        );

        $events   = $this->db->fetchAll("SELECT id, title, event_date FROM public_events WHERE deleted_at IS NULL ORDER BY event_date DESC LIMIT 100");
        $bookings = $this->db->fetchAll(
            "SELECT pb.id, pb.public_ref, pb.event_date, c.name AS customer_name
               FROM private_bookings pb
               JOIN customers c ON c.id = pb.customer_id
              WHERE pb.deleted_at IS NULL ORDER BY pb.event_date DESC LIMIT 100"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/gallery/edit', [
                'pageTitle' => 'Edit Album — ' . $album['title'],
                'album'     => $album,
                'images'    => $images,
                'tokens'    => $tokens,
                'events'    => $events,
                'bookings'  => $bookings,
                'old'       => [],
                'errors'    => [],
            ])
        );
    }

    public function update(string $id): Response
    {
        $album = $this->findAlbum((int) $id);
        if (!$album) {
            flash('error', 'Album not found.');
            return Response::make()->redirect(url('/admin/gallery'));
        }

        [$data, $errors] = $this->validate();
        if ($errors) {
            $images   = $this->db->fetchAll("SELECT gi.*, m.public_url FROM gallery_images gi JOIN media m ON m.id = gi.media_id WHERE gi.album_id = ? ORDER BY gi.sort_order", [(int) $id]);
            $tokens   = $this->db->fetchAll("SELECT * FROM gallery_access_tokens WHERE album_id = ? ORDER BY created_at DESC", [(int) $id]);
            $events   = $this->db->fetchAll("SELECT id, title, event_date FROM public_events WHERE deleted_at IS NULL ORDER BY event_date DESC LIMIT 100");
            $bookings = $this->db->fetchAll(
                "SELECT pb.id, pb.public_ref, pb.event_date, c.name AS customer_name
                   FROM private_bookings pb JOIN customers c ON c.id = pb.customer_id
                  WHERE pb.deleted_at IS NULL ORDER BY pb.event_date DESC LIMIT 100"
            );
            return Response::make()->html(
                $this->view->renderWithLayout('admin', 'admin/gallery/edit', [
                    'pageTitle' => 'Edit Album — ' . $album['title'],
                    'album'     => $album,
                    'images'    => $images,
                    'tokens'    => $tokens,
                    'events'    => $events,
                    'bookings'  => $bookings,
                    'old'       => $this->request->all(),
                    'errors'    => $errors,
                ])
            );
        }

        $this->ensureUniqueSlug($data['slug'], (int) $id);

        // Cover image: allow clearing or setting via hidden media_id field
        $coverImageId = (int) $this->request->str('cover_image_id') ?: null;

        $this->db->execute(
            "UPDATE gallery_albums SET
                slug=?,title=?,description=?,segment=?,event_date=?,event_type=?,venue=?,location=?,
                linked_event_id=?,linked_booking_id=?,is_featured=?,is_downloadable=?,
                status=?,scheduled_at=?,meta_title=?,meta_description=?,sort_order=?,
                cover_image_id=?,updated_at=NOW()
             WHERE id=?",
            [
                $data['slug'], $data['title'], $data['description'],
                $data['segment'], $data['event_date'] ?: null, $data['event_type'],
                $data['venue'], $data['location'],
                $data['linked_event_id'] ?: null, $data['linked_booking_id'] ?: null,
                $data['is_featured'], $data['is_downloadable'], $data['status'], $data['scheduled_at'] ?: null,
                $data['meta_title'], $data['meta_description'], (int) $data['sort_order'],
                $coverImageId, (int) $id,
            ]
        );

        $this->activityLogger->log('gallery.updated', 'gallery_album', $id);
        flash('success', 'Album saved.');
        return Response::make()->redirect(url('/admin/gallery/' . $id . '/edit'));
    }

    public function publish(string $id): Response
    {
        $album = $this->findAlbum((int) $id);
        if (!$album) {
            flash('error', 'Album not found.');
            return Response::make()->redirect(url('/admin/gallery'));
        }

        $newStatus = $this->request->str('status');
        if (!in_array($newStatus, self::STATUSES, true)) {
            flash('error', 'Invalid status.');
            return Response::make()->redirect(url('/admin/gallery/' . $id . '/edit'));
        }

        $publishedAt = ($newStatus === 'published' && $album['status'] !== 'published')
            ? 'NOW()'
            : ($album['published_at'] ? "'{$album['published_at']}'" : 'NULL');

        $this->db->execute(
            "UPDATE gallery_albums SET status=?, published_at=IF(?='published' AND status!='published',NOW(),published_at), updated_at=NOW() WHERE id=?",
            [$newStatus, $newStatus, (int) $id]
        );

        $this->activityLogger->log('gallery.status_changed', 'gallery_album', $id, ['to' => $newStatus]);
        flash('success', 'Album status set to ' . $newStatus . '.');
        return Response::make()->redirect(url('/admin/gallery/' . $id . '/edit'));
    }

    public function upload(string $id): Response
    {
        $album = $this->findAlbum((int) $id);
        if (!$album) {
            return Response::make()->status(404)->json(['error' => 'Album not found']);
        }

        $files = $_FILES['images'] ?? null;
        if (!$files) {
            flash('error', 'No files uploaded.');
            return Response::make()->redirect(url('/admin/gallery/' . $id . '/edit'));
        }

        // Support multi-file upload
        $uploaded = 0;
        $errors   = [];
        $fileList = is_array($files['name']) ? $files : $this->wrapSingleFile($files);

        $maxSort = (int) ($this->db->fetchScalar(
            "SELECT COALESCE(MAX(sort_order), 0) FROM gallery_images WHERE album_id=?",
            [(int) $id]
        ) ?? 0);

        foreach ($fileList['name'] as $i => $name) {
            $singleFile = [
                'name'     => $name,
                'type'     => $fileList['type'][$i],
                'tmp_name' => $fileList['tmp_name'][$i],
                'error'    => $fileList['error'][$i],
                'size'     => $fileList['size'][$i],
            ];

            if ($singleFile['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "File {$name}: upload error code " . $singleFile['error'];
                continue;
            }

            try {
                $media = $this->mediaUpload->store($singleFile, uploadedBy: $_SESSION['user']['id'] ?? null);
                $this->db->execute(
                    "INSERT INTO gallery_images (album_id, media_id, sort_order, created_at)
                     VALUES (?, ?, ?, NOW())",
                    [(int) $id, $media['id'], ++$maxSort]
                );
                $uploaded++;
            } catch (\Throwable $e) {
                $errors[] = "File {$name}: " . $e->getMessage();
            }
        }

        if ($errors) {
            flash('error', implode('; ', $errors));
        }
        if ($uploaded > 0) {
            flash('success', "{$uploaded} image(s) uploaded.");
        }

        $this->activityLogger->log('gallery.images_uploaded', 'gallery_album', $id, ['count' => $uploaded]);
        return Response::make()->redirect(url('/admin/gallery/' . $id . '/edit'));
    }

    public function reorder(string $id): Response
    {
        $album = $this->findAlbum((int) $id);
        if (!$album) {
            return Response::make()->status(404)->json(['error' => 'Album not found']);
        }

        $order = (array) ($this->request->all()['order'] ?? []);
        foreach ($order as $pos => $imageId) {
            $this->db->execute(
                "UPDATE gallery_images SET sort_order=? WHERE id=? AND album_id=?",
                [(int) $pos + 1, (int) $imageId, (int) $id]
            );
        }

        return Response::make()->json(['ok' => true]);
    }

    public function generateToken(string $id): Response
    {
        $album = $this->findAlbum((int) $id);
        if (!$album) {
            flash('error', 'Album not found.');
            return Response::make()->redirect(url('/admin/gallery'));
        }

        $token      = Token::generate();
        $password   = trim($this->request->str('password'));
        $expiryDays = (int) $this->request->str('expiry_days');
        $expiresAt  = $expiryDays > 0
            ? date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"))
            : null;

        $this->db->execute(
            "INSERT INTO gallery_access_tokens
                (album_id, token_hash, password_hash, expires_at, is_active, created_by, created_at)
             VALUES (?, ?, ?, ?, 1, ?, NOW())",
            [
                (int) $id,
                $token['hash'],
                $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null,
                $expiresAt,
                $_SESSION['user']['id'] ?? null,
            ]
        );

        // Optionally email the link
        $sendTo = trim($this->request->str('send_email'));
        $tokenBase64 = base64_encode(hex2bin($token['raw']));
        $galleryUrl  = url('/g/' . $tokenBase64);

        if ($sendTo && filter_var($sendTo, FILTER_VALIDATE_EMAIL)) {
            $sendName = trim($this->request->str('send_name')) ?: $sendTo;
            $siteName = setting('site.name', 'Unwinded');
            $body = '<p>Dear ' . htmlspecialchars($sendName) . ',</p>'
                . '<p>Your private gallery is ready to view:</p>'
                . '<p><a href="' . htmlspecialchars($galleryUrl) . '" style="display:inline-block;background:#1a1a1a;color:#fff;padding:.75rem 1.5rem;border-radius:6px;text-decoration:none;font-weight:600;">View gallery</a></p>'
                . '<p style="font-size:.875rem;color:#666;">Link: <a href="' . htmlspecialchars($galleryUrl) . '">' . htmlspecialchars($galleryUrl) . '</a></p>'
                . ($password !== '' ? '<p style="font-size:.875rem;color:#666;">Password: <strong>' . htmlspecialchars($password) . '</strong></p>' : '')
                . ($expiresAt ? '<p style="font-size:.875rem;color:#666;">This link expires on ' . date('d M Y', strtotime($expiresAt)) . '.</p>' : '')
                . '<p>Thank you for choosing ' . htmlspecialchars($siteName) . '!</p>';

            try {
                $this->mail->send($sendTo, $sendName, 'Your private gallery — ' . $album['title'], $body);
                flash('success', 'Gallery link sent to ' . $sendTo . '. URL: ' . $galleryUrl);
            } catch (\Throwable $e) {
                flash('warning', 'Token created but email failed: ' . $e->getMessage() . '. URL: ' . $galleryUrl);
            }
        } else {
            flash('success', 'Token created. Gallery URL: ' . $galleryUrl);
        }

        $this->activityLogger->log('gallery.token_created', 'gallery_album', $id);
        return Response::make()->redirect(url('/admin/gallery/' . $id . '/edit'));
    }

    private function validate(): array
    {
        $post   = $this->request->all();
        $errors = [];

        $title = trim((string) ($post['title'] ?? ''));
        if (strlen($title) < 2) {
            $errors['title'] = 'Title is required.';
        }

        $slug = trim((string) ($post['slug'] ?? ''));
        if ($slug === '') {
            $slug = $this->makeSlug($title);
        } else {
            $slug = $this->makeSlug($slug);
        }

        $status = (string) ($post['status'] ?? 'draft');
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'draft';
        }

        $segment  = (string) ($post['segment'] ?? 'other');
        $allowed  = ['corporate','restaurant','bridal','baby_shower','birthday','couples','private','public_event','other'];
        if (!in_array($segment, $allowed, true)) {
            $segment = 'other';
        }

        $eventDate    = trim((string) ($post['event_date'] ?? ''));
        $scheduledAt  = trim((string) ($post['scheduled_at'] ?? ''));

        if ($eventDate && !strtotime($eventDate)) {
            $errors['event_date'] = 'Invalid event date.';
            $eventDate = '';
        }
        if ($scheduledAt && !strtotime($scheduledAt)) {
            $errors['scheduled_at'] = 'Invalid scheduled date.';
            $scheduledAt = '';
        }

        $data = [
            'title'             => substr($title, 0, 500),
            'slug'              => substr($slug, 0, 250),
            'description'       => trim((string) ($post['description'] ?? '')),
            'segment'           => $segment,
            'event_date'        => $eventDate ?: null,
            'event_type'        => substr(trim((string) ($post['event_type'] ?? '')), 0, 100),
            'venue'             => substr(trim((string) ($post['venue'] ?? '')), 0, 300),
            'location'          => substr(trim((string) ($post['location'] ?? '')), 0, 200),
            'linked_event_id'   => (int) ($post['linked_event_id'] ?? 0) ?: null,
            'linked_booking_id' => (int) ($post['linked_booking_id'] ?? 0) ?: null,
            'is_featured'       => isset($post['is_featured']) ? 1 : 0,
            'is_downloadable'   => isset($post['is_downloadable']) ? 1 : 0,
            'status'            => $status,
            'scheduled_at'      => $scheduledAt ?: null,
            'meta_title'        => substr(trim((string) ($post['meta_title'] ?? '')), 0, 500),
            'meta_description'  => substr(trim((string) ($post['meta_description'] ?? '')), 0, 500),
            'sort_order'        => max(0, (int) ($post['sort_order'] ?? 0)),
        ];

        return [$data, $errors];
    }

    private function makeSlug(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }

    private function ensureUniqueSlug(string &$slug, int $excludeId = 0): void
    {
        $base = $slug;
        $i    = 1;
        while (true) {
            $row = $this->db->fetchOne(
                "SELECT id FROM gallery_albums WHERE slug=? AND deleted_at IS NULL" . ($excludeId ? " AND id != {$excludeId}" : ''),
                [$slug]
            );
            if (!$row) {
                break;
            }
            $slug = $base . '-' . $i++;
        }
    }

    private function findAlbum(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM gallery_albums WHERE id=? AND deleted_at IS NULL",
            [$id]
        ) ?: null;
    }

    private function wrapSingleFile(array $file): array
    {
        return [
            'name'     => [$file['name']],
            'type'     => [$file['type']],
            'tmp_name' => [$file['tmp_name']],
            'error'    => [$file['error']],
            'size'     => [$file['size']],
        ];
    }
}
