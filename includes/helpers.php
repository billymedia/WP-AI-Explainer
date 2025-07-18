<?php
/**
 * Helper functions for the plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
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
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                
                // Security: Sanitize IP and validate
                $ip = sanitize_text_field($ip);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
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
    
    return $wpdb->insert($table_name, $data) !== false;
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
    
    // Clear transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_explainer_cache_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_explainer_cache_%'");
    
    // Clear file cache
    $upload_dir = wp_upload_dir();
    $cache_dir = $upload_dir['basedir'] . '/explainer-plugin/cache';
    
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
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
    if (strpos($api_key, 'sk-') !== 0) {
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
    // Check for common injection patterns
    $suspicious_patterns = array(
        '/\b(union|select|insert|update|delete|drop|create|alter|exec|execute)\b/i',
        '/\b(script|iframe|object|embed|form|input|textarea|button)\b/i',
        '/\b(eval|setTimeout|setInterval|Function|constructor)\b/i',
        '/\b(document\.|window\.|location\.|history\.)/i',
        '/\b(alert|confirm|prompt|console\.)/i',
        '/\b(XMLHttpRequest|fetch|ajax)/i',
        '/\b(base64_decode|eval|exec|system|shell_exec|passthru|file_get_contents)/i',
        '/\b(wp_query|wp_db|wpdb|global \$)/i',
        '/\b(\$_GET|\$_POST|\$_REQUEST|\$_COOKIE|\$_SESSION|\$_SERVER|\$_FILES)/i',
        '/\b(include|require|include_once|require_once)/i',
        '/\b(file_put_contents|fwrite|fopen|fclose|unlink|rmdir|mkdir)/i',
        '/\b(curl_exec|curl_init|wp_remote_get|wp_remote_post)/i',
        '/\b(mail|wp_mail|wp_send_json|wp_die)/i',
        '/\b(add_action|add_filter|remove_action|remove_filter)/i',
        '/\b(do_action|apply_filters|current_user_can|is_admin)/i',
        '/\b(wp_enqueue_script|wp_enqueue_style|wp_localize_script)/i',
        '/\b(register_activation_hook|register_deactivation_hook)/i',
        '/\b(wp_verify_nonce|wp_create_nonce|check_admin_referer)/i'
    );
    
    foreach ($suspicious_patterns as $pattern) {
        if (preg_match($pattern, $text)) {
            return true;
        }
    }
    
    // Check for excessive special characters
    $special_char_count = preg_match_all('/[<>{}[\]()&|`~!@#$%^*+=\\\\\/]/', $text);
    if ($special_char_count > (strlen($text) * 0.2)) {
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
                error_log("Explainer Plugin: Rate limit exceeded for {$user_identifier} in {$window} window");
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