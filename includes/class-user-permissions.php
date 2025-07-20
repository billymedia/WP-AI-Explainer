<?php
/**
 * User Permissions Management
 * 
 * Handles role-based access control, user capabilities,
 * and audit trail for admin actions.
 *
 * @package ExplainerPlugin
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Explainer_User_Permissions {
    
    /**
     * Custom capabilities
     */
    const CAP_MANAGE_EXPLAINER = 'manage_explainer_plugin';
    const CAP_VIEW_ANALYTICS = 'view_explainer_analytics';
    const CAP_EXPORT_DATA = 'export_explainer_data';
    const CAP_MANAGE_API = 'manage_explainer_api';
    const CAP_VIEW_LOGS = 'view_explainer_logs';
    
    /**
     * User roles that should have access
     */
    private $allowed_roles = array('administrator', 'editor');
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_explainer_audit_action', array($this, 'log_admin_action'));
        
        // User capability checks
        add_filter('user_has_cap', array($this, 'filter_user_capabilities'), 10, 4);
        
        // Admin interface filters
        add_action('admin_init', array($this, 'restrict_admin_access'));
        add_filter('option_page_capability_explainer_settings', array($this, 'get_required_capability'));
        
        // AJAX capability checks
        add_action('wp_ajax_explainer_get_explanation', array($this, 'check_explanation_capability'), 1);
        add_action('wp_ajax_nopriv_explainer_get_explanation', array($this, 'check_guest_access'), 1);
    }
    
    /**
     * Initialize user permissions
     */
    public function init() {
        // Add custom capabilities to roles
        $this->add_capabilities_to_roles();
        
        // Set up audit logging
        $this->setup_audit_logging();
    }
    
    /**
     * Add custom capabilities to WordPress roles
     */
    private function add_capabilities_to_roles() {
        // Administrator gets all capabilities
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap(self::CAP_MANAGE_EXPLAINER);
            $admin_role->add_cap(self::CAP_VIEW_ANALYTICS);
            $admin_role->add_cap(self::CAP_EXPORT_DATA);
            $admin_role->add_cap(self::CAP_MANAGE_API);
            $admin_role->add_cap(self::CAP_VIEW_LOGS);
        }
        
        // Editor gets limited capabilities
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap(self::CAP_VIEW_ANALYTICS);
            
            // Only add management capability if enabled in settings
            if (get_option('explainer_allow_editor_management', false)) {
                $editor_role->add_cap(self::CAP_MANAGE_EXPLAINER);
            }
        }
    }
    
    /**
     * Check if user can manage the plugin
     */
    public function can_manage_plugin($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        return user_can($user_id, self::CAP_MANAGE_EXPLAINER) || 
               user_can($user_id, 'manage_options');
    }
    
    /**
     * Check if user can view analytics
     */
    public function can_view_analytics($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        return user_can($user_id, self::CAP_VIEW_ANALYTICS) || 
               $this->can_manage_plugin($user_id);
    }
    
    /**
     * Check if user can export data
     */
    public function can_export_data($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        return user_can($user_id, self::CAP_EXPORT_DATA) || 
               user_can($user_id, 'manage_options');
    }
    
    /**
     * Check if user can manage API settings
     */
    public function can_manage_api($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        return user_can($user_id, self::CAP_MANAGE_API) || 
               user_can($user_id, 'manage_options');
    }
    
    /**
     * Check if user can view logs
     */
    public function can_view_logs($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        return user_can($user_id, self::CAP_VIEW_LOGS) || 
               user_can($user_id, 'manage_options');
    }
    
    /**
     * Check if user can use explanation feature
     */
    public function can_use_explainer($user_id = null) {
        // Guest access check
        if (!$user_id && !is_user_logged_in()) {
            return $this->is_guest_access_allowed();
        }
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Check if user is in allowed roles
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Check if user role is allowed
        $user_roles = $user->roles;
        $allowed_roles = $this->get_allowed_user_roles();
        
        return !empty(array_intersect($user_roles, $allowed_roles));
    }
    
    /**
     * Get allowed user roles
     */
    private function get_allowed_user_roles() {
        $default_roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');
        $allowed_roles = get_option('explainer_allowed_user_roles', $default_roles);
        
        return is_array($allowed_roles) ? $allowed_roles : $default_roles;
    }
    
    /**
     * Check if guest access is allowed
     */
    private function is_guest_access_allowed() {
        return get_option('explainer_allow_guest_access', true);
    }
    
    /**
     * Filter user capabilities
     */
    public function filter_user_capabilities($allcaps, $caps, $args, $user) {
        // Don't modify capabilities for administrators
        if (in_array('administrator', $user->roles)) {
            return $allcaps;
        }
        
        // Apply role-based restrictions
        if (isset($args[0])) {
            $capability = $args[0];
            
            switch ($capability) {
                case self::CAP_MANAGE_EXPLAINER:
                    if (!get_option('explainer_allow_editor_management', false) && 
                        in_array('editor', $user->roles)) {
                        $allcaps[$capability] = false;
                    }
                    break;
                    
                case self::CAP_MANAGE_API:
                    // Only administrators can manage API by default
                    if (!in_array('administrator', $user->roles)) {
                        $allcaps[$capability] = false;
                    }
                    break;
            }
        }
        
        return $allcaps;
    }
    
    /**
     * Restrict admin access
     */
    public function restrict_admin_access() {
        // Check if current user can access settings page
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'explainer-settings' ) {
            if (!$this->can_manage_plugin()) {
                wp_die(esc_html__('You do not have permission to access this page.', 'wp-ai-explainer'));
            }
        }
    }
    
    /**
     * Get required capability for settings page
     */
    public function get_required_capability($capability) {
        return self::CAP_MANAGE_EXPLAINER;
    }
    
    /**
     * Check explanation capability for AJAX requests
     */
    public function check_explanation_capability() {
        if (!$this->can_use_explainer()) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to use the AI Explainer feature.', 'wp-ai-explainer')
            ));
        }
    }
    
    /**
     * Check guest access for non-logged-in users
     */
    public function check_guest_access() {
        if (!$this->is_guest_access_allowed()) {
            wp_send_json_error(array(
                'message' => __('Guest access is not allowed. Please log in to use the AI Explainer feature.', 'wp-ai-explainer')
            ));
        }
        
        // Apply rate limiting for guests
        if (!$this->check_guest_rate_limit()) {
            wp_send_json_error(array(
                'message' => __('Rate limit exceeded. Please try again later.', 'wp-ai-explainer')
            ));
        }
    }
    
    /**
     * Check guest rate limiting
     */
    private function check_guest_rate_limit() {
        $ip = $this->get_user_ip();
        $limit_key = 'explainer_guest_limit_' . md5($ip);
        $current_count = get_transient($limit_key);
        
        if ($current_count === false) {
            set_transient($limit_key, 1, HOUR_IN_SECONDS);
            return true;
        }
        
        $guest_limit = get_option('explainer_guest_hourly_limit', 10);
        
        if ($current_count >= $guest_limit) {
            return false;
        }
        
        set_transient($limit_key, $current_count + 1, HOUR_IN_SECONDS);
        return true;
    }
    
    /**
     * Setup audit logging
     */
    private function setup_audit_logging() {
        // Log significant admin actions
        add_action('update_option_explainer_api_key', array($this, 'log_api_key_change'));
        add_action('update_option_explainer_enabled', array($this, 'log_plugin_toggle'));
        add_action('explainer_settings_saved', array($this, 'log_settings_change'));
    }
    
    /**
     * Log API key changes
     */
    public function log_api_key_change($option_name) {
        $this->log_audit_event('api_key_changed', array(
            'action' => 'API key modified',
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_user_ip()
        ));
    }
    
    /**
     * Log plugin toggle
     */
    public function log_plugin_toggle($option_name) {
        $enabled = get_option('explainer_enabled');
        $this->log_audit_event('plugin_toggled', array(
            'action' => $enabled ? 'Plugin enabled' : 'Plugin disabled',
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_user_ip()
        ));
    }
    
    /**
     * Log settings changes
     */
    public function log_settings_change($settings) {
        $this->log_audit_event('settings_changed', array(
            'action' => 'Settings modified',
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_user_ip(),
            'changed_settings' => array_keys($settings)
        ));
    }
    
    /**
     * Log admin action via AJAX
     */
    public function log_admin_action() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'explainer_nonce' ) ) {
            wp_die('Security check failed');
        }
        
        if (!$this->can_manage_plugin()) {
            wp_die('Insufficient permissions');
        }
        
        if ( ! isset( $_POST['action_type'] ) || ! isset( $_POST['details'] ) ) {
            wp_die('Missing required parameters');
        }
        
        $action = sanitize_text_field( wp_unslash( $_POST['action_type'] ) );
        $details = sanitize_text_field( wp_unslash( $_POST['details'] ) );
        
        $this->log_audit_event($action, array(
            'action' => $details,
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_user_ip()
        ));
        
        wp_send_json_success();
    }
    
    /**
     * Log audit event
     */
    private function log_audit_event($event_type, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'explainer_audit_log';
        
        // Create audit table if it doesn't exist
        $this->ensure_audit_table_exists();
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct query needed for audit logging
        $wpdb->insert(
            $table_name,
            array(
                'event_type' => $event_type,
                'user_id' => $data['user_id'],
                'action' => $data['action'],
                'ip_address' => $this->anonymize_ip($data['ip_address']),
                'user_agent' => substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ), 0, 255 ),
                'event_data' => wp_json_encode($data),
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Ensure audit table exists
     */
    private function ensure_audit_table_exists() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'explainer_audit_log';
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query needed for table existence check
        if ($wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                event_type varchar(50) NOT NULL,
                user_id bigint(20) DEFAULT NULL,
                action varchar(255) NOT NULL,
                ip_address varchar(45) NOT NULL,
                user_agent text,
                event_data text,
                timestamp datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY event_type (event_type),
                KEY user_id (user_id),
                KEY timestamp (timestamp)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    /**
     * Get audit logs
     */
    public function get_audit_logs($limit = 50, $offset = 0, $filters = array()) {
        if (!$this->can_view_logs()) {
            return array();
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'explainer_audit_log';
        
        $where_clauses = array('1=1');
        $where_values = array();
        
        // Apply filters
        if (!empty($filters['user_id'])) {
            $where_clauses[] = 'user_id = %d';
            $where_values[] = $filters['user_id'];
        }
        
        if (!empty($filters['event_type'])) {
            $where_clauses[] = 'event_type = %s';
            $where_values[] = $filters['event_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'timestamp >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'timestamp <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        $where_clause = implode(' AND ', $where_clauses);
        
        $sql = "SELECT * FROM $table_name WHERE $where_clause ORDER BY timestamp DESC LIMIT %d OFFSET %d"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $where_values[] = $limit;
        $where_values[] = $offset;
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Direct query needed for audit log retrieval
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get user role management interface
     */
    public function render_role_management() {
        if (!$this->can_manage_plugin()) {
            return '<p>' . __('Access denied.', 'wp-ai-explainer') . '</p>';
        }
        
        $allowed_roles = $this->get_allowed_user_roles();
        $all_roles = wp_roles()->get_names();
        
        ob_start();
        ?>
        <div class="explainer-role-management">
            <h3><?php esc_html_e('User Role Management', 'wp-ai-explainer'); ?></h3>
            <p><?php esc_html_e('Select which user roles can use the AI Explainer feature.', 'wp-ai-explainer'); ?></p>
            
            <form method="post" action="options.php">
                <?php settings_fields('explainer_permissions'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Allowed User Roles', 'wp-ai-explainer'); ?></th>
                        <td>
                            <?php foreach ($all_roles as $role_key => $role_name): ?>
                                <label>
                                    <input type="checkbox" 
                                           name="explainer_allowed_user_roles[]" 
                                           value="<?php echo esc_attr($role_key); ?>"
                                           <?php checked(in_array($role_key, $allowed_roles)); ?>>
                                    <?php echo esc_html($role_name); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Guest Access', 'wp-ai-explainer'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="explainer_allow_guest_access" 
                                       value="1"
                                       <?php checked($this->is_guest_access_allowed()); ?>>
                                <?php esc_html_e('Allow non-logged-in users to use the explainer', 'wp-ai-explainer'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Editor Management', 'wp-ai-explainer'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="explainer_allow_editor_management" 
                                       value="1"
                                       <?php checked(get_option('explainer_allow_editor_management', false)); ?>>
                                <?php esc_html_e('Allow editors to manage plugin settings', 'wp-ai-explainer'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Guest Rate Limit', 'wp-ai-explainer'); ?></th>
                        <td>
                            <input type="number" 
                                   name="explainer_guest_hourly_limit" 
                                   value="<?php echo esc_attr(get_option('explainer_guest_hourly_limit', 10)); ?>"
                                   min="1" max="100">
                            <p class="description"><?php esc_html_e('Maximum explanations per hour for guest users', 'wp-ai-explainer'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Utility methods
     */
    
    private function get_user_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[$key] ) );
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
            $parts[3] = '0';
            return implode('.', $parts);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            for ($i = 4; $i < count($parts); $i++) {
                $parts[$i] = '0';
            }
            return implode(':', $parts);
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Remove capabilities on deactivation
     */
    public static function remove_capabilities() {
        $roles = array('administrator', 'editor');
        $caps = array(
            self::CAP_MANAGE_EXPLAINER,
            self::CAP_VIEW_ANALYTICS,
            self::CAP_EXPORT_DATA,
            self::CAP_MANAGE_API,
            self::CAP_VIEW_LOGS
        );
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($caps as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
    
    /**
     * Create audit table on activation
     */
    public static function create_audit_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'explainer_audit_log';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            action varchar(255) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            event_data text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Drop audit table on uninstall
     */
    public static function drop_audit_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'explainer_audit_log';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Direct schema change needed for audit table cleanup on uninstall
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}

// Initialize user permissions
new Explainer_User_Permissions();