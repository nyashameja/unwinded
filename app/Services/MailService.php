<?php

declare(strict_types=1);

namespace Unwinded\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Thin wrapper around PHPMailer for sending transactional email.
 * All SMTP credentials come from config — never from the database or request.
 */
class MailService
{
    public function __construct(private array $config) {}

    /**
     * Send a single email immediately (bypasses the queue).
     * Use for test sends; production sends go through the email_queue table.
     *
     * @throws \RuntimeException on failure
     */
    public function send(
        string $toAddress,
        string $toName,
        string $subject,
        string $bodyHtml,
        ?string $replyTo = null,
    ): void {
        $mailer = $this->buildMailer();
        $mailer->addAddress($toAddress, $toName);
        $mailer->Subject = $subject;
        $mailer->Body    = $bodyHtml;
        $mailer->AltBody = strip_tags($bodyHtml);

        if ($replyTo) {
            $mailer->addReplyTo($replyTo);
        }

        if (!$mailer->send()) {
            throw new \RuntimeException('Mail send failed: ' . $mailer->ErrorInfo);
        }
    }

    /**
     * Verify the SMTP connection — used by settings test mail.
     *
     * @return array{ok: bool, message: string}
     */
    public function testConnection(): array
    {
        try {
            $mailer = $this->buildMailer();
            $mailer->smtpConnect([
                'ssl' => [
                    'verify_peer'       => true,
                    'verify_peer_name'  => true,
                    'allow_self_signed' => false,
                ],
            ]);
            $mailer->smtpClose();
            return ['ok' => true, 'message' => 'SMTP connection successful.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function buildMailer(): PHPMailer
    {
        $mailer = new PHPMailer(exceptions: true);
        $mailer->isSMTP();
        $mailer->Host       = $this->config['host']       ?? '';
        $mailer->Port       = (int) ($this->config['port'] ?? 587);
        $mailer->SMTPAuth   = true;
        $mailer->Username   = $this->config['username']   ?? '';
        $mailer->Password   = $this->config['password']   ?? '';
        $mailer->SMTPSecure = match ($this->config['encryption'] ?? 'tls') {
            'ssl'  => PHPMailer::ENCRYPTION_SMTPS,
            default => PHPMailer::ENCRYPTION_STARTTLS,
        };
        $mailer->setFrom(
            $this->config['from_address'] ?? '',
            $this->config['from_name']    ?? 'Unwinded',
        );
        $mailer->isHTML(true);
        $mailer->CharSet = PHPMailer::CHARSET_UTF8;

        // Catch-all mode for development
        $mode = $this->config['mode'] ?? 'live';
        if ($mode === 'log') {
            $mailer->isSendmail();
            $mailer->Sendmail = '/bin/true';
        }

        return $mailer;
    }
}
