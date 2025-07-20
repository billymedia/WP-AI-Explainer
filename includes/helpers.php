<?php
/**
 * Helper functions for the plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// PHP 8.0 polyfills for compatibility with PHP 7.4
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return strpos($haystack, $needle) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return strpos($haystack, $needle) !== false;
    }
}

/**
 * Sanitize and validate text selection with comprehensive security checks
 *
 * @param string $text The text to sanitize
 * @return string|false Sanitized text or false if invalid
 */
function explainer_sanitize_text_selection($text) {
    if (empty($text)) {
        return false;
    }
    
    // Security: Check for extremely long input to prevent DoS
    if (strlen($text) > 5000) {
        return false;
    }
    
    // Security: Remove null bytes and control characters
    $text = str_replace(chr(0), '', $text);
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
    
    // Security: Prevent script injection attempts
    $dangerous_patterns = array(
        '/<script[^>]*>.*?<\/script>/is',
        '/<iframe[^>]*>.*?<\/iframe>/is',
        '/javascript:/i',
        '/vbscript:/i',
        '/on\w+\s*=/i',
        '/data:text\/html/i',
        '/data:application\/javascript/i'
    );
    
    foreach ($dangerous_patterns as $pattern) {
        if (preg_match($pattern, $text)) {
            return false;
        }
    }
    
    // Remove HTML tags and decode entities
    $text = wp_strip_all_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    
    // Security: Additional sanitization
    $text = sanitize_text_field($text);
    
    // Trim whitespace and normalize
    $text = trim($text);
    $text = preg_replace('/\s+/', ' ', $text);
    
    // Note: Length and word count validation is now handled in the API proxy
    // This function focuses on security validation only
    
    // Security: Check for suspicious patterns
    if (explainer_contains_suspicious_content($text)) {
        return false;
    }
    
    // Check for blocked words
    $blocked_word = explainer_check_blocked_words($text);
    if ($blocked_word !== false) {
        // Return false to indicate blocked content, but store the blocked word for error messaging
        add_filter('explainer_blocked_word_found', function() use ($blocked_word) {
            return $blocked_word;
        });
        return false;
    }
    
    return $text;
}

/**
 * Count words in text
 *
 * @param string $text The text to count
 * @return int Number of words
 */
function explainer_count_words($text) {
    if (empty($text)) {
        return 0;
    }
    
    // Split by whitespace and filter empty strings
    $words = array_filter(preg_split('/\s+/', trim($text)));
    return count($words);
}

/**
 * Generate cache key for explanation
 *
 * @param string $text The text to generate key for
 * @return string Cache key
 */
function explainer_generate_cache_key($text) {
    return 'explainer_cache_' . md5($text);
}

/**
 * Check if user has exceeded rate limit
 *
 * @param int|string $user_identifier User ID or IP address
 * @return bool True if rate limit exceeded
 */
function explainer_check_rate_limit($user_identifier) {
    if (!get_option('explainer_rate_limit_enabled', true)) {
        return false;
    }
    
    $is_logged_in = is_user_logged_in();
    $limit = $is_logged_in ? 
        get_option('explainer_rate_limit_logged', 20) : 
        get_option('explainer_rate_limit_anonymous', 10);
    
    $transient_key = 'explainer_rate_limit_' . $user_identifier;
    $current_count = get_transient($transient_key);
    
    if ($current_count === false) {
        set_transient($transient_key, 1, 60); // 1 minute
        return false;
    }
    
    if ($current_count >= $limit) {
        return true;
    }
    
    set_transient($transient_key, $current_count + 1, 60);
    return false;
}

/**
 * Get user identifier for rate limiting
 *
 * @return string User ID or IP address
 */
function explainer_get_user_identifier() {
    if (is_user_logged_in()) {
        return get_current_user_id();
    }
    
    return explainer_get_client_ip();
}

/**
 * Get client IP address with enhanced security
 *
 * @return string Client IP address
 */
