<?php
/**
 * Plugin activation functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle plugin activation
 */
class ExplainerPlugin_Activator {
    
    /**
     * Activate the plugin
     */
    public static function activate() {
        // Check WordPress version
        if (!self::check_wordpress_version()) {
            deactivate_plugins(EXPLAINER_PLUGIN_BASENAME);
            wp_die(__('This plugin requires WordPress 5.0 or higher.', 'explainer-plugin'));
        }
        
        // Check PHP version
        if (!self::check_php_version()) {
            deactivate_plugins(EXPLAINER_PLUGIN_BASENAME);
            wp_die(__('This plugin requires PHP 7.4 or higher.', 'explainer-plugin'));
        }
        
        // Create database tables
        self::create_database_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create necessary directories
        self::create_directories();
        
        // Set activation flag
        update_option('explainer_plugin_activated', true);
        
        // Clear any existing caches
        self::clear_caches();
    }
    
    /**
     * Check WordPress version compatibility
     */
    private static function check_wordpress_version() {
        global $wp_version;
        return version_compare($wp_version, '5.0', '>=');
    }
    
    /**
     * Check PHP version compatibility
     */
    private static function check_php_version() {
        return version_compare(PHP_VERSION, '7.4', '>=');
    }
    
    /**
     * Create database tables
     */
    private static function create_database_tables() {
        // No database tables needed for this plugin
        // Debug logs are stored in wp_options table
        
        // Update database version
        update_option('explainer_db_version', '1.0');
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $default_options = array(
            'explainer_enabled' => true,
            'explainer_api_provider' => 'openai',
            'explainer_api_key' => '',
            'explainer_claude_api_key' => '',
            'explainer_api_model' => 'gpt-3.5-turbo',
            'explainer_max_selection_length' => 200,
            'explainer_min_selection_length' => 3,
            'explainer_max_words' => 30,
            'explainer_min_words' => 1,
            'explainer_cache_enabled' => true,
            'explainer_cache_duration' => 24, // hours
            'explainer_rate_limit_enabled' => true,
            'explainer_rate_limit_logged' => 20, // per minute
            'explainer_rate_limit_anonymous' => 10, // per minute
            'explainer_included_selectors' => 'article, main, .content, .entry-content, .post-content',
            'explainer_excluded_selectors' => 'nav, header, footer, aside, .widget, #wpadminbar, .admin-bar',
            'explainer_tooltip_bg_color' => '#333333',
            'explainer_tooltip_text_color' => '#ffffff',
            'explainer_button_enabled_color' => '#46b450',
            'explainer_button_disabled_color' => '#666666',
            'explainer_button_text_color' => '#ffffff',
            'explainer_toggle_position' => 'bottom-right',
            'explainer_debug_mode' => false,
            'explainer_custom_prompt' => 'Please provide a clear, concise explanation of the following text in 1-2 sentences: {{snippet}}',
        );
        
        foreach ($default_options as $option => $value) {
            add_option($option, $value);
        }
    }
    
    /**
     * Create necessary directories
     */
    private static function create_directories() {
        $upload_dir = wp_upload_dir();
        $plugin_dir = $upload_dir['basedir'] . '/explainer-plugin';
        
        if (!file_exists($plugin_dir)) {
            wp_mkdir_p($plugin_dir);
        }
        
        // Create cache directory
        $cache_dir = $plugin_dir . '/cache';
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }
        
        // Create logs directory
        $logs_dir = $plugin_dir . '/logs';
        if (!file_exists($logs_dir)) {
            wp_mkdir_p($logs_dir);
        }
        
        // Create .htaccess for security
        $htaccess_content = "Order deny,allow\nDeny from all\n";
        file_put_contents($plugin_dir . '/.htaccess', $htaccess_content);
    }
    
    /**
     * Clear caches
     */
    private static function clear_caches() {
        // Clear WordPress object cache
        wp_cache_flush();
        
        // Clear any transients
        delete_transient('explainer_api_test');
        
        // Clear plugin cache
        self::clear_plugin_cache();
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
}