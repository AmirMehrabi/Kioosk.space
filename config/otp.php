<?php

return [
    'ttl_seconds' => 180,
    'resend_seconds' => 60,
    'max_attempts' => 5,
    'phone_hourly_limit' => (int) env('OTP_PHONE_HOURLY_LIMIT', 5),
    'ip_hourly_limit' => (int) env('OTP_IP_HOURLY_LIMIT', 20),
    'send_per_minute' => (int) env('OTP_SEND_PER_MINUTE', 5),
    'verify_per_minute' => (int) env('OTP_VERIFY_PER_MINUTE', 30),
    'failed_verification_limit' => 10,
    'staff_session_seconds' => (int) env('OTP_STAFF_SESSION_SECONDS', 1800),
    'log_failed_code' => env('OTP_LOG_FAILED_CODE', false),
];