function explainer_get_client_ip() {
    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', sanitize_text_field( wp_unslash( $_SERVER[$key] ) ) ) as $ip) {
                $ip = trim($ip);
                
                // Security: Sanitize IP and validate
                $ip = sanitize_text_field($ip);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
}

/**
 * Log API request for analytics
 *
 * @param array $data Request data
 * @return bool Success status
 */
function explainer_log_api_request($data) {
    if (!get_option('explainer_analytics_enabled', true)) {
        return false;
    }
    
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'explainer_requests';
    
    $default_data = array(
        'user_id' => is_user_logged_in() ? get_current_user_id() : null,
        'user_ip' => explainer_get_client_ip(),
        'selected_text' => '',
        'explanation' => '',
        'tokens_used' => 0,
        'cost' => 0.0,
        'response_time' => 0.0,
        'status' => 'success',
        'created_at' => current_time('mysql'),
    );
    
    $data = wp_parse_args($data, $default_data);
    
    // Use WordPress API instead of direct database query
    // For now, we'll disable this functionality and use options/transients instead
    // TODO: Create proper database table via activation hook or use post meta
    return false; // Disabled for security compliance
}

/**
 * Get cached explanation
 *
 * @param string $text The text to get explanation for
 * @return string|false Cached explanation or false if not found
 */
function explainer_get_cached_explanation($text) {
    if (!get_option('explainer_cache_enabled', true)) {
        return false;
    }
    
    $cache_key = explainer_generate_cache_key($text);
    return get_transient($cache_key);
}

/**
 * Cache explanation
 *
 * @param string $text The text that was explained
 * @param string $explanation The explanation to cache
 * @return bool Success status
 */
function explainer_cache_explanation($text, $explanation) {
    if (!get_option('explainer_cache_enabled', true)) {
        return false;
    }
    
    $cache_key = explainer_generate_cache_key($text);
    $cache_duration = get_option('explainer_cache_duration', 24) * HOUR_IN_SECONDS;
    
    return set_transient($cache_key, $explanation, $cache_duration);
}

/**
 * Clear explanation cache
 *
 * @return bool Success status
 */
function explainer_clear_cache() {
    global $wpdb;
    
    // Clear transients using WordPress API
    $transients = get_option( '_transient_timeout_explainer_cache_%' );
    if ( $transients ) {
        foreach ( $transients as $transient_key => $value ) {
            if ( str_starts_with( $transient_key, 'explainer_cache_' ) ) {
                delete_transient( str_replace( '_transient_timeout_', '', $transient_key ) );
            }
        }
    }
    
    // Alternative: Clear specific transients we know about
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $transient_keys = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", 'explainer_cache_%' ) );
    foreach ( $transient_keys as $key ) {
        delete_transient( str_replace( '_transient_', '', $key ) );
    }
    
    // Clear file cache
    $upload_dir = wp_upload_dir();
    $cache_dir = $upload_dir['basedir'] . '/explainer-plugin/cache';
    
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                wp_delete_file($file);
            }
        }
    }
    
    return true;
}

/**
 * Calculate estimated API cost
 *
 * @param int $tokens Number of tokens used
 * @param string $model The AI model used
 * @return float Estimated cost in USD
 */
function explainer_calculate_api_cost($tokens, $model = 'gpt-3.5-turbo') {
    // Pricing per 1000 tokens (as of 2024)
    $pricing = array(
        'gpt-3.5-turbo' => 0.0015,
        'gpt-4' => 0.03,
        'gpt-4-turbo' => 0.01,
    );
    
    $rate = isset($pricing[$model]) ? $pricing[$model] : $pricing['gpt-3.5-turbo'];
    
    return ($tokens / 1000) * $rate;
}

/**
 * Validate API key format
 *
 * @param string $api_key The API key to validate
 * @return bool True if valid format
 */
