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
        add_options_page(
            __('AI Explainer Settings', 'explainer-plugin'),
            __('Explainer Settings', 'explainer-plugin'),
            'manage_options',
            'explainer-settings',
            array($this, 'settings_page')
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
        
        // Add settings sections
        add_settings_section(
            'explainer_basic_settings',
            __('Basic Settings', 'explainer-plugin'),
            array($this, 'basic_settings_callback'),
            'explainer_settings'
        );
        
        add_settings_section(
            'explainer_advanced_settings',
            __('Advanced Settings', 'explainer-plugin'),
            array($this, 'advanced_settings_callback'),
            'explainer_settings'
        );
        
        // Add settings fields
        add_settings_field(
            'explainer_enabled',
            __('Enable Plugin', 'explainer-plugin'),
            array($this, 'enabled_field_callback'),
            'explainer_settings',
            'explainer_basic_settings'
        );
        
        add_settings_field(
            'explainer_language',
            __('Language', 'explainer-plugin'),
            array($this, 'language_field_callback'),
            'explainer_settings',
            'explainer_basic_settings'
        );
        
        add_settings_field(
            'explainer_api_key',
            __('OpenAI API Key', 'explainer-plugin'),
            array($this, 'api_key_field_callback'),
            'explainer_settings',
            'explainer_basic_settings'
        );
        
        add_settings_field(
            'explainer_api_model',
            __('AI Model', 'explainer-plugin'),
            array($this, 'api_model_field_callback'),
            'explainer_settings',
            'explainer_basic_settings'
        );
        
        add_settings_field(
            'explainer_custom_prompt',
            __('Custom Prompt Template', 'explainer-plugin'),
            array($this, 'custom_prompt_field_callback'),
            'explainer_settings',
            'explainer_basic_settings'
        );
        
        add_settings_field(
            'explainer_cache_enabled',
            __('Enable Cache', 'explainer-plugin'),
            array($this, 'cache_enabled_field_callback'),
            'explainer_settings',
            'explainer_advanced_settings'
        );
        
        add_settings_field(
            'explainer_cache_duration',
            __('Cache Duration (hours)', 'explainer-plugin'),
            array($this, 'cache_duration_field_callback'),
            'explainer_settings',
            'explainer_advanced_settings'
        );
        
        add_settings_field(
            'explainer_rate_limit_enabled',
            __('Enable Rate Limiting', 'explainer-plugin'),
            array($this, 'rate_limit_enabled_field_callback'),
            'explainer_settings',
            'explainer_advanced_settings'
        );
        
        add_settings_field(
            'explainer_rate_limit_logged',
            __('Rate Limit (logged in users)', 'explainer-plugin'),
            array($this, 'rate_limit_logged_field_callback'),
            'explainer_settings',
            'explainer_advanced_settings'
        );
        
        add_settings_field(
            'explainer_rate_limit_anonymous',
            __('Rate Limit (anonymous users)', 'explainer-plugin'),
            array($this, 'rate_limit_anonymous_field_callback'),
            'explainer_settings',
            'explainer_advanced_settings'
        );
        
        // Handle Ajax requests
        add_action('wp_ajax_explainer_test_api_key', array($this, 'test_api_key'));
        add_action('wp_ajax_explainer_clear_cache', array($this, 'clear_cache'));
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
        echo '<p>' . esc_html__('Configure the basic settings for the AI Explainer plugin.', 'explainer-plugin') . '</p>';
    }
    
    /**
     * Advanced settings section callback
     */
    public function advanced_settings_callback() {
        echo '<p>' . esc_html__('Advanced configuration options for performance and rate limiting.', 'explainer-plugin') . '</p>';
    }
    
    /**
     * Enabled field callback
     */
    public function enabled_field_callback() {
        $value = get_option('explainer_enabled', true);
        ?>
        <label>
            <input type="checkbox" name="explainer_enabled" value="1" <?php checked($value, true); ?> />
            <?php echo esc_html__('Enable the AI Explainer plugin', 'explainer-plugin'); ?>
        </label>
        <?php
    }
    
    /**
     * Language field callback
     */
    public function language_field_callback() {
        $value = get_option('explainer_language', 'en_GB');
        $languages = array(
            'en_US' => __('English (United States)', 'explainer-plugin'),
            'en_GB' => __('English (United Kingdom)', 'explainer-plugin'),
            'es_ES' => __('Spanish (Spain)', 'explainer-plugin'),
            'de_DE' => __('German (Germany)', 'explainer-plugin'),
            'fr_FR' => __('French (France)', 'explainer-plugin'),
            'hi_IN' => __('Hindi (India)', 'explainer-plugin'),
            'zh_CN' => __('Chinese (Simplified)', 'explainer-plugin')
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
            <?php echo esc_html__('Select the language for the plugin interface and AI explanations.', 'explainer-plugin'); ?>
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
            <?php echo esc_html__('Enter your OpenAI API key. Get one from https://platform.openai.com/api-keys', 'explainer-plugin'); ?>
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
            <?php echo esc_html__('Enable caching to reduce API calls and costs', 'explainer-plugin'); ?>
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
            <?php echo esc_html__('How long to cache explanations (1-168 hours)', 'explainer-plugin'); ?>
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
            <?php echo esc_html__('Enable rate limiting to prevent abuse', 'explainer-plugin'); ?>
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
            <?php echo esc_html__('Requests per minute for logged in users', 'explainer-plugin'); ?>
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
            <?php echo esc_html__('Requests per minute for anonymous users', 'explainer-plugin'); ?>
        </p>
        <?php
    }
    
    /**
     * Test API key via Ajax
     */
    public function test_api_key() {
        check_ajax_referer('explainer_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'explainer-plugin')));
        }
        
        // Get API key and provider from request
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        $provider = sanitize_text_field($_POST['provider'] ?? 'openai');
        
        // Fallback to saved API key if not provided in request
        if (empty($api_key)) {
            if ($provider === 'claude') {
                $api_key = $this->api_proxy->get_decrypted_api_key_for_provider('claude');
            } else {
                $api_key = $this->api_proxy->get_decrypted_api_key_for_provider('openai');
            }
        }
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => __('Please enter an API key to test', 'explainer-plugin')));
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
            wp_send_json_error(array('message' => __('Permission denied', 'explainer-plugin')));
        }
        
        $result = $this->api_proxy->clear_cache();
        
        if ($result) {
            wp_send_json_success(array('message' => __('Cache cleared successfully', 'explainer-plugin')));
        } else {
            wp_send_json_error(array('message' => __('Failed to clear cache', 'explainer-plugin')));
        }
    }
    
    /**
     * View debug logs via Ajax
     */
    public function view_debug_logs() {
        check_ajax_referer('explainer_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'explainer-plugin')));
        }
        
        $logs = get_option('explainer_debug_logs', array());
        
        if (empty($logs)) {
            wp_send_json_success(array('logs' => array(), 'message' => __('No debug logs found.', 'explainer-plugin')));
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
            wp_send_json_error(array('message' => __('Permission denied', 'explainer-plugin')));
        }
        
        delete_option('explainer_debug_logs');
        
        wp_send_json_success(array('message' => __('Debug logs deleted successfully.', 'explainer-plugin')));
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_styles($hook) {
        if ($hook !== 'settings_page_explainer-settings') {
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
        if ($hook !== 'settings_page_explainer-settings') {
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
            wp_send_json_error(array('message' => __('Permission denied', 'explainer-plugin')));
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
        
        wp_send_json_success(array('message' => __('Settings reset to defaults successfully', 'explainer-plugin')));
    }
    
    
    /**
     * Process API key before saving
     */
    public function process_api_key_save($value, $old_value) {
        if (!empty($value)) {
            // Encrypt the API key before saving
            return $this->api_proxy->encrypt_api_key($value);
        }
        return $value;
    }
    
    /**
     * Process Claude API key before saving
     */
    public function process_claude_api_key_save($value, $old_value) {
        if (!empty($value)) {
            // Encrypt the Claude API key before saving
            return $this->api_proxy->encrypt_api_key($value);
        }
        return $value;
    }
    
    /**
     * Custom prompt field callback
     */
    public function custom_prompt_field_callback() {
        $value = get_option('explainer_custom_prompt', 'Please provide a clear, concise explanation of the following text in 1-2 sentences: {{snippet}}');
        ?>
        <textarea name="explainer_custom_prompt" rows="4" cols="60" class="large-text code"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php echo esc_html__('Customize the prompt sent to the AI. Use {{snippet}} where you want the selected text to appear. Maximum 500 characters.', 'explainer-plugin'); ?>
        </p>
        <p class="description">
            <strong><?php echo esc_html__('Example:', 'explainer-plugin'); ?></strong> <?php echo esc_html__('\"Explain this text in simple terms for a beginner: {{snippet}}\"', 'explainer-plugin'); ?>
        </p>
        <button type="button" class="button" id="reset-prompt-default"><?php echo esc_html__('Reset to Default', 'explainer-plugin'); ?></button>
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
        if (strpos($sanitized, '{{snippet}}') === false) {
            add_settings_error(
                'explainer_custom_prompt',
                'missing_snippet_variable',
                __('Custom prompt must contain {{snippet}} placeholder.', 'explainer-plugin')
            );
            return $old_value; // Return old value if validation fails
        }
        
        // Check length limit
        if (strlen($sanitized) > 500) {
            add_settings_error(
                'explainer_custom_prompt',
                'prompt_too_long',
                __('Custom prompt cannot exceed 500 characters.', 'explainer-plugin')
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
        if (strpos($sanitized, '{{snippet}}') === false) {
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
                <h3><?php _e('AI Explainer Plugin Automatically Disabled', 'explainer-plugin'); ?></h3>
                <p><strong><?php _e('The plugin has been automatically disabled due to API usage limits being exceeded.', 'explainer-plugin'); ?></strong></p>
                
                <?php if (!empty($reason)): ?>
                    <p><strong><?php _e('Reason:', 'explainer-plugin'); ?></strong> <?php echo esc_html($reason); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($provider)): ?>
                    <p><strong><?php _e('Provider:', 'explainer-plugin'); ?></strong> <?php echo esc_html($provider); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($time_since)): ?>
                    <p><strong><?php _e('Disabled:', 'explainer-plugin'); ?></strong> <?php echo esc_html($time_since); ?></p>
                <?php endif; ?>
                
                <p><?php _e('Please check your AI provider account billing and usage limits. Once resolved, you can manually re-enable the plugin below.', 'explainer-plugin'); ?></p>
                
                <div class="explainer-notice-actions">
                    <button type="button" class="button button-primary explainer-reenable-btn" data-nonce="<?php echo wp_create_nonce('explainer_reenable_plugin'); ?>">
                        <?php _e('Re-enable Plugin', 'explainer-plugin'); ?>
                    </button>
                    <button type="button" class="button explainer-dismiss-notice-btn" data-nonce="<?php echo wp_create_nonce('explainer_dismiss_notice'); ?>">
                        <?php _e('Dismiss Notice (Keep Plugin Disabled)', 'explainer-plugin'); ?>
                    </button>
                    <a href="<?php echo admin_url('options-general.php?page=explainer-settings'); ?>" class="button">
                        <?php _e('Go to Plugin Settings', 'explainer-plugin'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <style>
        .explainer-usage-notice {
            border-left-color: #dc3232 !important;
            background-color: #fff2f2;
        }
        .explainer-notice-content h3 {
            margin: 0 0 10px 0;
            color: #dc3232;
        }
        .explainer-notice-content p {
            margin: 8px 0;
        }
        .explainer-notice-actions {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        .explainer-notice-actions .button {
            margin-right: 10px;
            margin-bottom: 5px;
        }
        .explainer-reenable-btn:disabled,
        .explainer-dismiss-notice-btn:disabled {
            opacity: 0.6;
            pointer-events: none;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Handle re-enable button
            $('.explainer-reenable-btn').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var nonce = button.data('nonce');
                var originalText = button.text();
                
                if (!confirm('<?php echo esc_js(__('Are you sure you want to re-enable the AI Explainer plugin? Make sure you have resolved the usage limit issues first.', 'explainer-plugin')); ?>')) {
                    return;
                }
                
                button.prop('disabled', true).text('<?php echo esc_js(__('Re-enabling...', 'explainer-plugin')); ?>');
                
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
                        $('body').prepend('<div class="notice notice-success is-dismissible"><p><?php echo esc_js(__('Plugin has been successfully re-enabled.', 'explainer-plugin')); ?></p></div>');
                        // Reload page after a short delay to reflect enabled state
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        alert('<?php echo esc_js(__('Error re-enabling plugin:', 'explainer-plugin')); ?> ' + (response.data.message || '<?php echo esc_js(__('Unknown error', 'explainer-plugin')); ?>'));
                        button.prop('disabled', false).text(originalText);
                    }
                })
                .fail(function() {
                    alert('<?php echo esc_js(__('Failed to re-enable plugin. Please try again.', 'explainer-plugin')); ?>');
                    button.prop('disabled', false).text(originalText);
                });
            });
            
            // Handle dismiss notice button
            $('.explainer-dismiss-notice-btn').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var nonce = button.data('nonce');
                var originalText = button.text();
                
                button.prop('disabled', true).text('<?php echo esc_js(__('Dismissing...', 'explainer-plugin')); ?>');
                
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
                        alert('<?php echo esc_js(__('Error dismissing notice:', 'explainer-plugin')); ?> ' + (response.data.message || '<?php echo esc_js(__('Unknown error', 'explainer-plugin')); ?>'));
                        button.prop('disabled', false).text(originalText);
                    }
                })
                .fail(function() {
                    alert('<?php echo esc_js(__('Failed to dismiss notice. Please try again.', 'explainer-plugin')); ?>');
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
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'explainer_reenable_plugin')) {
            wp_send_json_error(array('message' => __('Invalid nonce.', 'explainer-plugin')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'explainer-plugin')));
        }
        
        // Check if plugin is actually auto-disabled
        if (!explainer_is_auto_disabled()) {
            wp_send_json_error(array('message' => __('Plugin is not currently auto-disabled.', 'explainer-plugin')));
        }
        
        // Re-enable the plugin
        $success = explainer_reenable_plugin();
        
        if ($success) {
            wp_send_json_success(array(
                'message' => __('Plugin has been successfully re-enabled.', 'explainer-plugin')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to re-enable plugin.', 'explainer-plugin')
            ));
        }
    }
    
    /**
     * Handle AJAX request to dismiss usage exceeded notice
     */
    public function handle_dismiss_usage_notice() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'explainer_dismiss_notice')) {
            wp_send_json_error(array('message' => __('Invalid nonce.', 'explainer-plugin')));
        }
        
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'explainer-plugin')));
        }
        
        // Dismiss the notice
        $success = explainer_dismiss_usage_notice();
        
        if ($success) {
            wp_send_json_success(array(
                'message' => __('Notice dismissed successfully.', 'explainer-plugin')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to dismiss notice.', 'explainer-plugin')
            ));
        }
    }
}