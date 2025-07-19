<?php
/**
 * GDPR Compliance Handler
 * 
 * Handles GDPR compliance features including cookie consent,
 * data privacy, and user data management.
 *
 * @package ExplainerPlugin
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Explainer_GDPR_Compliance {
    
    /**
     * Cookie consent status
     */
    const COOKIE_CONSENT_NAME = 'explainer_gdpr_consent';
    const COOKIE_CONSENT_DURATION = 365; // days
    
    /**
     * Data retention periods
     */
    const DATA_RETENTION_LOGS = 30; // days
    const DATA_RETENTION_ANALYTICS = 90; // days
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_explainer_gdpr_consent', array($this, 'handle_consent_ajax'));
        add_action('wp_ajax_nopriv_explainer_gdpr_consent', array($this, 'handle_consent_ajax'));
        add_action('wp_ajax_explainer_export_data', array($this, 'handle_data_export'));
        add_action('wp_ajax_explainer_delete_data', array($this, 'handle_data_deletion'));
        
        // Privacy policy integration
        add_action('admin_init', array($this, 'add_privacy_policy_content'));
        
        // Cleanup hooks
        add_action('explainer_daily_cleanup', array($this, 'cleanup_old_data'));
        
        // Data export/erasure hooks (WordPress 4.9.6+)
        if (function_exists('wp_register_plugin_exporter')) {
            add_filter('wp_privacy_personal_data_exporters', array($this, 'register_data_exporter'));
            add_filter('wp_privacy_personal_data_erasers', array($this, 'register_data_eraser'));
        }
    }
    
    /**
     * Initialize GDPR compliance
     */
    public function init() {
        // Schedule daily cleanup if not already scheduled
        if (!wp_next_scheduled('explainer_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'explainer_daily_cleanup');
        }
        
        // Add cookie consent banner if needed
        if ($this->requires_consent_banner()) {
            add_action('wp_footer', array($this, 'render_consent_banner'));
        }
    }
    
    /**
     * Check if user has given consent
     */
    public function has_consent() {
        if (!$this->is_gdpr_applicable()) {
            return true; // No consent needed outside GDPR scope
        }
        
        $consent = isset($_COOKIE[self::COOKIE_CONSENT_NAME]) ? 
                   sanitize_text_field($_COOKIE[self::COOKIE_CONSENT_NAME]) : '';
        
        return $consent === 'granted';
    }
    
    /**
     * Check if GDPR is applicable
     */
    public function is_gdpr_applicable() {
        // Check if site is targeting EU users
        $gdpr_enabled = get_option('explainer_gdpr_enabled', true);
        
        if (!$gdpr_enabled) {
            return false;
        }
        
        // Simple IP-based check (in production, use a proper GeoIP service)
        $user_ip = $this->get_user_ip();
        $eu_check = $this->is_eu_ip($user_ip);
        
        return $eu_check;
    }
    
    /**
     * Check if consent banner is required
     */
    private function requires_consent_banner() {
        return $this->is_gdpr_applicable() && !$this->has_consent() && !$this->is_consent_declined();
    }
    
    /**
     * Check if consent was declined
     */
    private function is_consent_declined() {
        $consent = isset($_COOKIE[self::COOKIE_CONSENT_NAME]) ? 
                   sanitize_text_field($_COOKIE[self::COOKIE_CONSENT_NAME]) : '';
        
        return $consent === 'declined';
    }
    
    /**
     * Render consent banner
     */
    public function render_consent_banner() {
        if (is_admin()) {
            return;
        }
        
        $privacy_policy_url = get_privacy_policy_url();
        $site_name = get_bloginfo('name');
        
        ?>
        <div id="explainer-gdpr-banner" class="explainer-gdpr-banner" role="banner" aria-live="polite">
            <div class="explainer-gdpr-content">
                <div class="explainer-gdpr-message">
                    <h3><?php esc_html_e('Cookie Consent', 'explainer-plugin'); ?></h3>
                    <p>
                        <?php
                        printf(
                            /* translators: %s: site name */
                            esc_html__('This website uses cookies to enhance your experience, including the AI Explainer feature. We respect your privacy and only collect necessary data. By continuing to use %s, you consent to our use of cookies.', 'explainer-plugin'),
                            '<strong>' . esc_html($site_name) . '</strong>'
                        );
                        ?>
                    </p>
                    <?php if ($privacy_policy_url): ?>
                    <p>
                        <a href="<?php echo esc_url($privacy_policy_url); ?>" target="_blank" rel="noopener">
                            <?php esc_html_e('Read our Privacy Policy', 'explainer-plugin'); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="explainer-gdpr-actions">
                    <button type="button" 
                            id="explainer-gdpr-accept" 
                            class="explainer-gdpr-button explainer-gdpr-accept"
                            aria-label="<?php esc_attr_e('Accept cookies and continue', 'explainer-plugin'); ?>">
                        <?php esc_html_e('Accept', 'explainer-plugin'); ?>
                    </button>
                    <button type="button" 
                            id="explainer-gdpr-decline" 
                            class="explainer-gdpr-button explainer-gdpr-decline"
                            aria-label="<?php esc_attr_e('Decline cookies', 'explainer-plugin'); ?>">
                        <?php esc_html_e('Decline', 'explainer-plugin'); ?>
                    </button>
                    <button type="button" 
                            id="explainer-gdpr-customize" 
                            class="explainer-gdpr-button explainer-gdpr-customize"
                            aria-label="<?php esc_attr_e('Customize cookie preferences', 'explainer-plugin'); ?>">
                        <?php esc_html_e('Customize', 'explainer-plugin'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        (function() {
            'use strict';
            
            const banner = document.getElementById('explainer-gdpr-banner');
            const acceptBtn = document.getElementById('explainer-gdpr-accept');
            const declineBtn = document.getElementById('explainer-gdpr-decline');
            const customizeBtn = document.getElementById('explainer-gdpr-customize');
            
            if (!banner) return;
            
            // Handle accept
            acceptBtn.addEventListener('click', function() {
                setConsent('granted');
                hideBanner();
                enableExplainerFeatures();
            });
            
            // Handle decline
            declineBtn.addEventListener('click', function() {
                setConsent('declined');
                hideBanner();
                disableExplainerFeatures();
            });
            
            // Handle customize
            customizeBtn.addEventListener('click', function() {
                showCustomizeModal();
            });
            
            function setConsent(status) {
                const expiryDate = new Date();
                expiryDate.setDate(expiryDate.getDate() + <?php echo self::COOKIE_CONSENT_DURATION; ?>);
                
                document.cookie = '<?php echo self::COOKIE_CONSENT_NAME; ?>=' + status + 
                                '; expires=' + expiryDate.toUTCString() + 
                                '; path=/; SameSite=Lax; Secure';
                
                // Send to server
                if (typeof explainerAjax !== 'undefined') {
                    fetch(explainerAjax.ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'explainer_gdpr_consent',
                            nonce: explainerAjax.nonce,
                            consent: status
                        })
                    }).catch(function(error) {
                        console.warn('GDPR consent tracking failed:', error);
                    });
                }
            }
            
            function hideBanner() {
                banner.style.opacity = '0';
                banner.style.transform = 'translateY(100%)';
                setTimeout(function() {
                    banner.style.display = 'none';
                }, 300);
            }
            
            function enableExplainerFeatures() {
                if (window.ExplainerPlugin) {
                    window.ExplainerPlugin.config.enabled = true;
                    window.ExplainerPlugin.config.gdprConsent = true;
                }
            }
            
            function disableExplainerFeatures() {
                if (window.ExplainerPlugin) {
                    window.ExplainerPlugin.config.enabled = false;
                    window.ExplainerPlugin.config.gdprConsent = false;
                }
            }
            
            function showCustomizeModal() {
                // Basic implementation - in production, show detailed cookie preferences
                const allowAnalytics = confirm('<?php esc_js(_e('Allow analytics cookies to improve the service?', 'explainer-plugin')); ?>');
                const allowFunctional = confirm('<?php esc_js(_e('Allow functional cookies for AI explanations?', 'explainer-plugin')); ?>');
                
                if (allowFunctional) {
                    setConsent('granted');
                    enableExplainerFeatures();
                } else {
                    setConsent('declined');
                    disableExplainerFeatures();
                }
                
                hideBanner();
            }
            
            // Keyboard accessibility
            banner.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    declineBtn.click();
                }
            });
            
        })();
        </script>
        <?php
    }
    
    /**
     * Handle AJAX consent
     */
    public function handle_consent_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'explainer_nonce')) {
            wp_die('Security check failed');
        }
        
        $consent = sanitize_text_field($_POST['consent']);
        $user_ip = $this->get_user_ip();
        $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        // Log consent for audit trail
        $this->log_consent_event($consent, $user_ip, $user_agent);
        
        wp_send_json_success(array(
            'status' => $consent,
            'message' => __('Consent preference saved.', 'explainer-plugin')
        ));
    }
    
    /**
     * Log consent event
     */
    private function log_consent_event($consent, $user_ip, $user_agent) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'explainer_gdpr_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'consent_status' => $consent,
                'user_ip' => $this->anonymize_ip($user_ip),
                'user_agent' => substr($user_agent, 0, 255),
                'timestamp' => current_time('mysql'),
                'session_id' => $this->get_session_id()
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Handle data export request
     */
    public function handle_data_export() {
        if (!wp_verify_nonce($_POST['nonce'], 'explainer_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!is_user_logged_in()) {
            wp_die('Authentication required');
        }
        
        $user_id = get_current_user_id();
        $data = $this->get_user_data($user_id);
        
        wp_send_json_success(array(
            'data' => $data,
            'message' => __('User data exported successfully.', 'explainer-plugin')
        ));
    }
    
    /**
     * Handle data deletion request
     */
    public function handle_data_deletion() {
        if (!wp_verify_nonce($_POST['nonce'], 'explainer_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!is_user_logged_in()) {
            wp_die('Authentication required');
        }
        
        $user_id = get_current_user_id();
        $deleted = $this->delete_user_data($user_id);
        
        wp_send_json_success(array(
            'deleted' => $deleted,
            'message' => __('User data deleted successfully.', 'explainer-plugin')
        ));
    }
    
    /**
     * Get user data for export
     */
    private function get_user_data($user_id) {
        $data = array();
        
        // Get user preferences
        $preferences = get_user_meta($user_id, 'explainer_preferences', true);
        if ($preferences) {
            $data['preferences'] = $preferences;
        }
        
        // Get usage statistics (anonymized)
        $stats = get_user_meta($user_id, 'explainer_usage_stats', true);
        if ($stats) {
            $data['usage_statistics'] = $stats;
        }
        
        return $data;
    }
    
    /**
     * Delete user data
     */
    private function delete_user_data($user_id) {
        $deleted = array();
        
        // Delete user preferences
        if (delete_user_meta($user_id, 'explainer_preferences')) {
            $deleted[] = 'preferences';
        }
        
        // Delete usage statistics
        if (delete_user_meta($user_id, 'explainer_usage_stats')) {
            $deleted[] = 'usage_statistics';
        }
        
        // Delete from GDPR logs (anonymize instead of delete for audit trail)
        global $wpdb;
        $table_name = $wpdb->prefix . 'explainer_gdpr_logs';
        
        $wpdb->update(
            $table_name,
            array('user_ip' => '0.0.0.0', 'user_agent' => '[DELETED]'),
            array('session_id' => $this->get_session_id()),
            array('%s', '%s'),
            array('%s')
        );
        
        return $deleted;
    }
    
    /**
     * Register data exporter for WordPress privacy tools
     */
    public function register_data_exporter($exporters) {
        $exporters['explainer-plugin'] = array(
            'exporter_friendly_name' => __('WP AI Explainer', 'explainer-plugin'),
            'callback' => array($this, 'wp_privacy_exporter')
        );
        
        return $exporters;
    }
    
    /**
     * Register data eraser for WordPress privacy tools
     */
    public function register_data_eraser($erasers) {
        $erasers['explainer-plugin'] = array(
            'eraser_friendly_name' => __('WP AI Explainer', 'explainer-plugin'),
            'callback' => array($this, 'wp_privacy_eraser')
        );
        
        return $erasers;
    }
    
    /**
     * WordPress privacy exporter callback
     */
    public function wp_privacy_exporter($email_address, $page = 1) {
        $user = get_user_by('email', $email_address);
        if (!$user) {
            return array(
                'data' => array(),
                'done' => true
            );
        }
        
        $data = $this->get_user_data($user->ID);
        $export_items = array();
        
        if (!empty($data)) {
            $export_items[] = array(
                'group_id' => 'explainer_plugin',
                'group_label' => __('WP AI Explainer', 'explainer-plugin'),
                'item_id' => 'explainer_data_' . $user->ID,
                'data' => array(
                    array(
                        'name' => __('User Preferences', 'explainer-plugin'),
                        'value' => wp_json_encode($data['preferences'] ?? array())
                    ),
                    array(
                        'name' => __('Usage Statistics', 'explainer-plugin'),
                        'value' => wp_json_encode($data['usage_statistics'] ?? array())
                    )
                )
            );
        }
        
        return array(
            'data' => $export_items,
            'done' => true
        );
    }
    
    /**
     * WordPress privacy eraser callback
     */
    public function wp_privacy_eraser($email_address, $page = 1) {
        $user = get_user_by('email', $email_address);
        if (!$user) {
            return array(
                'items_removed' => 0,
                'items_retained' => 0,
                'messages' => array(),
                'done' => true
            );
        }
        
        $deleted = $this->delete_user_data($user->ID);
        
        return array(
            'items_removed' => count($deleted),
            'items_retained' => 0,
            'messages' => array(
                sprintf(
                    /* translators: %s: comma-separated list of deleted items */
                    __('Removed: %s', 'explainer-plugin'),
                    implode(', ', $deleted)
                )
            ),
            'done' => true
        );
    }
    
    /**
     * Add privacy policy content
     */
    public function add_privacy_policy_content() {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }
        
        $content = sprintf(
            '<h2>%s</h2>' .
            '<p>%s</p>' .
            '<h3>%s</h3>' .
            '<ul>' .
            '<li>%s</li>' .
            '<li>%s</li>' .
            '<li>%s</li>' .
            '</ul>' .
            '<h3>%s</h3>' .
            '<p>%s</p>' .
            '<h3>%s</h3>' .
            '<p>%s</p>',
            __('WP AI Explainer', 'explainer-plugin'),
            __('This plugin provides AI-powered text explanations. To provide this service, we may collect and process certain data.', 'explainer-plugin'),
            __('Data We Collect', 'explainer-plugin'),
            __('Selected text for AI processing (temporarily, not stored)', 'explainer-plugin'),
            __('Usage preferences and settings', 'explainer-plugin'),
            __('Basic usage statistics (anonymized)', 'explainer-plugin'),
            __('How We Use Your Data', 'explainer-plugin'),
            __('We use your data solely to provide AI explanations and improve the service. Text sent for AI processing is not stored permanently and is only used for generating explanations.', 'explainer-plugin'),
            __('Your Rights', 'explainer-plugin'),
            __('You can request access to, correction of, or deletion of your data at any time. You can also withdraw consent for data processing, which will disable the AI explanation features.', 'explainer-plugin')
        );
        
        wp_add_privacy_policy_content(
            'WP AI Explainer',
            wp_kses_post($content)
        );
    }
    
    /**
     * Cleanup old data
     */
    public function cleanup_old_data() {
        global $wpdb;
        
        // Clean up old GDPR logs
        $logs_table = $wpdb->prefix . 'explainer_gdpr_logs';
        $logs_cutoff = date('Y-m-d H:i:s', strtotime('-' . self::DATA_RETENTION_LOGS . ' days'));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$logs_table} WHERE timestamp < %s",
            $logs_cutoff
        ));
        
        // Clean up old analytics data
        $analytics_cutoff = strtotime('-' . self::DATA_RETENTION_ANALYTICS . ' days');
        delete_option('explainer_analytics_' . date('Y-m-d', $analytics_cutoff));
    }
    
    /**
     * Utility methods
     */
    
    private function get_user_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = sanitize_text_field($_SERVER[$key]);
                // Handle comma-separated IPs
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }
        
        return '0.0.0.0';
    }
    
    private function anonymize_ip($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0'; // Anonymize last octet
            return implode('.', $parts);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            for ($i = 4; $i < count($parts); $i++) {
                $parts[$i] = '0'; // Anonymize last 4 groups
            }
            return implode(':', $parts);
        }
        
        return '0.0.0.0';
    }
    
    private function is_eu_ip($ip) {
        // Simplified EU check - in production, use a proper GeoIP service
        // This is a basic implementation for demonstration
        $eu_ranges = array(
            '185.0.0.0/8',    // Example EU range
            '194.0.0.0/8',    // Example EU range
        );
        
        foreach ($eu_ranges as $range) {
            if ($this->ip_in_range($ip, $range)) {
                return true;
            }
        }
        
        return false; // Default to false for demo
    }
    
    private function ip_in_range($ip, $range) {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $bits) = explode('/', $range);
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        
        return ($ip_long & $mask) === ($subnet_long & $mask);
    }
    
    private function get_session_id() {
        if (session_id()) {
            return session_id();
        }
        
        // Fallback to user IP + user agent hash
        $user_ip = $this->get_user_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return md5($user_ip . $user_agent . date('Y-m-d'));
    }
    
    /**
     * Create GDPR tables on activation
     */
    public static function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'explainer_gdpr_logs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            consent_status varchar(20) NOT NULL,
            user_ip varchar(45) NOT NULL,
            user_agent text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            session_id varchar(32),
            PRIMARY KEY (id),
            KEY consent_status (consent_status),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Drop GDPR tables on uninstall
     */
    public static function drop_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'explainer_gdpr_logs';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}

// Initialize GDPR compliance
new Explainer_GDPR_Compliance();