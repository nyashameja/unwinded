<?php
return [
    'session_lifetime'      => 480,   // minutes (8 hours)
    'session_idle_timeout'  => 120,   // minutes of inactivity
    'login_throttle_max'    => 5,     // attempts
    'login_throttle_window' => 15,    // minutes
    'login_lockout_minutes' => 30,
    'password_reset_expire' => 60,    // minutes
    'quote_validity_days'   => 14,
    'quote_reminder_day'    => 10,
    'deposit_percentage'    => 50,
    'deposit_soft_deadline_hours' => 48,
    'deposit_hard_deadline_days'  => 5,   // business days before event
    'ticket_reservation_minutes'  => 15,
    'refund_processing_days'      => 10,  // business days after approval
    'cron_secret'           => $_ENV['CRON_SECRET'] ?? '',
    'rate_limits' => [
        'quote_form'    => ['max' => 5,  'window' => 60],
        'contact_form'  => ['max' => 5,  'window' => 60],
        'ticket_checkout' => ['max' => 10, 'window' => 60],
        'login'         => ['max' => 5,  'window' => 15],
        'password_reset'=> ['max' => 3,  'window' => 60],
        'gallery_password' => ['max' => 10, 'window' => 60],
    ],
];
