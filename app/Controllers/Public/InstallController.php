<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;

/**
 * One-time install wizard. Disabled once a user account exists.
 */
class InstallController
{
    public function __construct(
        private Database $db,
        private Request  $request,
        private View     $view,
    ) {}

    public function index(): Response
    {
        if ($this->isInstalled()) {
            return Response::make()->redirect(url('/admin'));
        }

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/install/index', [
                'pageTitle'  => 'Install Unwinded',
                'metaRobots' => 'noindex,nofollow',
            ])
        );
    }

    public function run(): Response
    {
        if ($this->isInstalled()) {
            return Response::make()->redirect(url('/admin'));
        }

        $output   = [];
        $success  = true;
        $root     = defined('APP_ROOT') ? APP_ROOT : dirname(dirname(dirname(__DIR__)));
        $console  = $root . '/bin/console';

        if (!is_executable($console)) {
            return Response::make()->html(
                $this->view->renderWithLayout('public', 'public/install/index', [
                    'pageTitle' => 'Install Unwinded',
                    'metaRobots' => 'noindex,nofollow',
                    'error' => 'The console binary is not executable. Run migrations manually: php bin/console migrate && php bin/console db:seed',
                ])
            );
        }

        foreach (['migrate', 'db:seed'] as $cmd) {
            $out = [];
            $code = 0;
            exec("php " . escapeshellarg($console) . " {$cmd} 2>&1", $out, $code);
            $output[$cmd] = ['code' => $code, 'lines' => $out];
            if ($code !== 0) {
                $success = false;
                break;
            }
        }

        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/install/index', [
                'pageTitle'  => 'Install Unwinded',
                'metaRobots' => 'noindex,nofollow',
                'ran'        => true,
                'success'    => $success,
                'output'     => $output,
            ])
        );
    }

    private function isInstalled(): bool
    {
        try {
            return (int) $this->db->fetchScalar("SELECT COUNT(*) FROM users") > 0;
        } catch (\Throwable) {
            return false;
        }
    }
}
