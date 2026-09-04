<?php

declare(strict_types=1);

use Unwinded\Core\Database;

class ScheduledTasksSeeder
{
    public function run(Database $db): void
    {
        $tasks = [
            [
                'name'       => 'email:flush',
                'handler'    => 'Unwinded\\Tasks\\FlushEmailQueueTask',
                'schedule'   => 'every_5_minutes',
                'is_enabled' => 1,
            ],
            [
                'name'       => 'quotes:expire',
                'handler'    => 'Unwinded\\Tasks\\ExpireQuotesTask',
                'schedule'   => 'daily',
                'is_enabled' => 1,
            ],
            [
                'name'       => 'quotes:remind',
                'handler'    => 'Unwinded\\Tasks\\QuoteReminderTask',
                'schedule'   => 'daily',
                'is_enabled' => 1,
            ],
            [
                'name'       => 'bookings:deposit_remind',
                'handler'    => 'Unwinded\\Tasks\\DepositReminderTask',
                'schedule'   => 'daily',
                'is_enabled' => 1,
            ],
            [
                'name'       => 'bookings:balance_remind',
                'handler'    => 'Unwinded\\Tasks\\BalanceReminderTask',
                'schedule'   => 'daily',
                'is_enabled' => 1,
            ],
            [
                'name'       => 'tickets:release_expired',
                'handler'    => 'Unwinded\\Tasks\\ReleaseExpiredReservationsTask',
                'schedule'   => 'every_5_minutes',
                'is_enabled' => 1,
            ],
            [
                'name'       => 'gallery:expire_tokens',
                'handler'    => 'Unwinded\\Tasks\\ExpireGalleryTokensTask',
                'schedule'   => 'daily',
                'is_enabled' => 1,
            ],
            [
                'name'       => 'rate_limits:clean',
                'handler'    => 'Unwinded\\Tasks\\CleanRateLimitsTask',
                'schedule'   => 'daily',
                'is_enabled' => 1,
            ],
        ];

        foreach ($tasks as $task) {
            $existing = $db->fetchOne(
                "SELECT id FROM scheduled_tasks WHERE name = ?",
                [$task['name']]
            );

            if (!$existing) {
                $db->insert('scheduled_tasks', $task);
                echo "Scheduled task created: {$task['name']}" . PHP_EOL;
            } else {
                echo "Scheduled task exists (skipped): {$task['name']}" . PHP_EOL;
            }
        }
    }
}
