<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class GalleryController
{
    public function __construct(
        private Database $db,
        private Request  $request,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $segment = $this->request->str('segment');
        $allowed = ['corporate','restaurant','bridal','baby_shower','birthday','couples','private','public_event','other'];

        $where  = "ga.status = 'published' AND ga.deleted_at IS NULL";
        $params = [];
        if ($segment && in_array($segment, $allowed, true)) {
            $where  .= " AND ga.segment = ?";
            $params[] = $segment;
        }

        $albums = $this->db->fetchAll(
            "SELECT ga.*, CONCAT('/media/', m.year, '/', m.month, '/', m.public_ref, '_medium.webp') AS cover_url
             FROM gallery_albums ga
             LEFT JOIN media m ON m.id = ga.cover_image_id AND m.deleted_at IS NULL
             WHERE {$where}
             ORDER BY ga.sort_order, ga.event_date DESC",
            $params
        );

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/gallery/index', [
                'pageTitle'       => 'Gallery',
                'metaDescription' => 'Browse photos from Unwinded events — corporate, private, and public sip-and-paint sessions.',
                'albums'          => $albums,
                'activeSegment'   => $segment,
                'bodyClass'       => 'page page--gallery',
            ])
        );
    }

    public function show(string $slug): Response
    {
        $album = $this->db->fetchOne(
            "SELECT * FROM gallery_albums WHERE slug = ? AND status = 'published' AND deleted_at IS NULL",
            [$slug]
        );
        if (!$album) {
            return Response::make()->status(404)->html(
                $this->view->renderWithLayout('public', 'errors/404', ['pageTitle' => 'Album Not Found'])
            );
        }

        $images = $this->db->fetchAll(
            "SELECT gi.*, CONCAT('/media/', m.year, '/', m.month, '/', m.public_ref, '_large.webp') AS public_url, m.file_name
             FROM gallery_images gi
             JOIN media m ON m.id = gi.media_id AND m.deleted_at IS NULL
             WHERE gi.album_id = ?
             ORDER BY gi.sort_order",
            [$album['id']]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/gallery/show', [
                'pageTitle'       => $album['meta_title'] ?: $album['title'],
                'metaDescription' => $album['meta_description'] ?: $album['description'],
                'ogImage'         => $album['og_image'],
                'album'           => $album,
                'images'          => $images,
                'bodyClass'       => 'page page--gallery-album',
            ])
        );
    }

    public function private(string $token): Response
    {
        $tokenHash = hash('sha256', base64_decode($token));
        $access = $this->db->fetchOne(
            "SELECT gat.*, ga.title AS album_title, ga.slug AS album_slug
             FROM gallery_access_tokens gat
             JOIN gallery_albums ga ON ga.id = gat.album_id
             WHERE gat.token_hash = ?
               AND gat.is_active = 1
               AND (gat.expires_at IS NULL OR gat.expires_at > NOW())
               AND (gat.revoked_at IS NULL)",
            [$tokenHash]
        );

        if (!$access) {
            return Response::make()->status(404)->html(
                $this->view->renderWithLayout('public', 'errors/404', ['pageTitle' => 'Gallery Not Found'])
            );
        }

        // If password-protected, show unlock form unless session already unlocked
        $sessionKey = 'gallery_unlocked_' . $access['id'];
        $unlocked   = isset($_SESSION[$sessionKey]);

        if ($access['password_hash'] && !$unlocked) {
            return Response::make()->html(
                $this->view->renderWithLayout('public', 'public/gallery/private', [
                    'pageTitle'  => 'Private Gallery',
                    'access'     => $access,
                    'token'      => $token,
                    'locked'     => true,
                    'bodyClass'  => 'page page--gallery-private',
                ])
            );
        }

        $images = $this->db->fetchAll(
            "SELECT gi.*, CONCAT('/media/', m.year, '/', m.month, '/', m.public_ref, '_large.webp') AS public_url, m.file_name
             FROM gallery_images gi
             JOIN media m ON m.id = gi.media_id AND m.deleted_at IS NULL
             WHERE gi.album_id = ?
             ORDER BY gi.sort_order",
            [$access['album_id']]
        );

        $this->db->insert('gallery_access_logs', [
            'token_id'   => $access['id'],
            'album_id'   => $access['album_id'],
            'event_type' => 'view',
            'ip_address' => $this->request->ip(),
            'user_agent' => substr($this->request->header('User-Agent') ?? '', 0, 500),
        ]);

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/gallery/private', [
                'pageTitle'        => $access['album_title'],
                'access'           => $access,
                'token'            => $token,
                'locked'           => false,
                'images'           => $images,
                'isDownloadable'   => (bool) $this->db->fetchScalar(
                    "SELECT is_downloadable FROM gallery_albums WHERE id = ?",
                    [$access['album_id']]
                ),
                'bodyClass'        => 'page page--gallery-private',
            ])
        );
    }

    public function privateAuth(string $token): Response
    {
        $tokenHash = hash('sha256', base64_decode($token));
        $access = $this->db->fetchOne(
            "SELECT * FROM gallery_access_tokens
             WHERE token_hash = ? AND is_active = 1
               AND (expires_at IS NULL OR expires_at > NOW())
               AND revoked_at IS NULL",
            [$tokenHash]
        );

        if (!$access) {
            return Response::make()->status(404)->html(
                $this->view->renderWithLayout('public', 'errors/404', ['pageTitle' => 'Gallery Not Found'])
            );
        }

        $password = $this->request->str('password') ?? '';
        if (!$access['password_hash'] || password_verify($password, $access['password_hash'])) {
            $_SESSION['gallery_unlocked_' . $access['id']] = true;
            return Response::make()->redirect(url('/g/' . $token));
        }

        $this->db->insert('gallery_access_logs', [
            'token_id'   => $access['id'],
            'album_id'   => $access['album_id'],
            'event_type' => 'password_fail',
            'ip_address' => $this->request->ip(),
            'user_agent' => substr($this->request->header('User-Agent') ?? '', 0, 500),
        ]);

        flash('error', 'Incorrect password. Please try again.');
        return Response::make()->redirect(url('/g/' . $token));
    }
}
