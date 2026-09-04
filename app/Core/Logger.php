<?php
declare(strict_types=1);

namespace Unwinded\Core;

/**
 * Minimal PSR-3-shaped file logger. Writes daily rotating log files.
 * Levels (ascending severity): debug, info, notice, warning, error, critical, alert, emergency.
 */
class Logger
{
    private const LEVELS = ['debug' => 0, 'info' => 1, 'notice' => 2, 'warning' => 3,
                             'error' => 4, 'critical' => 5, 'alert' => 6, 'emergency' => 7];

    public function __construct(
        private string $logPath,
        private string $minLevel = 'debug'
    ) {}

    public function debug(string $message, array $context = []): void    { $this->log('debug', $message, $context); }
    public function info(string $message, array $context = []): void     { $this->log('info', $message, $context); }
    public function notice(string $message, array $context = []): void   { $this->log('notice', $message, $context); }
    public function warning(string $message, array $context = []): void  { $this->log('warning', $message, $context); }
    public function error(string $message, array $context = []): void    { $this->log('error', $message, $context); }
    public function critical(string $message, array $context = []): void { $this->log('critical', $message, $context); }

    public function log(string $level, string $message, array $context = []): void
    {
        if ((self::LEVELS[$level] ?? 0) < (self::LEVELS[$this->minLevel] ?? 0)) {
            return;
        }
        $dir  = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $date   = date('Y-m-d');
        $file   = $dir . '/' . basename($this->logPath, '.log') . '-' . $date . '.log';
        $ts     = date('Y-m-d H:i:s');
        $ctx    = $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $line   = "[{$ts}] {$level}: {$message}{$ctx}" . PHP_EOL;
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