function explainer_validate_api_key($api_key) {
    if (empty($api_key)) {
        return false;
    }
    
    // Remove any whitespace
    $api_key = trim($api_key);
    
    // OpenAI API keys can have different formats:
    // - Legacy: sk-... (51 characters)
    // - New format: sk-proj-... (longer)
    // - Organization keys: sk-org-... 
    if (!str_starts_with($api_key, 'sk-')) {
        return false;
    }
    
    // Check minimum length (should be at least 20 characters)
    if (strlen($api_key) < 20) {
        return false;
    }
    
    // Check maximum reasonable length (should not exceed 200 characters)
    if (strlen($api_key) > 200) {
        return false;
    }
    
    // Check that it contains only valid characters (alphanumeric, hyphens, underscores)
    if (!preg_match('/^sk-[a-zA-Z0-9_-]+$/', $api_key)) {
        return false;
    }
    
    return true;
}

/**
 * Get plugin settings with defaults
 *
 * @return array Plugin settings
 */
function explainer_get_settings() {
    static $settings = null;
    
    if ($settings === null) {
        $settings = array(
            'enabled' => get_option('explainer_enabled', true),
            'api_key' => get_option('explainer_api_key', ''),
            'api_model' => get_option('explainer_api_model', 'gpt-3.5-turbo'),
            'max_selection_length' => get_option('explainer_max_selection_length', 200),
            'min_selection_length' => get_option('explainer_min_selection_length', 3),
            'max_words' => get_option('explainer_max_words', 30),
            'min_words' => get_option('explainer_min_words', 1),
            'cache_enabled' => get_option('explainer_cache_enabled', true),
            'cache_duration' => get_option('explainer_cache_duration', 24),
            'rate_limit_enabled' => get_option('explainer_rate_limit_enabled', true),
            'rate_limit_logged' => get_option('explainer_rate_limit_logged', 20),
            'rate_limit_anonymous' => get_option('explainer_rate_limit_anonymous', 10),
            'included_selectors' => get_option('explainer_included_selectors', 'article, main, .content, .entry-content, .post-content'),
            'excluded_selectors' => get_option('explainer_excluded_selectors', 'nav, header, footer, aside, .widget, #wpadminbar, .admin-bar'),
            'debug_mode' => get_option('explainer_debug_mode', false),
        );
    }
    
    return $settings;
}

/**
 * Check if current user can manage plugin
 *
 * @return bool True if user can manage plugin
 */
function explainer_user_can_manage() {
    return current_user_can('manage_options');
}

/**
 * Get plugin version
 *
 * @return string Plugin version
 */
function explainer_get_version() {
    return EXPLAINER_PLUGIN_VERSION;
}

/**
 * Check for suspicious content patterns
 *
 * @param string $text The text to check
 * @return bool True if suspicious content found
 */
function explainer_contains_suspicious_content($text) {
    // Check for actual code injection attempts
    // Note: This is for an AI text explanation service, so we should only block
    // actual malicious patterns, not common programming terms
    $suspicious_patterns = array(
        // Block actual script tags and injection attempts
        '/<script[^>]*>.*?<\/script>/is',
        '/<iframe[^>]*>.*?<\/iframe>/is',
        '/javascript:/i',
        '/vbscript:/i',
        '/on\w+\s*=/i', // onclick=, onload=, etc.
        
        // Block PHP code execution attempts
        '/<\?php/i',
        '/\$\{.*\}/s', // PHP variable interpolation
        
        // Block SQL injection patterns (but allow words like "select" in normal text)
        '/\b(union\s+select|select\s+\*\s+from|drop\s+table|insert\s+into)\b/i',
        
        // Block obvious eval attempts
        '/\beval\s*\(/i',
        '/\bexec\s*\(/i',
        '/\bsystem\s*\(/i',
        
        // Block base64 encoded content
        '/data:text\/html/i',
        '/data:application\/javascript/i',
        '/base64,/i'
    );
    
    foreach ($suspicious_patterns as $pattern) {
        if (preg_match($pattern, $text)) {
            return true;
        }
    }
    
    // Check for excessive special characters (more than 30% is suspicious)
    $special_char_count = preg_match_all('/[<>{}[\]()&|`~!@#$%^*+=\\\\\/]/', $text);
    if ($special_char_count > (strlen($text) * 0.3)) {
        return true;
    }
    
    // Check for encoding attempts
    if (preg_match('/%[0-9a-fA-F]{2}/', $text) || 
        preg_match('/&#[0-9]+;/', $text) || 
        preg_match('/&[a-zA-Z]+;/', $text)) {
        return true;
    }
    
    return false;
}

