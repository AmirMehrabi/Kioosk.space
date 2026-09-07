<?php

return [
    'ttl_seconds' => 180,
    'resend_seconds' => 60,
    'max_attempts' => 5,
    'phone_hourly_limit' => 5,
    'ip_hourly_limit' => 20,
    'failed_verification_limit' => 10,
    'staff_session_seconds' => 1800,
    'log_failed_code' => env('OTP_LOG_FAILED_CODE', false),
];
