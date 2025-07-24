<?php
/**
 * Admin functionality for the plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle admin functionality
 */
class ExplainerPlugin_Admin {
    
    /**
     * API proxy instance
     */
    private $api_proxy;
    
    /**
     * Initialize the admin class
     */
    public function __construct() {
        $this->api_proxy = new ExplainerPlugin_API_Proxy();
        
        // Hook to process API keys before saving
        add_filter('pre_update_option_explainer_api_key', array($this, 'process_api_key_save'), 10, 2);
        add_filter('pre_update_option_explainer_claude_api_key', array($this, 'process_claude_api_key_save'), 10, 2);
        
        // Hook to process custom prompt before saving
        add_filter('pre_update_option_explainer_custom_prompt', array($this, 'process_custom_prompt_save'), 10, 2);
        
        // Hook for admin notices
        add_action('admin_notices', array($this, 'display_usage_exceeded_notice'));
        
        // AJAX handlers for usage exceeded notice actions
        add_action('wp_ajax_explainer_reenable_plugin', array($this, 'handle_reenable_plugin'));
        add_action('wp_ajax_explainer_dismiss_usage_notice', array($this, 'handle_dismiss_usage_notice'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Custom AI-themed icon as base64 data URI
        $icon_svg_base64 = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0iY3VycmVudENvbG9yIj4KICA8cGF0aCBkPSJNMTAgMkM3LjIgMiA1IDQuMiA1IDdjMCAuOC4yIDEuNS41IDIuMkw0IDEwLjdjLS4zLjMtLjMuOCAwIDEuMWwuOS45Yy4zLjMuOC4zIDEuMSAwTDcuNSAxMWMuNy4zIDEuNC41IDIuMi41aC42Yy44IDAgMS41LS4yIDIuMi0uNUwxNCAxMi43Yy4zLjMuOC4zIDEuMSAwbC45LS45Yy4zLS4zLjMtLjggMC0xLjFsLTEuNS0xLjVjLjMtLjcuNS0xLjQuNS0yLjIgMC0yLjgtMi4yLTUtNS01em0tMiA1YzAtLjYuNC0xIDEtMXMxIC40IDEgMS0uNCAxLTEgMS0xLS40LTEtMXptMyAwYzAtLjYuNC0xIDEtMXMxIC40IDEgMS0uNCAxLTEgMS0xLS40LTEtMXoiLz4KICA8cGF0aCBkPSJNNiAxNGg4djFINnYtMXptMSAyaDZ2MUg3di0xem0xIDJoNHYxSDh2LTF6Ii8+CiAgPGNpcmNsZSBjeD0iOCIgY3k9IjUiIHI9Ii41Ii8+CiAgPGNpcmNsZSBjeD0iMTIiIGN5PSI1IiByPSIuNSIvPgogIDxjaXJjbGUgY3g9IjEwIiBjeT0iNCIgcj0iLjUiLz4KPC9zdmc+';
        
        add_menu_page(
            __('WP AI Explainer Settings', 'wp-ai-explainer'),
            __('WP AI Explainer', 'wp-ai-explainer'),
            'manage_options',
            'explainer-settings',
            array($this, 'settings_page'),
            $icon_svg_base64,
            30
        );
    }
    
    /**
     * Initialize settings
     */
    public function settings_init() {
        // Register all settings with validation
        register_setting('explainer_settings', 'explainer_enabled', array('sanitize_callback' => 'absint'));
        register_setting('explainer_settings', 'explainer_language', array('sanitize_callback' => array($this, 'validate_language')));
        register_setting('explainer_settings', 'explainer_api_provider', array('sanitize_callback' => array($this, 'validate_api_provider')));
        register_setting('explainer_settings', 'explainer_api_key', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('explainer_settings', 'explainer_claude_api_key', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('explainer_settings', 'explainer_api_model', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('explainer_settings', 'explainer_custom_prompt', array('sanitize_callback' => array($this, 'validate_custom_prompt')));
        register_setting('explainer_settings', 'explainer_max_selection_length', array('sanitize_callback' => array($this, 'validate_max_selection_length')));
        register_setting('explainer_settings', 'explainer_min_selection_length', array('sanitize_callback' => array($this, 'validate_min_selection_length')));
        register_setting('explainer_settings', 'explainer_max_words', array('sanitize_callback' => array($this, 'validate_max_words')));
        register_setting('explainer_settings', 'explainer_min_words', array('sanitize_callback' => array($this, 'validate_min_words')));
        register_setting('explainer_settings', 'explainer_cache_enabled', array('sanitize_callback' => 'absint'));
        register_setting('explainer_settings', 'explainer_cache_duration', array('sanitize_callback' => array($this, 'validate_cache_duration')));
        register_setting('explainer_settings', 'explainer_rate_limit_enabled', array('sanitize_callback' => 'absint'));
        register_setting('explainer_settings', 'explainer_rate_limit_logged', array('sanitize_callback' => array($this, 'validate_rate_limit_logged')));
        register_setting('explainer_settings', 'explainer_rate_limit_anonymous', array('sanitize_callback' => array($this, 'validate_rate_limit_anonymous')));
        register_setting('explainer_settings', 'explainer_included_selectors', array('sanitize_callback' => 'wp_kses_post'));
        register_setting('explainer_settings', 'explainer_excluded_selectors', array('sanitize_callback' => 'wp_kses_post'));
        register_setting('explainer_settings', 'explainer_tooltip_bg_color', array('sanitize_callback' => 'sanitize_hex_color'));
        register_setting('explainer_settings', 'explainer_tooltip_text_color', array('sanitize_callback' => 'sanitize_hex_color'));
        register_setting('explainer_settings', 'explainer_button_enabled_color', array('sanitize_callback' => 'sanitize_hex_color'));
        register_setting('explainer_settings', 'explainer_button_disabled_color', array('sanitize_callback' => 'sanitize_hex_color'));
        register_setting('explainer_settings', 'explainer_button_text_color', array('sanitize_callback' => 'sanitize_hex_color'));
        register_setting('explainer_settings', 'explainer_toggle_position', array('sanitize_callback' => array($this, 'validate_toggle_position')));
        register_setting('explainer_settings', 'explainer_show_disclaimer', array('sanitize_callback' => 'absint'));
        register_setting('explainer_settings', 'explainer_show_provider', array('sanitize_callback' => 'absint'));
        register_setting('explainer_settings', 'explainer_tooltip_footer_color', array('sanitize_callback' => 'sanitize_hex_color'));
        register_setting('explainer_settings', 'explainer_debug_mode', array('sanitize_callback' => 'absint'));
        register_setting('explainer_settings', 'explainer_blocked_words', array('sanitize_callback' => array($this, 'sanitize_blocked_words')));
        register_setting('explainer_settings', 'explainer_blocked_words_case_sensitive', array('sanitize_callback' => 'absint'));
        register_setting('explainer_settings', 'explainer_blocked_words_whole_word', array('sanitize_callback' => 'absint'));
        
        // Add settings sections
        add_settings_section(
            'explainer_basic_settings',
            __('Basic Settings', 'wp-ai-explainer'),
            array($this, 'basic_settings_callback'),
            'explainer_settings'
        );
        
        add_settings_section(
            'explainer_advanced_settings',
            __('Advanced Settings', 'wp-ai-explainer'),
            array($this, 'advanced_settings_callback'),
            'explainer_settings'
        );
        
        // Add settings fields
        add_settings_field(
            'explainer_enabled',
            __('Enable Plugin', 'wp-ai-explainer'),
            array($this, 'enabled_field_callback'),
            'explainer_settings',
            'explainer_basic_settings'
        );
        
        add_settings_field(
            'explainer_language',
            __('Language', 'wp-ai-explainer'),
            array($this, 'language_field_callback'),
            'explainer_settings',
            'explainer_basic_settings'
        );
        
        add_settings_field(
            'explainer_api_key',
            __('OpenAI API Key', 'wp-ai-explainer'),
            array($this, 'api_key_field_callback'),
            'explainer_settings',
            'explainer_basic_settings'
        );
        
        add_settings_field(
            'explainer_api_model',
            __('AI Model', 'wp-ai-explainer'),
            array($this, 'api_model_field_callback'),
            'explainer_settings',
            'explainer_basic_settings'
        );
        
        add_settings_field(
            'explainer_custom_prompt',
            __('Custom Prompt Template', 'wp-ai-explainer'),
            array($this, 'custom_prompt_field_callback'),
            'explainer_settings',
            'explainer_basic_settings'
        );
        
        add_settings_field(
            'explainer_cache_enabled',
            __('Enable Cache', 'wp-ai-explainer'),
            array($this, 'cache_enabled_field_callback'),
            'explainer_settings',
            'explainer_advanced_settings'
        );
        
        add_settings_field(
            'explainer_cache_duration',
            __('Cache Duration (hours)', 'wp-ai-explainer'),
            array($this, 'cache_duration_field_callback'),
            'explainer_settings',
            'explainer_advanced_settings'
        );
        
        add_settings_field(
            'explainer_rate_limit_enabled',
            __('Enable Rate Limiting', 'wp-ai-explainer'),
            array($this, 'rate_limit_enabled_field_callback'),
            'explainer_settings',
            'explainer_advanced_settings'
        );
        
        add_settings_field(
            'explainer_rate_limit_logged',
            __('Rate Limit (logged in users)', 'wp-ai-explainer'),
            array($this, 'rate_limit_logged_field_callback'),
            'explainer_settings',
            'explainer_advanced_settings'
        );
        
        add_settings_field(
            'explainer_rate_limit_anonymous',
            __('Rate Limit (anonymous users)', 'wp-ai-explainer'),
            array($this, 'rate_limit_anonymous_field_callback'),
            'explainer_settings',
            'explainer_advanced_settings'
        );
        
        // Handle Ajax requests
        add_action('wp_ajax_explainer_test_api_key', array($this, 'test_api_key'));
        add_action('wp_ajax_explainer_clear_cache', array($this, 'clear_cache'));
        add_action('wp_ajax_explainer_get_cache_count', array($this, 'get_cache_count'));
        add_action('wp_ajax_explainer_reset_settings', array($this, 'reset_settings'));
        add_action('wp_ajax_explainer_view_debug_logs', array($this, 'view_debug_logs'));
        add_action('wp_ajax_explainer_delete_debug_logs', array($this, 'delete_debug_logs'));
    }
    
    /**
     * Settings page callback
     */
    public function settings_page() {
        include EXPLAINER_PLUGIN_PATH . 'templates/admin-settings.php';
    }
    
    /**
     * Basic settings section callback
     */
    public function basic_settings_callback() {
        echo '<p>' . esc_html__('Configure the basic settings for the AI Explainer plugin.', 'wp-ai-explainer') . '</p>';
    }
    
    /**
     * Advanced settings section callback
     */
    public function advanced_settings_callback() {
        echo '<p>' . esc_html__('Advanced configuration options for performance and rate limiting.', 'wp-ai-explainer') . '</p>';
    }
    
    /**
     * Enabled field callback
     */
    public function enabled_field_callback() {
        $value = get_option('explainer_enabled', true);
        ?>
        <label>
            <input type="checkbox" name="explainer_enabled" value="1" <?php checked($value, true); ?> />
            <?php echo esc_html__('Enable the AI Explainer plugin', 'wp-ai-explainer'); ?>
        </label>
        <?php
    }
    
    /**
     * Language field callback
     */
    public function language_field_callback() {
        $value = get_option('explainer_language', 'en_GB');
        $languages = array(
            'en_US' => __('English (United States)', 'wp-ai-explainer'),
            'en_GB' => __('English (United Kingdom)', 'wp-ai-explainer'),
            'es_ES' => __('Spanish (Spain)', 'wp-ai-explainer'),
            'de_DE' => __('German (Germany)', 'wp-ai-explainer'),
            'fr_FR' => __('French (France)', 'wp-ai-explainer'),
            'hi_IN' => __('Hindi (India)', 'wp-ai-explainer'),
            'zh_CN' => __('Chinese (Simplified)', 'wp-ai-explainer')
        );
        ?>
        <select name="explainer_language" class="regular-text">
            <?php foreach ($languages as $code => $name): ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($value, $code); ?>>
                    <?php echo esc_html($name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php echo esc_html__('Select the language for the plugin interface and AI explanations.', 'wp-ai-explainer'); ?>
        </p>
        <?php
    }
    
    /**
     * API key field callback
     */
    public function api_key_field_callback() {
        $value = get_option('explainer_api_key', '');
        ?>
        <input type="password" name="explainer_api_key" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description">
            <?php echo esc_html__('Enter your OpenAI API key. Get one from https://platform.openai.com/api-keys', 'wp-ai-explainer'); ?>
        </p>
        <?php
    }
    
    /**
     * API model field callback
     */
    public function api_model_field_callback() {
        $value = get_option('explainer_api_model', 'gpt-3.5-turbo');
        $models = array(
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Recommended)',
            'gpt-4' => 'GPT-4 (Higher quality, more expensive)',
            'gpt-4-turbo' => 'GPT-4 Turbo (Fast and efficient)'
        );
        ?>
        <select name="explainer_api_model">
            <?php foreach ($models as $model => $label): ?>
                <option value="<?php echo esc_attr($model); ?>" <?php selected($value, $model); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Cache enabled field callback
     */
    public function cache_enabled_field_callback() {
        $value = get_option('explainer_cache_enabled', true);
        ?>
        <label>
            <input type="checkbox" name="explainer_cache_enabled" value="1" <?php checked($value, true); ?> />
            <?php echo esc_html__('Enable caching to reduce API calls and costs', 'wp-ai-explainer'); ?>
        </label>
        <?php
    }
    
    /**
     * Cache duration field callback
     */
    public function cache_duration_field_callback() {
        $value = get_option('explainer_cache_duration', 24);
        ?>
        <input type="number" name="explainer_cache_duration" value="<?php echo esc_attr($value); ?>" min="1" max="168" />
        <p class="description">
            <?php echo esc_html__('How long to cache explanations (1-168 hours)', 'wp-ai-explainer'); ?>
        </p>
        <?php
    }
    
    /**
     * Rate limit enabled field callback
     */
    public function rate_limit_enabled_field_callback() {
        $value = get_option('explainer_rate_limit_enabled', true);
        ?>
        <label>
            <input type="checkbox" name="explainer_rate_limit_enabled" value="1" <?php checked($value, true); ?> />
            <?php echo esc_html__('Enable rate limiting to prevent abuse', 'wp-ai-explainer'); ?>
        </label>
        <?php
    }
    
    /**
     * Rate limit logged field callback
     */
    public function rate_limit_logged_field_callback() {
        $value = get_option('explainer_rate_limit_logged', 20);
        ?>
        <input type="number" name="explainer_rate_limit_logged" value="<?php echo esc_attr($value); ?>" min="1" max="100" />
        <p class="description">
            <?php echo esc_html__('Requests per minute for logged in users', 'wp-ai-explainer'); ?>
        </p>
        <?php
    }
    
    /**
     * Rate limit anonymous field callback
     */
    public function rate_limit_anonymous_field_callback() {
        $value = get_option('explainer_rate_limit_anonymous', 10);
        ?>
        <input type="number" name="explainer_rate_limit_anonymous" value="<?php echo esc_attr($value); ?>" min="1" max="50" />
        <p class="description">
            <?php echo esc_html__('Requests per minute for anonymous users', 'wp-ai-explainer'); ?>
        </p>
        <?php
    }
    
    /**
     * Test API key via Ajax
     */
    public function test_api_key() {
        check_ajax_referer('explainer_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-ai-explainer')));
        }
        
        // Get provider from request - API key should never be sent in request for security
        $provider = sanitize_text_field( wp_unslash( $_POST['provider'] ?? 'openai' ) );
        
        // Always use stored API key for security - never accept API key from request
        if ($provider === 'claude') {
            $api_key = $this->api_proxy->get_decrypted_api_key_for_provider('claude');
        } else {
            $api_key = $this->api_proxy->get_decrypted_api_key_for_provider('openai');
        }
        
        if (empty($api_key)) {
            $provider_name = $provider === 'claude' ? 'Claude' : 'OpenAI';
            // translators: %s is the name of the AI provider (OpenAI or Claude)
            wp_send_json_error(array('message' => sprintf(__('No %s API key configured. Please save an API key first, then test it.', 'wp-ai-explainer'), $provider_name)));
        }
        
        $result = $this->api_proxy->test_api_key($api_key);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Clear cache via Ajax
     */
    public function clear_cache() {
        check_ajax_referer('explainer_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-ai-explainer')));
        }
        
        $result = $this->api_proxy->clear_cache();
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Cache cleared successfully', 'wp-ai-explainer'),
                'count' => 0
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to clear cache', 'wp-ai-explainer')));
        }
    }
    
