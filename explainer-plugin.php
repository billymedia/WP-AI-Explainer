<?php
/**
 * Plugin Name: AI Explainer Plugin
 * Plugin URI: https://github.com/billymedia/wp-explainer
 * Description: A lightweight WordPress plugin that uses multiple AI providers (OpenAI, Claude) to explain highlighted text via interactive tooltips. Features customisable appearance, disclaimers, provider attribution, encrypted API storage, and comprehensive admin interface.
 * Version: 1.0.0
 * Author: Billy Patel
 * Author URI: https://billymedia.co.uk
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: explainer-plugin
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EXPLAINER_PLUGIN_VERSION', '1.0.0');
define('EXPLAINER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EXPLAINER_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EXPLAINER_PLUGIN_FILE', __FILE__);
define('EXPLAINER_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class ExplainerPlugin {
    
    /**
     * Plugin instance
     * @var ExplainerPlugin
     */
    private static $instance = null;
    
    /**
     * Plugin loader
     * @var ExplainerPlugin_Loader
     */
    private $loader;
    
    /**
     * Get plugin instance
     * @return ExplainerPlugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core loader class
        require_once EXPLAINER_PLUGIN_PATH . 'includes/class-loader.php';
        
        // Helper functions
        require_once EXPLAINER_PLUGIN_PATH . 'includes/helpers.php';
        
        // Admin functionality
        if (is_admin()) {
            require_once EXPLAINER_PLUGIN_PATH . 'includes/class-admin.php';
        }
        
        // AI Provider system
        require_once EXPLAINER_PLUGIN_PATH . 'includes/interfaces/interface-ai-provider.php';
        require_once EXPLAINER_PLUGIN_PATH . 'includes/abstracts/abstract-ai-provider.php';
        require_once EXPLAINER_PLUGIN_PATH . 'includes/providers/class-openai-provider.php';
        require_once EXPLAINER_PLUGIN_PATH . 'includes/providers/class-claude-provider.php';
        require_once EXPLAINER_PLUGIN_PATH . 'includes/class-provider-factory.php';
        
        // API proxy for secure AI integration
        require_once EXPLAINER_PLUGIN_PATH . 'includes/class-api-proxy.php';
        
        // Theme compatibility system
        require_once EXPLAINER_PLUGIN_PATH . 'includes/class-theme-compatibility.php';
        
        // Security enhancements
        require_once EXPLAINER_PLUGIN_PATH . 'includes/class-security.php';
        
        // GDPR compliance
        require_once EXPLAINER_PLUGIN_PATH . 'includes/class-gdpr-compliance.php';
        
        // User permissions management
        require_once EXPLAINER_PLUGIN_PATH . 'includes/class-user-permissions.php';
        
        // Initialize loader
        $this->loader = new ExplainerPlugin_Loader();
    }
    
    /**
     * Set plugin locale for internationalization
     */
    private function set_locale() {
        $this->loader->add_action('plugins_loaded', $this, 'load_plugin_textdomain');
    }
    
    /**
     * Load plugin text domain for translations
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'explainer-plugin',
            false,
            dirname(EXPLAINER_PLUGIN_BASENAME) . '/languages/'
        );
    }
    
    /**
     * Define admin hooks
     */
    private function define_admin_hooks() {
        if (is_admin()) {
            $plugin_admin = new ExplainerPlugin_Admin();
            
            // Admin menu and settings
            $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
            $this->loader->add_action('admin_init', $plugin_admin, 'settings_init');
            
            // Admin scripts and styles
            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        }
    }
    
    /**
     * Define public hooks
     */
    private function define_public_hooks() {
        // Initialize classes (needed for both frontend and AJAX)
        $api_proxy = new ExplainerPlugin_API_Proxy();
        
        if (!is_admin()) {
            // Initialize theme compatibility
            $theme_compatibility = new ExplainerPlugin_Theme_Compatibility();
            
            // Initialize security
            $security = new ExplainerPlugin_Security();
            
            // Frontend scripts and styles
            error_log('ExplainerPlugin: Registering wp_enqueue_scripts hooks');
            $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_public_styles');
            $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_public_scripts');
        }
        
        // Ajax handlers - register for both admin and frontend
        $this->loader->add_action('init', $this, 'register_ajax_handlers');
    }
    
    /**
     * Register AJAX handlers
     */
    public function register_ajax_handlers() {
        error_log('ExplainerPlugin: Registering AJAX handlers');
        
        // Get API proxy instance
        $api_proxy = new ExplainerPlugin_API_Proxy();
        
        // Register AJAX handlers for both logged-in and non-logged-in users
        add_action('wp_ajax_explainer_get_explanation', array($api_proxy, 'get_explanation'));
        add_action('wp_ajax_nopriv_explainer_get_explanation', array($api_proxy, 'get_explanation'));
        
        error_log('ExplainerPlugin: AJAX handlers registered');
    }
    
    /**
     * Enqueue public styles
     */
    public function enqueue_public_styles() {
        if (!$this->should_load_assets()) {
            return;
        }
        
        wp_enqueue_style(
            'explainer-plugin-style',
            EXPLAINER_PLUGIN_URL . 'assets/css/style.css',
            array(),
            EXPLAINER_PLUGIN_VERSION,
            'all'
        );
    }
    
    /**
     * Enqueue public scripts
     */
    public function enqueue_public_scripts() {
        error_log('ExplainerPlugin: enqueue_public_scripts called');
        
        if (!$this->should_load_assets()) {
            error_log('ExplainerPlugin: should_load_assets returned false');
            return;
        }
        
        error_log('ExplainerPlugin: should_load_assets returned true, proceeding with script loading');
        
        // Load scripts immediately for debugging
        $this->enqueue_scripts_conditionally();
        
        // Preload critical resources
        add_action('wp_head', array($this, 'add_resource_hints'), 1);
    }
    
    /**
     * Conditionally enqueue scripts in footer for better performance
     */
    public function enqueue_scripts_conditionally() {
        error_log('ExplainerPlugin: enqueue_scripts_conditionally called');
        
        // Load on all frontend pages for debugging
        if (is_admin()) {
            error_log('ExplainerPlugin: Skipping script enqueue - is_admin() is true');
            return;
        }
        
        error_log('ExplainerPlugin: Enqueueing scripts...');
        
        // Tooltip script (load first) - using full version with footer support
        $tooltip_url = EXPLAINER_PLUGIN_URL . 'assets/js/tooltip.js';
        error_log('ExplainerPlugin: Tooltip script URL: ' . $tooltip_url);
        
        wp_enqueue_script(
            'explainer-plugin-tooltip',
            $tooltip_url,
            array(),
            EXPLAINER_PLUGIN_VERSION,
            true // Load in footer
        );
        
        // Main explainer script
        $main_url = EXPLAINER_PLUGIN_URL . 'assets/js/explainer.js';
        error_log('ExplainerPlugin: Main script URL: ' . $main_url);
        
        wp_enqueue_script(
            'explainer-plugin-main',
            $main_url,
            array('explainer-plugin-tooltip'),
            EXPLAINER_PLUGIN_VERSION,
            true // Load in footer
        );
        
        error_log('ExplainerPlugin: Scripts enqueued successfully');
        
        // Localize script for Ajax with optimized settings
        wp_localize_script('explainer-plugin-main', 'explainerAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('explainer_nonce'),
            'settings' => array_merge($this->get_optimized_settings(), array(
                'tooltip_url' => EXPLAINER_PLUGIN_URL . 'assets/js/tooltip.js'
            )),
            'debug' => get_option('explainer_debug_mode', false)
        ));
    }
    
    /**
     * Add resource hints for better performance
     */
    public function add_resource_hints() {
        if (!$this->should_load_assets()) {
            return;
        }
        
        // DNS prefetch for AI APIs
        echo '<link rel="dns-prefetch" href="//api.openai.com">' . "\n";
        echo '<link rel="dns-prefetch" href="//api.anthropic.com">' . "\n";
        
        // Preload critical CSS
        printf(
            '<link rel="preload" href="%s" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n",
            EXPLAINER_PLUGIN_URL . 'assets/css/style.css?ver=' . EXPLAINER_PLUGIN_VERSION
        );
    }
    
    /**
     * Get optimized settings for frontend
     */
    private function get_optimized_settings() {
        static $settings = null;
        
        if ($settings === null) {
            $settings = array(
                'enabled' => get_option('explainer_enabled', true),
                'max_selection_length' => (int) get_option('explainer_max_selection_length', 200),
                'min_selection_length' => (int) get_option('explainer_min_selection_length', 3),
                'max_words' => (int) get_option('explainer_max_words', 30),
                'min_words' => (int) get_option('explainer_min_words', 1),
                'included_selectors' => get_option('explainer_included_selectors', 'article, main, .content, .entry-content, .post-content'),
                'excluded_selectors' => get_option('explainer_excluded_selectors', ''),
                'toggle_position' => get_option('explainer_toggle_position', 'bottom-right'),
                'tooltip_bg_color' => get_option('explainer_tooltip_bg_color', '#333333'),
                'tooltip_text_color' => get_option('explainer_tooltip_text_color', '#ffffff'),
                'button_enabled_color' => get_option('explainer_button_enabled_color', '#46b450'),
                'button_disabled_color' => get_option('explainer_button_disabled_color', '#666666'),
                'button_text_color' => get_option('explainer_button_text_color', '#ffffff'),
                'cache_enabled' => get_option('explainer_cache_enabled', true),
                'cache_duration' => (int) get_option('explainer_cache_duration', 24),
                'rate_limit_enabled' => get_option('explainer_rate_limit_enabled', true),
                'rate_limit_logged' => (int) get_option('explainer_rate_limit_logged', 20),
                'rate_limit_anonymous' => (int) get_option('explainer_rate_limit_anonymous', 10),
                'api_provider' => get_option('explainer_api_provider', 'openai'),
                'api_model' => get_option('explainer_api_model', 'gpt-3.5-turbo'),
                'custom_prompt' => get_option('explainer_custom_prompt', 'Please provide a clear, concise explanation of the following text in 1-2 sentences: {{snippet}}'),
                'show_disclaimer' => get_option('explainer_show_disclaimer', true),
                'show_provider' => get_option('explainer_show_provider', true),
                'tooltip_footer_color' => get_option('explainer_tooltip_footer_color', '#ffffff'),
                'debug_mode' => get_option('explainer_debug_mode', false),
            );
        }
        
        return $settings;
    }
    
    /**
     * Check if current page is likely to need the plugin
     */
    private function is_content_page() {
        // Skip on admin, login, and feed pages
        if (is_admin() || $this->is_login_page() || is_feed()) {
            return false;
        }
        
        // Skip on search and 404 pages (usually less content)
        if (is_search() || is_404()) {
            return false;
        }
        
        // Load on content pages
        return is_singular() || is_home() || is_archive() || is_category() || is_tag();
    }
    
    /**
     * Check if plugin assets should be loaded
     */
    private function should_load_assets() {
        // Don't load on admin pages
        if (is_admin()) {
            return false;
        }
        
        // Don't load if plugin is disabled
        if (!get_option('explainer_enabled', true)) {
            return false;
        }
        
        // Don't load on login/register pages
        if ($this->is_login_page()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if current page is login/register page
     */
    private function is_login_page() {
        // Check for WordPress login page
        if (function_exists('is_login') && is_login()) {
            return true;
        }
        
        // Check for common login/register URLs
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $login_pages = array(
            'wp-login.php',
            'wp-register.php',
            '/login',
            '/register',
            '/signup',
            '/sign-up'
        );
        
        foreach ($login_pages as $page) {
            if (strpos($request_uri, $page) !== false) {
                return true;
            }
        }
        
        // Check if we're in the admin area
        if (is_admin()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Run the plugin
     */
    public function run() {
        $this->loader->run();
    }
}

/**
 * Plugin activation hook
 */
function activate_explainer_plugin() {
    require_once EXPLAINER_PLUGIN_PATH . 'includes/class-activator.php';
    ExplainerPlugin_Activator::activate();
}

/**
 * Plugin deactivation hook
 */
function deactivate_explainer_plugin() {
    require_once EXPLAINER_PLUGIN_PATH . 'includes/class-deactivator.php';
    ExplainerPlugin_Deactivator::deactivate();
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'activate_explainer_plugin');
register_deactivation_hook(__FILE__, 'deactivate_explainer_plugin');

/**
 * Initialize and run the plugin
 */
function run_explainer_plugin() {
    $plugin = ExplainerPlugin::get_instance();
    $plugin->run();
}

// Start the plugin
run_explainer_plugin();