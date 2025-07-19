<?php
/**
 * Plugin Name: WP AI Explainer
 * Plugin URI: https://github.com/billymedia/WP-AI-Explainer
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
        
        // Load localization helper
        require_once EXPLAINER_PLUGIN_PATH . 'includes/class-localization.php';
        
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
        // Check for custom language setting, fallback to WordPress locale
        $selected_language = get_option('explainer_language', get_locale());
        
        if (!empty($selected_language) && $selected_language !== get_locale()) {
            // Override locale for this plugin only if different from WordPress default
            add_filter('plugin_locale', array($this, 'override_plugin_locale'), 10, 2);
        }
        
        load_plugin_textdomain(
            'explainer-plugin',
            false,
            dirname(EXPLAINER_PLUGIN_BASENAME) . '/languages/'
        );
        
        // Remove the filter after loading
        if (!empty($selected_language) && $selected_language !== get_locale()) {
            remove_filter('plugin_locale', array($this, 'override_plugin_locale'), 10);
        }
    }
    
    /**
     * Override plugin locale for this plugin only
     */
    public function override_plugin_locale($locale, $domain) {
        if ($domain === 'explainer-plugin') {
            $selected_language = get_option('explainer_language', get_locale());
            if (!empty($selected_language)) {
                return $selected_language;
            }
        }
        return $locale;
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
        
        // Register AJAX handler for localized strings
        add_action('wp_ajax_explainer_get_localized_strings', array($this, 'get_localized_strings'));
        add_action('wp_ajax_nopriv_explainer_get_localized_strings', array($this, 'get_localized_strings'));
        
        error_log('ExplainerPlugin: AJAX handlers registered');
    }
    
    /**
     * Get localized strings for frontend
     */
    public function get_localized_strings() {
        // Get selected language, fallback to WordPress locale
        $selected_language = get_option('explainer_language', get_locale());
        
        // Define localized strings
        $strings = array(
            'en_US' => array(
                'explanation' => 'Explanation',
                'loading' => 'Loading...',
                'error' => 'Error',
                'disclaimer' => 'AI-generated content may not always be accurate',
                'powered_by' => 'Powered by',
                'failed_to_get_explanation' => 'Failed to get explanation',
                'connection_error' => 'Connection error. Please try again.',
                'loading_explanation' => 'Loading explanation...',
                'selection_too_short' => 'Selection too short (minimum %d characters)',
                'selection_too_long' => 'Selection too long (maximum %d characters)',
                'selection_word_count' => 'Selection must be between %d and %d words',
                'ai_explainer_enabled' => 'AI Explainer enabled. Select text to get explanations.',
                'ai_explainer_disabled' => 'AI Explainer disabled.',
                'blocked_word_found' => 'Your selection contains blocked content'
            ),
            'en_GB' => array(
                'explanation' => 'Explanation',
                'loading' => 'Loading...',
                'error' => 'Error',
                'disclaimer' => 'AI-generated content may not always be accurate',
                'powered_by' => 'Powered by',
                'failed_to_get_explanation' => 'Failed to get explanation',
                'connection_error' => 'Connection error. Please try again.',
                'loading_explanation' => 'Loading explanation...',
                'selection_too_short' => 'Selection too short (minimum %d characters)',
                'selection_too_long' => 'Selection too long (maximum %d characters)',
                'selection_word_count' => 'Selection must be between %d and %d words',
                'ai_explainer_enabled' => 'AI Explainer enabled. Select text to get explanations.',
                'ai_explainer_disabled' => 'AI Explainer disabled.',
                'blocked_word_found' => 'Your selection contains blocked content'
            ),
            'es_ES' => array(
                'explanation' => 'Explicación',
                'loading' => 'Cargando...',
                'error' => 'Error',
                'disclaimer' => 'El contenido generado por IA puede no ser siempre preciso',
                'powered_by' => 'Desarrollado por',
                'failed_to_get_explanation' => 'Error al obtener la explicación',
                'connection_error' => 'Error de conexión. Por favor, inténtalo de nuevo.',
                'loading_explanation' => 'Cargando explicación...',
                'selection_too_short' => 'Selección demasiado corta (mínimo %d caracteres)',
                'selection_too_long' => 'Selección demasiado larga (máximo %d caracteres)',
                'selection_word_count' => 'La selección debe tener entre %d y %d palabras',
                'ai_explainer_enabled' => 'Explicador IA activado. Selecciona texto para obtener explicaciones.',
                'ai_explainer_disabled' => 'Explicador IA desactivado.',
                'blocked_word_found' => 'Su selección contiene contenido bloqueado'
            ),
            'de_DE' => array(
                'explanation' => 'Erklärung',
                'loading' => 'Wird geladen...',
                'error' => 'Fehler',
                'disclaimer' => 'KI-generierte Inhalte sind möglicherweise nicht immer korrekt',
                'powered_by' => 'Unterstützt von',
                'failed_to_get_explanation' => 'Erklärung konnte nicht abgerufen werden',
                'connection_error' => 'Verbindungsfehler. Bitte versuchen Sie es erneut.',
                'loading_explanation' => 'Erklärung wird geladen...',
                'selection_too_short' => 'Auswahl zu kurz (mindestens %d Zeichen)',
                'selection_too_long' => 'Auswahl zu lang (maximal %d Zeichen)',
                'selection_word_count' => 'Auswahl muss zwischen %d und %d Wörtern enthalten',
                'ai_explainer_enabled' => 'KI-Erklärer aktiviert. Text auswählen für Erklärungen.',
                'ai_explainer_disabled' => 'KI-Erklärer deaktiviert.',
                'blocked_word_found' => 'Ihre Auswahl enthält blockierten Inhalt'
            ),
            'fr_FR' => array(
                'explanation' => 'Explication',
                'loading' => 'Chargement...',
                'error' => 'Erreur',
                'disclaimer' => 'Le contenu généré par IA peut ne pas toujours être précis',
                'powered_by' => 'Propulsé par',
                'failed_to_get_explanation' => 'Impossible d\'obtenir l\'explication',
                'connection_error' => 'Erreur de connexion. Veuillez réessayer.',
                'loading_explanation' => 'Chargement de l\'explication...',
                'selection_too_short' => 'Sélection trop courte (minimum %d caractères)',
                'selection_too_long' => 'Sélection trop longue (maximum %d caractères)',
                'selection_word_count' => 'La sélection doit contenir entre %d et %d mots',
                'ai_explainer_enabled' => 'Explicateur IA activé. Sélectionnez du texte pour obtenir des explications.',
                'ai_explainer_disabled' => 'Explicateur IA désactivé.',
                'blocked_word_found' => 'Votre sélection contient du contenu bloqué'
            ),
            'hi_IN' => array(
                'explanation' => 'व्याख्या',
                'loading' => 'लोड हो रहा है...',
                'error' => 'त्रुटि',
                'disclaimer' => 'AI-जनरेटेड सामग्री हमेशा सटीक नहीं हो सकती',
                'powered_by' => 'द्वारा संचालित',
                'failed_to_get_explanation' => 'व्याख्या प्राप्त करने में विफल',
                'connection_error' => 'कनेक्शन त्रुटि। कृपया पुनः प्रयास करें।',
                'loading_explanation' => 'व्याख्या लोड हो रही है...',
                'selection_too_short' => 'चयन बहुत छोटा है (न्यूनतम %d वर्ण)',
                'selection_too_long' => 'चयन बहुत लंबा है (अधिकतम %d वर्ण)',
                'selection_word_count' => 'चयन में %d और %d शब्दों के बीच होना चाहिए',
                'ai_explainer_enabled' => 'AI व्याख्याकार सक्षम। व्याख्या पाने के लिए टेक्स्ट चुनें।',
                'ai_explainer_disabled' => 'AI व्याख्याकार अक्षम।',
                'blocked_word_found' => 'आपके चयन में अवरुद्ध सामग्री है'
            ),
            'zh_CN' => array(
                'explanation' => '解释',
                'loading' => '加载中...',
                'error' => '错误',
                'disclaimer' => 'AI生成的内容可能并不总是准确的',
                'powered_by' => '技术支持',
                'failed_to_get_explanation' => '获取解释失败',
                'connection_error' => '连接错误。请重试。',
                'loading_explanation' => '正在加载解释...',
                'selection_too_short' => '选择太短（最少%d个字符）',
                'selection_too_long' => '选择太长（最多%d个字符）',
                'selection_word_count' => '选择必须在%d到%d个单词之间',
                'ai_explainer_enabled' => 'AI解释器已启用。选择文本以获取解释。',
                'ai_explainer_disabled' => 'AI解释器已禁用。',
                'blocked_word_found' => '您的选择包含被阻止的内容'
            )
        );
        
        // Get strings for selected language, fallback to English
        $localized_strings = isset($strings[$selected_language]) ? $strings[$selected_language] : $strings['en_GB'];
        
        wp_send_json_success(array(
            'language' => $selected_language,
            'strings' => $localized_strings
        ));
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
 * Add settings link to plugin action links
 * 
 * @param array $links Array of plugin action links
 * @return array Modified array of plugin action links
 */
function explainer_plugin_action_links($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=explainer-settings'),
        __('Settings', 'explainer-plugin')
    );
    
    // Add settings link to the beginning of the array
    array_unshift($links, $settings_link);
    
    return $links;
}

// Register the settings link filter
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'explainer_plugin_action_links');

/**
 * Initialize and run the plugin
 */
function run_explainer_plugin() {
    $plugin = ExplainerPlugin::get_instance();
    $plugin->run();
}

// Start the plugin
run_explainer_plugin();