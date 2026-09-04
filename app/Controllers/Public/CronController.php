<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;

/**
 * Token-protected cron fallback for hosts that cannot run CLI.
 * Set cron.web_token in settings; call /cron/run?token=<value> from cPanel cron.
 */
class CronController
{
    public function __construct(
        private Database $db,
        private Request  $request,
    ) {}

    public function run(): Response
    {
        $expectedToken = setting('cron.web_token', '');
        $suppliedToken = $this->request->str('token') ?? '';

        if (!$expectedToken || !hash_equals($expectedToken, $suppliedToken)) {
            return Response::make()->status(403)->body('Forbidden');
        }

        $root    = defined('APP_ROOT') ? APP_ROOT : dirname(dirname(dirname(__DIR__)));
        $console = $root . '/bin/console';

        if (is_executable($console)) {
            $out  = [];
            $code = 0;
            exec("php " . escapeshellarg($console) . " schedule:run 2>&1", $out, $code);
            return Response::make()
                ->header('Content-Type', 'text/plain; charset=utf-8')
                ->body("Exit: {$code}\n" . implode("\n", $out));
        }

        return Response::make()
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->body("console binary not executable");
    }
}
