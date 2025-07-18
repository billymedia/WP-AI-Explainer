<?php
/**
 * Fired when the plugin is uninstalled
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin data on uninstall
 */
class ExplainerPlugin_Uninstaller {
    
    /**
     * Run the uninstall process
     */
    public static function uninstall() {
        // Check if user wants to keep data
        if (get_option('explainer_keep_data_on_uninstall', false)) {
            return;
        }
        
        // Remove all plugin options
        self::remove_plugin_options();
        
        // Drop custom database tables
        self::drop_database_tables();
        
        // Clear all caches
        self::clear_all_caches();
        
        // Clear transients
        self::clear_transients();
        
        // Remove custom directories
        self::remove_directories();
        
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Remove user meta
        self::remove_user_meta();
        
        // Clean up rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Remove all plugin options
     */
    private static function remove_plugin_options() {
        $options = array(
            'explainer_enabled',
            'explainer_api_key',
            'explainer_api_model',
            'explainer_max_selection_length',
            'explainer_min_selection_length',
            'explainer_max_words',
            'explainer_min_words',
            'explainer_cache_enabled',
            'explainer_cache_duration',
            'explainer_rate_limit_enabled',
            'explainer_rate_limit_logged',
            'explainer_rate_limit_anonymous',
            'explainer_included_selectors',
            'explainer_excluded_selectors',
            'explainer_tooltip_bg_color',
            'explainer_tooltip_text_color',
            'explainer_toggle_position',
            'explainer_debug_mode',
            'explainer_custom_prompt',
            'explainer_debug_logs',
            'explainer_plugin_activated',
            'explainer_plugin_deactivated',
            'explainer_db_version',
            'explainer_keep_data_on_uninstall',
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Remove any options that start with explainer_
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'explainer_%'");
    }
    
    /**
     * Drop custom database tables
     */
    private static function drop_database_tables() {
        global $wpdb;
        
        // No custom database tables to drop
        // All data is stored in wp_options table
    }
    
    /**
     * Clear all caches
     */
    private static function clear_all_caches() {
        // Clear WordPress object cache
        wp_cache_flush();
        
        // Clear plugin-specific cache
        wp_cache_delete_group('explainer_explanations');
        wp_cache_delete_group('explainer_settings');
        
        // Clear file-based cache
        self::clear_file_cache();
    }
    
    /**
     * Clear file-based cache
     */
    private static function clear_file_cache() {
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
     * Clear transients
     */
    private static function clear_transients() {
        global $wpdb;
        
        // Clear rate limiting transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_explainer_rate_limit_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_explainer_rate_limit_%'");
        
        // Clear cache transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_explainer_cache_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_explainer_cache_%'");
        
        // Clear settings transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_explainer_settings_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_explainer_settings_%'");
        
        // Clear API test transients
        delete_transient('explainer_api_test');
    }
    
    /**
     * Remove custom directories
     */
    private static function remove_directories() {
        $upload_dir = wp_upload_dir();
        $plugin_dir = $upload_dir['basedir'] . '/explainer-plugin';
        
        if (is_dir($plugin_dir)) {
            self::remove_directory_recursive($plugin_dir);
        }
    }
    
    /**
     * Recursively remove directory
     */
    private static function remove_directory_recursive($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::remove_directory_recursive($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Clear scheduled events
     */
    private static function clear_scheduled_events() {
        // Clear cache cleanup event
        wp_clear_scheduled_hook('explainer_cache_cleanup');
        
        // Clear log cleanup event
        wp_clear_scheduled_hook('explainer_log_cleanup');
        
        // Clear debug log cleanup event
        wp_clear_scheduled_hook('explainer_debug_cleanup');
        
        // Clear any other scheduled events
        wp_clear_scheduled_hook('explainer_daily_cleanup');
        wp_clear_scheduled_hook('explainer_weekly_cleanup');
    }
    
    /**
     * Remove user meta
     */
    private static function remove_user_meta() {
        global $wpdb;
        
        // Remove user preferences
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'explainer_%'");
        
        // Remove user debug preferences
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'explainer_debug_%'");
        
        // Remove user settings
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'explainer_preferences_%'");
    }
}

// Run the uninstall process
ExplainerPlugin_Uninstaller::uninstall();