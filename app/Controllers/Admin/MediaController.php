<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Auth;
use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;
use Unwinded\Services\MediaUploadService;

class MediaController
{
    public function __construct(
        private Auth               $auth,
        private Database           $db,
        private Request            $request,
        private View               $view,
        private MediaUploadService $mediaUpload,
        private ActivityLogger     $activityLogger,
    ) {}

    public function index(): Response
    {
        $page    = max(1, (int) ($this->request->query('page', 1)));
        $perPage = 36;
        $offset  = ($page - 1) * $perPage;

        $total = (int) $this->db->fetchScalar(
            "SELECT COUNT(*) FROM media WHERE deleted_at IS NULL"
        );
        $items = $this->db->fetchAll(
            "SELECT * FROM media WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );

        // Attach public URLs to each item
        foreach ($items as &$item) {
            $item['urls'] = $this->mediaUpload->urlsFor($item);
        }
        unset($item);

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/media/index', [
                'pageTitle' => 'Media Library',
                'items'     => $items,
                'total'     => $total,
                'page'      => $page,
                'perPage'   => $perPage,
                'pages'     => (int) ceil($total / $perPage),
            ])
        );
    }

    public function upload(): Response
    {
        if (empty($_FILES['file'])) {
            return Response::make()->json(['error' => 'No file received.'], 422);
        }

        $user = $this->auth->user();

        try {
            $media = $this->mediaUpload->store($_FILES['file'], false, $user['id'] ?? null);
            $this->activityLogger->log('media.uploaded', 'media', $media['id']);
            return Response::make()->json([
                'ok'   => true,
                'id'   => $media['id'],
                'ref'  => $media['public_ref'],
                'name' => $media['original_name'],
                'urls' => $media['urls'],
            ]);
        } catch (\RuntimeException $e) {
            return Response::make()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function destroy(string $id): Response
    {
        $row = $this->db->fetchOne(
            "SELECT id FROM media WHERE id = ? AND deleted_at IS NULL",
            [(int) $id]
        );
        if (!$row) {
            flash('error', 'Media item not found.');
            return Response::make()->redirect(url('/admin/media'));
        }

        $this->mediaUpload->delete((int) $id);
        $this->activityLogger->log('media.deleted', 'media', $id);

        if ($this->request->isAjax()) {
            return Response::make()->json(['ok' => true]);
        }
        flash('success', 'File deleted.');
        return Response::make()->redirect(url('/admin/media'));
    }
}
