<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;
use Unwinded\Services\MailService;
use Unwinded\Services\SettingsService;

class SettingsController
{
    public function __construct(
        private Database        $db,
        private Request         $request,
        private View            $view,
        private SettingsService $settings,
        private MailService     $mail,
        private ActivityLogger  $activityLogger,
    ) {}

    public function index(): Response
    {
        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/settings/index', [
                'pageTitle' => 'Settings',
                'grouped'   => $this->settings->grouped(),
            ])
        );
    }

    public function update(): Response
    {
        $data = $this->request->all();
        unset($data['_csrf_token']);

        // Cast booleans: checkboxes not submitted are absent (= 0)
        $grouped = $this->settings->grouped();
        foreach ($grouped as $rows) {
            foreach ($rows as $row) {
                if ($row['type'] === 'boolean') {
                    $data[$row['key']] = isset($data[$row['key']]) ? '1' : '0';
                }
            }
        }

        $this->settings->bulkSet($data);
        $this->activityLogger->log('settings.updated', 'settings');

        flash('success', 'Settings saved.');
        return Response::make()->redirect(url('/admin/settings'));
    }

    public function testMail(): Response
    {
        $result = $this->mail->testConnection();

        if ($this->request->wantsJson() || $this->request->isAjax()) {
            return Response::make()->json($result);
        }

        $type = $result['ok'] ? 'success' : 'error';
        flash($type, $result['message']);
        return Response::make()->redirect(url('/admin/settings#mail'));
    }
}
