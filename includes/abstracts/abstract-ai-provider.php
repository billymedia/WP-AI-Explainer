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
                'message' => __('API key is required.', 'ai-explainer')
            );
        }
        
        if (!$this->validate_api_key($api_key)) {
            return array(
                'success' => false,
                'message' => __('Invalid API key format.', 'ai-explainer')
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
                'message' => __('Connection failed. Please check your internet connection.', 'ai-explainer')
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 401) {
            return array(
                'success' => false,
                // translators: %s is the name of the AI provider (OpenAI, Claude, etc.)
                'message' => sprintf(__('Invalid API key. Please check your %s API key.', 'ai-explainer'), $this->get_name())
            );
        }
        
        if ($response_code === 429) {
            return array(
                'success' => false,
                'message' => __('Rate limit exceeded. Please try again later.', 'ai-explainer')
            );
        }
        
        if ($response_code !== 200) {
            return array(
                'success' => false,
                // translators: %d is the HTTP status code from the API response
                'message' => sprintf(__('API error (HTTP %d). Please try again.', 'ai-explainer'), $response_code)
            );
        }
        
        return array(
            'success' => true,
            // translators: %s is the name of the AI provider (OpenAI, Claude, etc.)
            'message' => sprintf(__('%s API key is valid and working.', 'ai-explainer'), $this->get_name())
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
                'error' => __('API request failed. Please try again.', 'ai-explainer')
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        // Check for quota/billing issues first
        $quota_error = $this->check_quota_exceeded($response);
        if ($quota_error) {
            return $quota_error;
        }
        
        if ($response_code !== 200) {
            return array(
                'success' => false,
                'error' => __('Explanation temporarily unavailable. Please try again later.', 'ai-explainer')
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'error' => __('Invalid API response format.', 'ai-explainer')
            );
        }
        
        return null; // No common errors found
    }
    
    /**
     * Check if the API response indicates quota/billing exceeded
     * 
     * @param array $response WordPress HTTP response
     * @return array|null Error array with disable_plugin flag or null if no quota error
     */
    protected function check_quota_exceeded($response) {
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Parse response body for error details
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = array();
        }
        
        // Check for provider-specific quota errors
        if ($this->is_quota_exceeded_error($response_code, $data)) {
            return array(
                'success' => false,
                'error' => $this->get_quota_exceeded_message($data),
                'disable_plugin' => true,
                'error_type' => 'quota_exceeded'
            );
        }
        
        return null;
    }
    
    /**
     * Check if response indicates quota exceeded (provider-specific implementation)
     * 
     * @param int $response_code HTTP response code
     * @param array $data Parsed response data
     * @return bool True if quota exceeded
     */
    protected function is_quota_exceeded_error($response_code, $data) {
        // Default implementation - providers should override this
        return false;
    }
    
    /**
     * Get user-friendly message for quota exceeded error
     * 
     * @param array $data Parsed response data
     * @return string User-friendly error message
     */
    protected function get_quota_exceeded_message($data) {
        return sprintf(
            // translators: First %1$s is the AI provider name, second %2$s is the same provider name for the account reference
            __('API usage limit exceeded for %1$s. The plugin has been automatically disabled to prevent further charges. Please check your %2$s account billing and usage limits, then manually re-enable the plugin when ready.', 'ai-explainer'),
            $this->get_name(),
            $this->get_name()
        );
    }
}