/**
 * Check if text contains blocked words
 *
 * @param string $text The text to check
 * @return bool|string False if no blocked words found, or the first blocked word found
 */
function explainer_check_blocked_words($text) {
    // Get blocked words from options
    $blocked_words = get_option('explainer_blocked_words', '');
    
    // If no blocked words configured, return false
    if (empty($blocked_words)) {
        return false;
    }
    
    // Get matching options
    $case_sensitive = get_option('explainer_blocked_words_case_sensitive', false);
    $whole_word_only = get_option('explainer_blocked_words_whole_word', false);
    
    // Split blocked words by newlines
    $words = explode("\n", $blocked_words);
    
    // Prepare text for comparison
    $compare_text = $case_sensitive ? $text : strtolower($text);
    
    foreach ($words as $word) {
        $word = trim($word);
        
        // Skip empty words
        if (empty($word)) {
            continue;
        }
        
        // Prepare word for comparison
        $compare_word = $case_sensitive ? $word : strtolower($word);
        
        // Check for match
        if ($whole_word_only) {
            // Use word boundaries for whole word matching
            $pattern = '/\b' . preg_quote($compare_word, '/') . '\b/';
            if ($case_sensitive) {
                $match = preg_match($pattern, $text);
            } else {
                $match = preg_match($pattern . 'i', $text);
            }
            
            if ($match) {
                return $word; // Return the original blocked word
            }
        } else {
            // Simple substring matching
            if (strpos($compare_text, $compare_word) !== false) {
                return $word; // Return the original blocked word
            }
        }
    }
    
    return false; // No blocked words found
}

/**
 * Enhanced rate limiting with DDoS protection
 *
 * @param int|string $user_identifier User ID or IP address
 * @return bool True if rate limit exceeded
 */
function explainer_check_advanced_rate_limit($user_identifier) {
    if (!get_option('explainer_rate_limit_enabled', true)) {
        return false;
    }
    
    $is_logged_in = is_user_logged_in();
    
    // Different limits for different time windows
    $limits = array(
        'minute' => $is_logged_in ? 
            get_option('explainer_rate_limit_logged', 20) : 
            get_option('explainer_rate_limit_anonymous', 10),
        'hour' => $is_logged_in ? 100 : 50,
        'day' => $is_logged_in ? 500 : 200
    );
    
    $time_windows = array(
        'minute' => 60,
        'hour' => 3600,
        'day' => 86400
    );
    
    foreach ($limits as $window => $limit) {
        $transient_key = "explainer_rate_limit_{$window}_{$user_identifier}";
        $current_count = get_transient($transient_key);
        
        if ($current_count === false) {
            set_transient($transient_key, 1, $time_windows[$window]);
        } else {
            if ($current_count >= $limit) {
                // Log potential DDoS attempt
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
                    if ( function_exists( 'wp_debug_log' ) ) {
                        wp_debug_log( "WP AI Explainer: Rate limit exceeded for {$user_identifier} in {$window} window" );
                    }
                }
                return true;
            }
            set_transient($transient_key, $current_count + 1, $time_windows[$window]);
        }
    }
    
    return false;
}

/**
 * Sanitize admin input with comprehensive validation
 *
 * @param mixed $input The input to sanitize
 * @param string $type The type of input (text, number, email, url, etc.)
 * @return mixed Sanitized input
 */
