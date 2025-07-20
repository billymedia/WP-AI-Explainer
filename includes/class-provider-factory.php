<?php
/**
 * AI Provider Factory
 * 
 * Creates and manages AI provider instances
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Factory class for AI providers
 */
class ExplainerPlugin_Provider_Factory {
    
    /**
     * Provider instances cache
     * 
     * @var array
     */
    private static $providers = array();
    
    /**
     * Available providers
     * 
     * @var array
     */
    private static $available_providers = array(
        'openai' => 'ExplainerPlugin_OpenAI_Provider',
        'claude' => 'ExplainerPlugin_Claude_Provider'
    );
    
    /**
     * Get provider instance
     * 
     * @param string $provider_key Provider key (openai, claude)
     * @param array $config Provider configuration
     * @return ExplainerPlugin_AI_Provider_Interface|null Provider instance or null if not found
     */
    public static function get_provider($provider_key, $config = array()) {
        // Check if provider is available
        if (!isset(self::$available_providers[$provider_key])) {
            return null;
        }
        
        // Return cached instance if available
        if (isset(self::$providers[$provider_key])) {
            return self::$providers[$provider_key];
        }
        
        $class_name = self::$available_providers[$provider_key];
        
        // Check if class exists
        if (!class_exists($class_name)) {
            return null;
        }
        
        // Create and cache provider instance
        self::$providers[$provider_key] = new $class_name($config);
        
        return self::$providers[$provider_key];
    }
    
    /**
     * Get current provider based on settings
     * 
     * @return ExplainerPlugin_AI_Provider_Interface|null Current provider or null if not configured
     */
    public static function get_current_provider() {
        $provider_key = get_option('explainer_api_provider', 'openai');
        return self::get_provider($provider_key);
    }
    
    /**
     * Get available providers list
     * 
     * @return array Array of provider_key => provider_name
     */
    public static function get_available_providers() {
        $providers = array();
        
        foreach (self::$available_providers as $key => $class_name) {
            $provider = self::get_provider($key);
            if ($provider) {
                $providers[$key] = $provider->get_name();
            }
        }
        
        return $providers;
    }
    
    /**
     * Get available models for current provider
     * 
     * @return array Array of model_key => model_label
     */
    public static function get_current_provider_models() {
        $provider = self::get_current_provider();
        
        if (!$provider) {
            return array();
        }
        
        return $provider->get_models();
    }
    
    /**
     * Get API key for current provider (returns encrypted key)
     * 
     * @return string Encrypted API key or empty string if not configured
     */
    public static function get_current_api_key() {
        $provider_key = get_option('explainer_api_provider', 'openai');
        
        switch ($provider_key) {
            case 'openai':
                return get_option('explainer_api_key', '');
            case 'claude':
                return get_option('explainer_claude_api_key', '');
            default:
                return '';
        }
    }
    
    /**
     * Get decrypted API key for current provider
     * 
     * @return string Decrypted API key or empty string if not configured
     */
    public static function get_current_decrypted_api_key() {
        $provider_key = get_option('explainer_api_provider', 'openai');
        $api_proxy = new ExplainerPlugin_API_Proxy();
        
        return $api_proxy->get_decrypted_api_key_for_provider($provider_key);
    }
    
    /**
     * Validate API key for current provider
     * 
     * @param string $api_key API key to validate
     * @return bool True if valid
     */
    public static function validate_current_api_key($api_key) {
        $provider = self::get_current_provider();
        
        if (!$provider) {
            return false;
        }
        
        return $provider->validate_api_key($api_key);
    }
    
    /**
     * Test API key for current provider
     * 
     * @param string $api_key API key to test
     * @return array Test result
     */
    public static function test_current_api_key($api_key) {
        $provider = self::get_current_provider();
        
        if (!$provider) {
            return array(
                'success' => false,
                'message' => __('No provider configured.', 'wp-ai-explainer')
            );
        }
        
        return $provider->test_api_key($api_key);
    }
    
    /**
     * Clear provider cache
     */
    public static function clear_cache() {
        self::$providers = array();
    }
    
    /**
     * Register a new provider
     * 
     * @param string $key Provider key
     * @param string $class_name Provider class name
     */
    public static function register_provider($key, $class_name) {
        self::$available_providers[$key] = $class_name;
    }
    
    /**
     * Check if a provider is available
     * 
     * @param string $provider_key Provider key
     * @return bool True if available
     */
    public static function is_provider_available($provider_key) {
        return isset(self::$available_providers[$provider_key]);
    }
}