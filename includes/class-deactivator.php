<?php
/**
 * Plugin deactivation functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle plugin deactivation
 */
class ExplainerPlugin_Deactivator {
    
    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Clear caches
        self::clear_caches();
        
        // Clear transients
        self::clear_transients();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set deactivation flag
        update_option('explainer_plugin_deactivated', true);
        delete_option('explainer_plugin_activated');
    }
    
    /**
     * Clear scheduled WordPress events
     */
    private static function clear_scheduled_events() {
        // Clear cache cleanup event
        wp_clear_scheduled_hook('explainer_cache_cleanup');
        
        // Clear log cleanup event
        wp_clear_scheduled_hook('explainer_log_cleanup');
        
        // Clear analytics cleanup event
        wp_clear_scheduled_hook('explainer_analytics_cleanup');
    }
    
    /**
     * Clear all caches
     */
    private static function clear_caches() {
        // Clear WordPress object cache
        wp_cache_flush();
        
        // Clear plugin cache
        self::clear_plugin_cache();
        
        // Clear any cached explanations
        self::clear_explanation_cache();
    }
    
    /**
     * Clear plugin-specific cache
     */
    private static function clear_plugin_cache() {
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
    }
    
    /**
     * Clear explanation cache
     */
    private static function clear_explanation_cache() {
        global $wpdb;
        
        // Clear cache keys from wp_options table
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'explainer_cache_%'");
        
        // Clear WordPress object cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    /**
     * Clear transients
     */
    private static function clear_transients() {
        global $wpdb;
        
        // Clear rate limiting transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_explainer_rate_limit_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_explainer_rate_limit_%'");
        
        // Clear API test transients
        delete_transient('explainer_api_test');
        
        // Clear settings cache transients
        delete_transient('explainer_settings_cache');
    }
}