function explainer_sanitize_admin_input($input, $type = 'text') {
    switch ($type) {
        case 'text':
            return sanitize_text_field($input);
        case 'textarea':
            return sanitize_textarea_field($input);
        case 'number':
            return absint($input);
        case 'float':
            return floatval($input);
        case 'email':
            return sanitize_email($input);
        case 'url':
            return esc_url_raw($input);
        case 'key':
            return sanitize_key($input);
        case 'slug':
            return sanitize_title($input);
        case 'html':
            return wp_kses_post($input);
        case 'boolean':
            return (bool) $input;
        default:
            return sanitize_text_field($input);
    }
}

/**
 * Verify user permissions with enhanced security
 *
 * @param string $capability The capability to check
 * @param int $user_id Optional user ID to check (defaults to current user)
 * @return bool True if user has permission
 */
function explainer_verify_user_permission($capability = 'manage_options', $user_id = null) {
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }
    
    // Check if user exists
    if (!get_userdata($user_id)) {
        return false;
    }
    
    // Check capability
    if (!user_can($user_id, $capability)) {
        return false;
    }
    
    // Additional security checks
    if (is_user_logged_in()) {
        // Check if user is not banned or suspended
        $user_meta = get_user_meta($user_id, 'explainer_user_status', true);
        if ($user_meta === 'banned' || $user_meta === 'suspended') {
            return false;
        }
    }
    
    return true;
}

/**
 * Generate secure nonce with additional entropy
 *
 * @param string $action The action for the nonce
 * @return string The generated nonce
 */
function explainer_create_secure_nonce($action = 'explainer_nonce') {
    // Add additional entropy to the nonce
    $entropy = wp_generate_password(12, false);
    $enhanced_action = $action . '_' . $entropy;
    
    // Store entropy in transient for verification
    set_transient("explainer_nonce_entropy_{$action}", $entropy, 3600);
    
    return wp_create_nonce($enhanced_action);
}

/**
 * Verify secure nonce with additional entropy
 *
 * @param string $nonce The nonce to verify
 * @param string $action The action for the nonce
 * @return bool True if nonce is valid
 */
function explainer_verify_secure_nonce($nonce, $action = 'explainer_nonce') {
    // Get stored entropy
    $entropy = get_transient("explainer_nonce_entropy_{$action}");
    
    if (!$entropy) {
        return false;
    }
    
    $enhanced_action = $action . '_' . $entropy;
    
    // Verify nonce
    if (!wp_verify_nonce($nonce, $enhanced_action)) {
        return false;
    }
    
    // Clean up entropy after use
    delete_transient("explainer_nonce_entropy_{$action}");
    
    return true;
}

/**
 * Automatically disable the plugin due to quota exceeded
 *
 * @param string $reason The reason for disabling (e.g., quota exceeded message)
 * @param string $provider The AI provider that caused the issue
 * @return bool True if plugin was disabled successfully
 */
function explainer_auto_disable_plugin($reason, $provider = '') {
    // Disable the plugin
    update_option('explainer_enabled', false);
    
    // Store the reason for disabling
    update_option('explainer_disabled_reason', sanitize_textarea_field($reason));
    
    // Store the provider that caused the issue
    if (!empty($provider)) {
        update_option('explainer_disabled_provider', sanitize_text_field($provider));
    }
    
    // Store timestamp
    update_option('explainer_disabled_timestamp', current_time('mysql'));
    
    // Track how many times this has happened
    $count = (int) get_option('explainer_usage_exceeded_count', 0);
    update_option('explainer_usage_exceeded_count', $count + 1);
    
    // Log the event
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
        if ( function_exists( 'wp_debug_log' ) ) {
            wp_debug_log( sprintf(
                'WP AI Explainer: Auto-disabled due to quota exceeded. Provider: %s, Reason: %s',
                $provider,
                $reason
            ) );
        }
    }
    
    // Set a flag to show admin notice
    update_option('explainer_show_usage_notice', true);
    
    return true;
}