    /**
     * Get cache count via Ajax
     */
    public function get_cache_count() {
        check_ajax_referer('explainer_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-ai-explainer')));
        }
        
        $count = explainer_count_cached_items();
        
        wp_send_json_success(array('count' => $count));
    }
    
    /**
     * View debug logs via Ajax
     */
    public function view_debug_logs() {
        check_ajax_referer('explainer_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-ai-explainer')));
        }
        
        $logs = get_option('explainer_debug_logs', array());
        
        if (empty($logs)) {
            wp_send_json_success(array('logs' => array(), 'message' => __('No debug logs found.', 'wp-ai-explainer')));
        }
        
        // Get latest 100 logs
        $logs = array_slice($logs, -100);
        
        wp_send_json_success(array('logs' => $logs));
    }
    
    /**
     * Delete debug logs via Ajax
     */
    public function delete_debug_logs() {
        check_ajax_referer('explainer_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-ai-explainer')));
        }
        
        delete_option('explainer_debug_logs');
        
        wp_send_json_success(array('message' => __('Debug logs deleted successfully.', 'wp-ai-explainer')));
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_styles($hook) {
        if ($hook !== 'toplevel_page_explainer-settings') {
            return;
        }
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style(
            'explainer-admin',
            EXPLAINER_PLUGIN_URL . 'assets/css/admin.css',
            array('wp-color-picker'),
            EXPLAINER_PLUGIN_VERSION
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_explainer-settings') {
            return;
        }
        
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script(
            'explainer-admin',
            EXPLAINER_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            EXPLAINER_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('explainer-admin', 'explainerAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('explainer_admin_nonce')
        ));
    }
    
    /**
     * Reset settings to defaults
     */
    public function reset_settings() {
        check_ajax_referer('explainer_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'wp-ai-explainer')));
        }
        
        // Get all default options
        $defaults = array(
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
            'explainer_cache_duration' => 24,
            'explainer_rate_limit_enabled' => true,
            'explainer_rate_limit_logged' => 20,
            'explainer_rate_limit_anonymous' => 10,
            'explainer_included_selectors' => 'article, main, .content, .entry-content, .post-content',
            'explainer_excluded_selectors' => 'nav, header, footer, aside, .widget, #wpadminbar, .admin-bar',
            'explainer_tooltip_bg_color' => '#333333',
            'explainer_tooltip_text_color' => '#ffffff',
            'explainer_button_enabled_color' => '#46b450',
            'explainer_button_disabled_color' => '#666666',
            'explainer_button_text_color' => '#ffffff',
            'explainer_toggle_position' => 'bottom-right',
            'explainer_show_disclaimer' => true,
            'explainer_show_provider' => true,
            'explainer_tooltip_footer_color' => '#ffffff',
            'explainer_debug_mode' => false,
            'explainer_custom_prompt' => 'Please provide a clear, concise explanation of the following text in 1-2 sentences: {{snippet}}',
        );
        
        // Update all options to defaults
        foreach ($defaults as $option => $value) {
            update_option($option, $value);
        }
        
        wp_send_json_success(array('message' => __('Settings reset to defaults successfully', 'wp-ai-explainer')));
    }
    
    
    /**
     * Process API key before saving
     */
    public function process_api_key_save($value, $old_value) {
        if (!empty($value)) {
            // Encrypt the new API key before saving
            return $this->api_proxy->encrypt_api_key($value);
        }
        
        // If empty value submitted, keep the existing key (don't clear it)
        // This allows the secure form to have empty inputs while preserving existing keys
        return $old_value;
    }
    
    /**
     * Process Claude API key before saving
     */
    public function process_claude_api_key_save($value, $old_value) {
        if (!empty($value)) {
            // Encrypt the new Claude API key before saving
            return $this->api_proxy->encrypt_api_key($value);
        }
        
        // If empty value submitted, keep the existing key (don't clear it)
        // This allows the secure form to have empty inputs while preserving existing keys
        return $old_value;
    }
    
    /**
     * Custom prompt field callback
     */
    public function custom_prompt_field_callback() {
        $value = get_option('explainer_custom_prompt', 'Please provide a clear, concise explanation of the following text in 1-2 sentences: {{snippet}}');
        ?>
        <textarea name="explainer_custom_prompt" rows="4" cols="60" class="large-text code"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php 
            // translators: {{snippet}} is a placeholder that will be replaced with the user's selected text
            echo esc_html__('Customize the prompt sent to the AI. Use {{snippet}} where you want the selected text to appear. Maximum 500 characters.', 'wp-ai-explainer'); ?>
        </p>
        <p class="description">
            <strong><?php echo esc_html__('Example:', 'wp-ai-explainer'); ?></strong> <?php 
            // translators: {{snippet}} is a placeholder that will be replaced with the user's selected text
            echo esc_html__('\"Explain this text in simple terms for a beginner: {{snippet}}\"', 'wp-ai-explainer'); ?>
        </p>
        <button type="button" class="button" id="reset-prompt-default"><?php echo esc_html__('Reset to Default', 'wp-ai-explainer'); ?></button>
        <?php
    }
    
    /**
     * Process custom prompt before saving
     */
    public function process_custom_prompt_save($value, $old_value) {
        // Verify nonce for security (WordPress handles this automatically for settings)
        if (!current_user_can('manage_options')) {
            return $old_value;
        }
        
        // Sanitize as plain text only
        $sanitized = sanitize_textarea_field($value);
        
        // Set default if empty
        if (empty($sanitized)) {
            $sanitized = 'Please provide a clear, concise explanation of the following text in 1-2 sentences: {{snippet}}';
        }
        
        // Check for required {{snippet}} variable
        if (!str_contains($sanitized, '{{snippet}}')) {
            add_settings_error(
                'explainer_custom_prompt',
                'missing_snippet_variable',
                // translators: {{snippet}} is a placeholder that will be replaced with the user's selected text
                __('Custom prompt must contain {{snippet}} placeholder.', 'wp-ai-explainer')
            );
            return $old_value; // Return old value if validation fails
        }
        
        // Check length limit
        if (strlen($sanitized) > 500) {
            add_settings_error(
                'explainer_custom_prompt',
                'prompt_too_long',
                __('Custom prompt cannot exceed 500 characters.', 'wp-ai-explainer')
            );
            return $old_value; // Return old value if validation fails
        }
        
        return $sanitized;
    }
    
    /**
     * Validate custom prompt
     */
    public function validate_custom_prompt($value) {
        // Sanitize as plain text only
        $sanitized = sanitize_textarea_field($value);
        
        // Set default if empty
        if (empty($sanitized)) {
            $sanitized = 'Please provide a clear, concise explanation of the following text in 1-2 sentences: {{snippet}}';
        }
        
        // Check for required {{snippet}} variable
        if (!str_contains($sanitized, '{{snippet}}')) {
            // Return default if validation fails
            return 'Please provide a clear, concise explanation of the following text in 1-2 sentences: {{snippet}}';
        }
        
        // Check length limit
        if (strlen($sanitized) > 500) {
            // Return default if validation fails
            return 'Please provide a clear, concise explanation of the following text in 1-2 sentences: {{snippet}}';
        }
        
        return $sanitized;
    }
    
    /**
     * Validate API provider
     */
    public function validate_api_provider($value) {
        $valid_providers = array('openai', 'claude');
        
        if (!in_array($value, $valid_providers)) {
            return 'openai'; // Default to OpenAI if invalid
        }
        
        return $value;
    }
    
    /**
     * Validate language setting
     */
    public function validate_language($value) {
        $valid_languages = array('en_US', 'en_GB', 'es_ES', 'de_DE', 'fr_FR', 'hi_IN', 'zh_CN');
        
        if (!in_array($value, $valid_languages)) {
            return 'en_GB'; // Default to British English if invalid
        }
        
        return $value;
    }
    
    /**
     * Validate maximum selection length
     */
    public function validate_max_selection_length($value) {
        $value = absint($value);
        
        if ($value < 50 || $value > 1000) {
            return 200; // Default value
        }
        
        return $value;
    }
    
    /**
     * Validate minimum selection length
     */
    public function validate_min_selection_length($value) {
        $value = absint($value);
        
        if ($value < 1 || $value > 50) {
            return 3; // Default value
        }
        
        return $value;
    }
    
    /**
     * Validate maximum words
     */
    public function validate_max_words($value) {
        $value = absint($value);
        
        if ($value < 5 || $value > 100) {
            return 30; // Default value
        }
        
        return $value;
    }
    
    /**
     * Validate minimum words
     */
    public function validate_min_words($value) {
        $value = absint($value);
        
        if ($value < 1 || $value > 10) {
            return 1; // Default value
        }
        
        return $value;
    }
    
    /**
     * Validate cache duration
     */
    public function validate_cache_duration($value) {
        $value = absint($value);
        
        if ($value < 1 || $value > 168) {
            return 24; // Default value
        }
        
        return $value;
    }
    
    /**
     * Validate rate limit for logged users
     */
    public function validate_rate_limit_logged($value) {
        $value = absint($value);
        
        if ($value < 1 || $value > 100) {
            return 20; // Default value
        }
        
        return $value;
    }
    
    /**
     * Validate rate limit for anonymous users
     */
    public function validate_rate_limit_anonymous($value) {
        $value = absint($value);
        
        if ($value < 1 || $value > 50) {
            return 10; // Default value
        }
        
        return $value;
    }
    
    /**
     * Validate toggle position
     */
    public function validate_toggle_position($value) {
        $valid_positions = array('bottom-right', 'bottom-left', 'top-right', 'top-left');
        
        if (!in_array($value, $valid_positions)) {
            return 'bottom-right'; // Default position
        }
        
        return $value;
    }
    
    /**
     * Display usage exceeded admin notice
     */
    public function display_usage_exceeded_notice() {
        // Check if we should show the notice
        if (!explainer_should_show_usage_notice()) {
            return;
        }
        
        // Get disable information
        $stats = explainer_get_usage_exceeded_stats();
        $reason = $stats['reason'];
        $provider = $stats['provider'];
        $time_since = $stats['time_since'];
        
        // Create the notice
        $notice_class = 'notice notice-error is-dismissible explainer-usage-notice';
        $notice_id = 'explainer-usage-exceeded-notice';
        
        ?>
        <div id="<?php echo esc_attr($notice_id); ?>" class="<?php echo esc_attr($notice_class); ?>">
            <div class="explainer-notice-content">
                <h3><?php esc_html_e('WP AI Explainer Automatically Disabled', 'wp-ai-explainer'); ?></h3>
                <p><strong><?php esc_html_e('The plugin has been automatically disabled due to API usage limits being exceeded.', 'wp-ai-explainer'); ?></strong></p>
                
                <?php if (!empty($reason)): ?>
                    <p><strong><?php esc_html_e('Reason:', 'wp-ai-explainer'); ?></strong> <?php echo esc_html($reason); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($provider)): ?>
                    <p><strong><?php esc_html_e('Provider:', 'wp-ai-explainer'); ?></strong> <?php echo esc_html($provider); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($time_since)): ?>
                    <p><strong><?php esc_html_e('Disabled:', 'wp-ai-explainer'); ?></strong> <?php echo esc_html($time_since); ?></p>
                <?php endif; ?>
                
                <p><?php esc_html_e('Please check your AI provider account billing and usage limits. Once resolved, you can manually re-enable the plugin below.', 'wp-ai-explainer'); ?></p>
                
                <div class="explainer-notice-actions">
                    <button type="button" class="button button-primary explainer-reenable-btn" data-nonce="<?php echo esc_attr(wp_create_nonce('explainer_reenable_plugin')); ?>">
                        <?php esc_html_e('Re-enable Plugin', 'wp-ai-explainer'); ?>
                    </button>
                    <button type="button" class="button explainer-dismiss-notice-btn" data-nonce="<?php echo esc_attr(wp_create_nonce('explainer_dismiss_notice')); ?>">
                        <?php esc_html_e('Dismiss Notice (Keep Plugin Disabled)', 'wp-ai-explainer'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('options-general.php?page=explainer-settings')); ?>" class="button">
                        <?php esc_html_e('Go to Plugin Settings', 'wp-ai-explainer'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Handle re-enable button
            $('.explainer-reenable-btn').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var nonce = button.data('nonce');
                var originalText = button.text();
                
                if (!confirm('<?php echo esc_js(__('Are you sure you want to re-enable the AI Explainer plugin? Make sure you have resolved the usage limit issues first.', 'wp-ai-explainer')); ?>')) {
                    return;
                }
                
                button.prop('disabled', true).text('<?php echo esc_js(__('Re-enabling...', 'wp-ai-explainer')); ?>');
                
                $.post(ajaxurl, {
                    action: 'explainer_reenable_plugin',
                    nonce: nonce
                })
                .done(function(response) {
                    if (response.success) {
                        $('#explainer-usage-exceeded-notice').fadeOut(function() {
                            $(this).remove();
                        });
                        // Show success message
                        $('body').prepend('<div class="notice notice-success is-dismissible"><p><?php echo esc_js(__('Plugin has been successfully re-enabled.', 'wp-ai-explainer')); ?></p></div>');
                        // Reload page after a short delay to reflect enabled state
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        alert('<?php echo esc_js(__('Error re-enabling plugin:', 'wp-ai-explainer')); ?> ' + (response.data.message || '<?php echo esc_js(__('Unknown error', 'wp-ai-explainer')); ?>'));
                        button.prop('disabled', false).text(originalText);
                    }
                })
                .fail(function() {
                    alert('<?php echo esc_js(__('Failed to re-enable plugin. Please try again.', 'wp-ai-explainer')); ?>');
                    button.prop('disabled', false).text(originalText);
                });
            });
            
            // Handle dismiss notice button
            $('.explainer-dismiss-notice-btn').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var nonce = button.data('nonce');
                var originalText = button.text();
                
                button.prop('disabled', true).text('<?php echo esc_js(__('Dismissing...', 'wp-ai-explainer')); ?>');
                
                $.post(ajaxurl, {
                    action: 'explainer_dismiss_usage_notice',
                    nonce: nonce
                })
                .done(function(response) {
                    if (response.success) {
                        $('#explainer-usage-exceeded-notice').fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        alert('<?php echo esc_js(__('Error dismissing notice:', 'wp-ai-explainer')); ?> ' + (response.data.message || '<?php echo esc_js(__('Unknown error', 'wp-ai-explainer')); ?>'));
                        button.prop('disabled', false).text(originalText);
                    }
                })
                .fail(function() {
                    alert('<?php echo esc_js(__('Failed to dismiss notice. Please try again.', 'wp-ai-explainer')); ?>');
                    button.prop('disabled', false).text(originalText);
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Handle AJAX request to re-enable the plugin
     */
    public function handle_reenable_plugin() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'explainer_reenable_plugin' ) ) {
            wp_send_json_error(array('message' => __('Invalid nonce.', 'wp-ai-explainer')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'wp-ai-explainer')));
        }
        
        // Check if plugin is actually auto-disabled
        if (!explainer_is_auto_disabled()) {
            wp_send_json_error(array('message' => __('Plugin is not currently auto-disabled.', 'wp-ai-explainer')));
        }
        
        // Re-enable the plugin
        $success = explainer_reenable_plugin();
        
        if ($success) {
            wp_send_json_success(array(
                'message' => __('Plugin has been successfully re-enabled.', 'wp-ai-explainer')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to re-enable plugin.', 'wp-ai-explainer')
            ));
        }
    }
    
    /**
     * Handle AJAX request to dismiss usage exceeded notice
     */
    public function handle_dismiss_usage_notice() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'explainer_dismiss_notice' ) ) {
            wp_send_json_error(array('message' => __('Invalid nonce.', 'wp-ai-explainer')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'wp-ai-explainer')));
        }
        
        // Dismiss the notice
        $success = explainer_dismiss_usage_notice();
        
        if ($success) {
            wp_send_json_success(array(
                'message' => __('Notice dismissed successfully.', 'wp-ai-explainer')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to dismiss notice.', 'wp-ai-explainer')
            ));
        }
    }
    
    /**
     * Sanitize blocked words list
     * 
     * @param string $value The textarea value with blocked words
     * @return string Sanitized blocked words list
     */
    public function sanitize_blocked_words($value) {
        if (empty($value)) {
            return '';
        }
        
        // Split by newlines
        $words = explode("\n", $value);
        $sanitized_words = array();
        
        foreach ($words as $word) {
            // Trim whitespace
            $word = trim($word);
            
            // Skip empty lines
            if (empty($word)) {
                continue;
            }
            
            // Sanitize the word (allow letters, numbers, spaces, hyphens, and common punctuation)
            $word = preg_replace('/[^a-zA-Z0-9\s\-_.,!?\'"]/u', '', $word);
            
            // Limit word length to prevent abuse
            if (strlen($word) > 100) {
                $word = substr($word, 0, 100);
            }
            
            // Add to sanitized list
            if (!empty($word)) {
                $sanitized_words[] = $word;
            }
        }
        
        // Limit total number of blocked words to 500
        $sanitized_words = array_slice($sanitized_words, 0, 500);
        
        // Join back with newlines
        return implode("\n", $sanitized_words);
    }
}