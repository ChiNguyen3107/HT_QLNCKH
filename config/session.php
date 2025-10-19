<?php
/**
 * Session Security Configuration
 * 
 * Cấu hình bảo mật session toàn diện
 */

return [
    // Session name
    'name' => 'NCKH_SESSION',
    
    // Session lifetime (seconds)
    'lifetime' => 3600, // 1 giờ
    
    // Warning time before session expires (seconds)
    'warning_time' => 300, // 5 phút trước khi hết hạn
    
    // Session path
    'path' => '/',
    
    // Session domain
    'domain' => null, // null = current domain
    
    // Secure flag (HTTPS only)
    'secure' => false, // Set to true in production with HTTPS
    
    // HTTP only flag (prevent XSS)
    'http_only' => true,
    
    // Same site policy
    'same_site' => 'Lax', // Lax, Strict, None
    
    // Maximum concurrent sessions per user
    'max_concurrent_sessions' => 3,
    
    // Check IP address for session validation
    'check_ip_address' => false, // Set to true for high security
    
    // Fingerprint fields for session validation
    'fingerprint_fields' => [
        'user_agent',
        'ip_address',
        'accept_language'
    ],
    
    // Session regeneration settings
    'regeneration' => [
        'on_login' => true,
        'interval' => 300, // Regenerate every 5 minutes
        'on_role_change' => true
    ],
    
    // Session timeout settings
    'timeout' => [
        'warning_enabled' => true,
        'warning_message' => 'Phiên làm việc của bạn sắp hết hạn. Vui lòng lưu công việc và đăng nhập lại.',
        'auto_extend' => false, // Auto extend on activity
        'extend_on_activity' => true // Extend session on user activity
    ],
    
    // Session monitoring
    'monitoring' => [
        'enabled' => true,
        'log_all_activities' => true,
        'log_failed_validations' => true,
        'alert_on_suspicious_activity' => true
    ],
    
    // Session cleanup
    'cleanup' => [
        'enabled' => true,
        'interval' => 3600, // Run cleanup every hour
        'max_age' => 86400 // Remove sessions older than 24 hours
    ],
    
    // Security headers
    'security_headers' => [
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'strict_transport_security' => 'max-age=31536000; includeSubDomains'
    ],
    
    // Session storage
    'storage' => [
        'driver' => 'database', // database, file, redis
        'table' => 'session_logs',
        'connection' => 'default'
    ],
    
    // Rate limiting
    'rate_limiting' => [
        'enabled' => true,
        'max_requests_per_minute' => 60,
        'max_login_attempts_per_hour' => 10
    ],
    
    // Session hijacking protection
    'hijacking_protection' => [
        'enabled' => true,
        'fingerprint_validation' => true,
        'ip_validation' => false, // Can cause issues with mobile users
        'user_agent_validation' => true,
        'concurrent_session_limit' => true
    ],
    
    // Session fixation protection
    'fixation_protection' => [
        'enabled' => true,
        'regenerate_on_login' => true,
        'regenerate_on_role_change' => true,
        'regenerate_on_privilege_escalation' => true
    ],
    
    // Session data encryption
    'encryption' => [
        'enabled' => false, // Requires additional setup
        'algorithm' => 'AES-256-GCM',
        'key' => null // Will be generated automatically
    ],
    
    // Debug settings
    'debug' => [
        'enabled' => false,
        'log_level' => 'info', // debug, info, warning, error
        'log_file' => 'storage/logs/session.log'
    ]
];