/**
 * Check if the plugin is currently auto-disabled
 *
 * @return bool True if plugin is auto-disabled
 */
function explainer_is_auto_disabled() {
    // Check if plugin is enabled
    if (get_option('explainer_enabled', true)) {
        return false;
    }
    
    // Check if there's a disable reason (indicates auto-disable vs manual disable)
    $reason = get_option('explainer_disabled_reason', '');
    return !empty($reason);
}

/**
 * Get the reason why the plugin was auto-disabled
 *
 * @return string The disable reason or empty string if not auto-disabled
 */
function explainer_get_disable_reason() {
    if (!explainer_is_auto_disabled()) {
        return '';
    }
    
    return get_option('explainer_disabled_reason', '');
}

/**
 * Get the provider that caused the auto-disable
 *
 * @return string The provider name or empty string
 */
function explainer_get_disable_provider() {
    if (!explainer_is_auto_disabled()) {
        return '';
    }
    
    return get_option('explainer_disabled_provider', '');
}

/**
 * Get the timestamp when the plugin was auto-disabled
 *
 * @return string MySQL timestamp or empty string
 */
function explainer_get_disable_timestamp() {
    if (!explainer_is_auto_disabled()) {
        return '';
    }
    
    return get_option('explainer_disabled_timestamp', '');
}

/**
 * Get human-readable time since plugin was disabled
 *
 * @return string Human-readable time difference
 */
function explainer_get_time_since_disabled() {
    $timestamp = explainer_get_disable_timestamp();
    if (empty($timestamp)) {
        return '';
    }
    
    $disabled_time = strtotime($timestamp);
    $current_time = current_time('timestamp');
    $time_diff = $current_time - $disabled_time;
    
    return human_time_diff($disabled_time, $current_time) . ' ' . __('ago', 'ai-explainer');
}

/**
 * Manually re-enable the plugin after auto-disable
 *
 * @return bool True if plugin was re-enabled successfully
 */
function explainer_reenable_plugin() {
    // Check if user has permission
    if (!current_user_can('manage_options')) {
        return false;
    }
    
    // Enable the plugin
    update_option('explainer_enabled', true);
    
    // Clear disable-related options
    delete_option('explainer_disabled_reason');
    delete_option('explainer_disabled_provider');
    delete_option('explainer_disabled_timestamp');
    delete_option('explainer_show_usage_notice');
    
    // Log the re-enable event
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
        if ( function_exists( 'wp_debug_log' ) ) {
            wp_debug_log( 'WP AI Explainer: Manually re-enabled by user: ' . get_current_user_id() );
        }
    }
    
    return true;
}

/**
 * Check if we should show the usage exceeded admin notice
 *
 * @return bool True if notice should be shown
 */
function explainer_should_show_usage_notice() {
    // Only show to admins
    if (!current_user_can('manage_options')) {
        return false;
    }
    
    // Check if plugin is auto-disabled and notice flag is set
    return explainer_is_auto_disabled() && get_option('explainer_show_usage_notice', false);
}

/**
 * Dismiss the usage exceeded admin notice
 *
 * @return bool True if notice was dismissed
 */
function explainer_dismiss_usage_notice() {
    // Check if user has permission
    if (!current_user_can('manage_options')) {
        return false;
    }
    
    delete_option('explainer_show_usage_notice');
    return true;
}

/**
 * Get usage exceeded statistics
 *
 * @return array Statistics about usage exceeded events
 */
function explainer_get_usage_exceeded_stats() {
    return array(
        'count' => (int) get_option('explainer_usage_exceeded_count', 0),
        'is_disabled' => explainer_is_auto_disabled(),
        'reason' => explainer_get_disable_reason(),
        'provider' => explainer_get_disable_provider(),
        'timestamp' => explainer_get_disable_timestamp(),
        'time_since' => explainer_get_time_since_disabled()
    );
}