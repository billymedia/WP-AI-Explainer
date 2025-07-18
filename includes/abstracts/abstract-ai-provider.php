<?php
/**
 * Abstract AI Provider Base Class
 * 
 * Provides common functionality for all AI providers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract base class for AI providers
 */
abstract class ExplainerPlugin_Abstract_AI_Provider implements ExplainerPlugin_AI_Provider_Interface {
    
    /**
     * Provider configuration
     * 
     * @var array
     */
    protected $config = array();
    
    /**
     * Constructor
     * 
     * @param array $config Provider configuration
     */
    public function __construct($config = array()) {
        $this->config = wp_parse_args($config, $this->get_default_config());
    }
    
    /**
     * Get default configuration
     * 
     * @return array Default configuration
     */
    protected function get_default_config() {
        return array(
            'max_tokens' => 150,
            'timeout' => 10,
            'temperature' => 0.7,
            'user_agent' => 'WordPress/ExplainerPlugin/1.0'
        );
    }
    
    /**
     * Get configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Configuration value
     */
    protected function get_config($key, $default = null) {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
    
    /**
     * Make HTTP request to API
     * 
     * @param string $api_key API key
     * @param string $prompt Prompt text
     * @param string $model Model to use
     * @param array $options Additional options
     * @return array WordPress HTTP response
     */
    public function make_request($api_key, $prompt, $model, $options = array()) {
        $headers = $this->get_request_headers($api_key);
        $body = $this->prepare_request_body($prompt, $model, $options);
        
        $args = array(
            'timeout' => $this->get_timeout(),
            'headers' => $headers,
            'body' => json_encode($body),
            'sslverify' => true,
            'user-agent' => $this->get_config('user_agent')
        );
        
        return wp_remote_post($this->get_api_endpoint(), $args);
    }
    
    /**
     * Test API key validity
     * 
     * @param string $api_key API key to test
     * @return array Test result with success and message
     */
    public function test_api_key($api_key) {
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => __('API key is required.', 'explainer-plugin')
            );
        }
        
        if (!$this->validate_api_key($api_key)) {
            return array(
                'success' => false,
                'message' => __('Invalid API key format.', 'explainer-plugin')
            );
        }
        
        return $this->perform_test_request($api_key);
    }
    
    /**
     * Perform actual test request
     * 
     * @param string $api_key API key
     * @return array Test result
     */
    protected function perform_test_request($api_key) {
        $test_prompt = $this->get_test_prompt();
        $models = $this->get_models();
        $test_model = key($models); // Use first available model
        
        $response = $this->make_request($api_key, $test_prompt, $test_model);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => __('Connection failed. Please check your internet connection.', 'explainer-plugin')
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 401) {
            return array(
                'success' => false,
                'message' => sprintf(__('Invalid API key. Please check your %s API key.', 'explainer-plugin'), $this->get_name())
            );
        }
        
        if ($response_code === 429) {
            return array(
                'success' => false,
                'message' => __('Rate limit exceeded. Please try again later.', 'explainer-plugin')
            );
        }
        
        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => sprintf(__('API error (HTTP %d). Please try again.', 'explainer-plugin'), $response_code)
            );
        }
        
        return array(
            'success' => true,
            'message' => sprintf(__('%s API key is valid and working.', 'explainer-plugin'), $this->get_name())
        );
    }
    
    /**
     * Get test prompt for API key validation
     * 
     * @return string Test prompt
     */
    protected function get_test_prompt() {
        return 'Say "API key is working" if you can read this.';
    }
    
    /**
     * Get maximum tokens (default implementation)
     * 
     * @return int Maximum tokens
     */
    public function get_max_tokens() {
        return $this->get_config('max_tokens', 150);
    }
    
    /**
     * Get request timeout (default implementation)
     * 
     * @return int Timeout in seconds
     */
    public function get_timeout() {
        return $this->get_config('timeout', 10);
    }
    
    /**
     * Common request headers
     * 
     * @return array Common headers
     */
    protected function get_common_headers() {
        return array(
            'Content-Type' => 'application/json',
            'User-Agent' => $this->get_config('user_agent')
        );
    }
    
    /**
     * Handle common API errors
     * 
     * @param array $response WordPress HTTP response
     * @return array|null Error array or null if no error
     */
    protected function handle_common_errors($response) {
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => __('API request failed. Please try again.', 'explainer-plugin')
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return array(
                'success' => false,
                'error' => __('Explanation temporarily unavailable. Please try again later.', 'explainer-plugin')
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'error' => __('Invalid API response format.', 'explainer-plugin')
            );
        }
        
        return null; // No common errors found
    }
}