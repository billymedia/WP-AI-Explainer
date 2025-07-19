<?php
/**
 * Accessibility Testing and Validation
 * 
 * Provides tools for testing WCAG 2.1 AA compliance,
 * keyboard navigation, screen reader compatibility,
 * and color contrast validation.
 *
 * @package ExplainerPlugin
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Explainer_Accessibility_Testing {
    
    /**
     * WCAG 2.1 AA Requirements
     */
    const WCAG_AA_CONTRAST_RATIO = 4.5;
    const WCAG_AA_LARGE_TEXT_RATIO = 3.0;
    const WCAG_AAA_CONTRAST_RATIO = 7.0;
    
    /**
     * Test results storage
     */
    private $test_results = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_explainer_run_accessibility_tests', array($this, 'run_accessibility_tests'));
        add_action('wp_ajax_explainer_get_test_results', array($this, 'get_test_results'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_testing_scripts'));
    }
    
    /**
     * Enqueue testing scripts
     */
    public function enqueue_testing_scripts($hook) {
        if ($hook !== 'toplevel_page_explainer-settings') {
            return;
        }
        
        wp_enqueue_script(
            'explainer-accessibility-testing',
            EXPLAINER_PLUGIN_URL . 'assets/js/accessibility-testing.js',
            array('jquery'),
            EXPLAINER_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('explainer-accessibility-testing', 'explainerAccessibilityTest', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('explainer_nonce'),
            'strings' => array(
                'running_tests' => __('Running accessibility tests...', 'explainer-plugin'),
                'tests_complete' => __('Tests completed', 'explainer-plugin'),
                'tests_failed' => __('Some tests failed', 'explainer-plugin'),
                'all_passed' => __('All tests passed!', 'explainer-plugin')
            )
        ));
    }
    
    /**
     * Run comprehensive accessibility tests
     */
    public function run_accessibility_tests() {
        if (!wp_verify_nonce($_POST['nonce'], 'explainer_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $this->test_results = array();
        
        // Run all test suites
        $this->test_color_contrast();
        $this->test_aria_implementation();
        $this->test_keyboard_navigation();
        $this->test_semantic_structure();
        $this->test_form_accessibility();
        $this->test_focus_management();
        $this->test_screen_reader_support();
        $this->test_responsive_accessibility();
        
        // Store results
        update_option('explainer_accessibility_test_results', $this->test_results);
        update_option('explainer_accessibility_last_test', current_time('mysql'));
        
        wp_send_json_success(array(
            'results' => $this->test_results,
            'summary' => $this->get_test_summary()
        ));
    }
    
    /**
     * Test color contrast compliance
     */
    private function test_color_contrast() {
        $test_name = 'color_contrast';
        $this->test_results[$test_name] = array(
            'name' => __('Color Contrast', 'explainer-plugin'),
            'description' => __('Tests WCAG 2.1 AA color contrast requirements', 'explainer-plugin'),
            'status' => 'passed',
            'issues' => array(),
            'score' => 100
        );
        
        // Test toggle button contrast
        $toggle_bg = get_option('explainer_toggle_bg_color', '#0073aa');
        $toggle_text = get_option('explainer_toggle_text_color', '#ffffff');
        
        $toggle_ratio = $this->calculate_contrast_ratio($toggle_bg, $toggle_text);
        if ($toggle_ratio < self::WCAG_AA_CONTRAST_RATIO) {
            $this->test_results[$test_name]['issues'][] = array(
                'element' => 'Toggle Button',
                'issue' => sprintf(
                    __('Contrast ratio %s is below WCAG AA requirement of %s', 'explainer-plugin'),
                    round($toggle_ratio, 2),
                    self::WCAG_AA_CONTRAST_RATIO
                ),
                'severity' => 'error'
            );
            $this->test_results[$test_name]['status'] = 'failed';
            $this->test_results[$test_name]['score'] -= 30;
        }
        
        // Test tooltip contrast
        $tooltip_bg = get_option('explainer_tooltip_bg_color', '#333333');
        $tooltip_text = get_option('explainer_tooltip_text_color', '#ffffff');
        
        $tooltip_ratio = $this->calculate_contrast_ratio($tooltip_bg, $tooltip_text);
        if ($tooltip_ratio < self::WCAG_AA_CONTRAST_RATIO) {
            $this->test_results[$test_name]['issues'][] = array(
                'element' => 'Tooltip',
                'issue' => sprintf(
                    __('Contrast ratio %s is below WCAG AA requirement of %s', 'explainer-plugin'),
                    round($tooltip_ratio, 2),
                    self::WCAG_AA_CONTRAST_RATIO
                ),
                'severity' => 'error'
            );
            $this->test_results[$test_name]['status'] = 'failed';
            $this->test_results[$test_name]['score'] -= 30;
        }
        
        // Test focus indicator contrast
        $focus_color = '#0073aa';
        $focus_bg = '#ffffff';
        
        $focus_ratio = $this->calculate_contrast_ratio($focus_color, $focus_bg);
        if ($focus_ratio < self::WCAG_AA_CONTRAST_RATIO) {
            $this->test_results[$test_name]['issues'][] = array(
                'element' => 'Focus Indicators',
                'issue' => sprintf(
                    __('Focus indicator contrast ratio %s is below WCAG AA requirement', 'explainer-plugin'),
                    round($focus_ratio, 2)
                ),
                'severity' => 'warning'
            );
            $this->test_results[$test_name]['score'] -= 20;
        }
    }
    
    /**
     * Test ARIA implementation
     */
    private function test_aria_implementation() {
        $test_name = 'aria_implementation';
        $this->test_results[$test_name] = array(
            'name' => __('ARIA Implementation', 'explainer-plugin'),
            'description' => __('Tests proper use of ARIA attributes and landmarks', 'explainer-plugin'),
            'status' => 'passed',
            'issues' => array(),
            'score' => 100
        );
        
        // Check for required ARIA attributes in JavaScript files
        $js_files = array(
            EXPLAINER_PLUGIN_PATH . 'assets/js/explainer.js',
            EXPLAINER_PLUGIN_PATH . 'assets/js/tooltip.js'
        );
        
        foreach ($js_files as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                // Check for aria-label usage
                if (strpos($content, 'aria-label') === false) {
                    $this->test_results[$test_name]['issues'][] = array(
                        'element' => basename($file),
                        'issue' => __('Missing aria-label attributes', 'explainer-plugin'),
                        'severity' => 'warning'
                    );
                    $this->test_results[$test_name]['score'] -= 10;
                }
                
                // Check for aria-live usage
                if (strpos($content, 'aria-live') === false) {
                    $this->test_results[$test_name]['issues'][] = array(
                        'element' => basename($file),
                        'issue' => __('Missing aria-live regions for dynamic content', 'explainer-plugin'),
                        'severity' => 'warning'
                    );
                    $this->test_results[$test_name]['score'] -= 10;
                }
                
                // Check for role attributes
                if (strpos($content, 'setAttribute(\'role\'') === false) {
                    $this->test_results[$test_name]['issues'][] = array(
                        'element' => basename($file),
                        'issue' => __('Missing role attributes for semantic meaning', 'explainer-plugin'),
                        'severity' => 'warning'
                    );
                    $this->test_results[$test_name]['score'] -= 15;
                }
            }
        }
        
        if ($this->test_results[$test_name]['score'] < 80) {
            $this->test_results[$test_name]['status'] = 'warning';
        }
        
        if ($this->test_results[$test_name]['score'] < 60) {
            $this->test_results[$test_name]['status'] = 'failed';
        }
    }
    
    /**
     * Test keyboard navigation
     */
    private function test_keyboard_navigation() {
        $test_name = 'keyboard_navigation';
        $this->test_results[$test_name] = array(
            'name' => __('Keyboard Navigation', 'explainer-plugin'),
            'description' => __('Tests keyboard accessibility and navigation patterns', 'explainer-plugin'),
            'status' => 'passed',
            'issues' => array(),
            'score' => 100
        );
        
        // Check for keyboard event handlers
        $js_file = EXPLAINER_PLUGIN_PATH . 'assets/js/explainer.js';
        if (file_exists($js_file)) {
            $content = file_get_contents($js_file);
            
            // Check for tab navigation
            if (strpos($content, 'Tab') === false || strpos($content, 'tabindex') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Tab Navigation',
                    'issue' => __('Missing comprehensive tab navigation support', 'explainer-plugin'),
                    'severity' => 'error'
                );
                $this->test_results[$test_name]['status'] = 'failed';
                $this->test_results[$test_name]['score'] -= 30;
            }
            
            // Check for escape key handling
            if (strpos($content, 'Escape') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Escape Key',
                    'issue' => __('Missing escape key handling for modal dismissal', 'explainer-plugin'),
                    'severity' => 'error'
                );
                $this->test_results[$test_name]['status'] = 'failed';
                $this->test_results[$test_name]['score'] -= 25;
            }
            
            // Check for arrow key navigation
            if (strpos($content, 'Arrow') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Arrow Keys',
                    'issue' => __('Missing arrow key navigation for enhanced accessibility', 'explainer-plugin'),
                    'severity' => 'warning'
                );
                $this->test_results[$test_name]['score'] -= 15;
            }
            
            // Check for enter/space key handling
            if (strpos($content, 'Enter') === false || strpos($content, ' ') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Enter/Space Keys',
                    'issue' => __('Missing enter/space key activation for buttons', 'explainer-plugin'),
                    'severity' => 'error'
                );
                $this->test_results[$test_name]['status'] = 'failed';
                $this->test_results[$test_name]['score'] -= 20;
            }
        }
    }
    
    /**
     * Test semantic structure
     */
    private function test_semantic_structure() {
        $test_name = 'semantic_structure';
        $this->test_results[$test_name] = array(
            'name' => __('Semantic Structure', 'explainer-plugin'),
            'description' => __('Tests proper HTML semantic structure and landmarks', 'explainer-plugin'),
            'status' => 'passed',
            'issues' => array(),
            'score' => 100
        );
        
        // Check CSS for proper semantic styling
        $css_file = EXPLAINER_PLUGIN_PATH . 'assets/css/style.css';
        if (file_exists($css_file)) {
            $content = file_get_contents($css_file);
            
            // Check for focus styles
            if (strpos($content, ':focus') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Focus Styles',
                    'issue' => __('Missing focus indicator styles', 'explainer-plugin'),
                    'severity' => 'error'
                );
                $this->test_results[$test_name]['status'] = 'failed';
                $this->test_results[$test_name]['score'] -= 40;
            }
            
            // Check for reduced motion support
            if (strpos($content, 'prefers-reduced-motion') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Reduced Motion',
                    'issue' => __('Missing reduced motion preferences support', 'explainer-plugin'),
                    'severity' => 'warning'
                );
                $this->test_results[$test_name]['score'] -= 20;
            }
            
            // Check for high contrast support
            if (strpos($content, 'prefers-contrast') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'High Contrast',
                    'issue' => __('Missing high contrast mode support', 'explainer-plugin'),
                    'severity' => 'warning'
                );
                $this->test_results[$test_name]['score'] -= 15;
            }
        }
    }
    
    /**
     * Test form accessibility
     */
    private function test_form_accessibility() {
        $test_name = 'form_accessibility';
        $this->test_results[$test_name] = array(
            'name' => __('Form Accessibility', 'explainer-plugin'),
            'description' => __('Tests form elements for proper labeling and structure', 'explainer-plugin'),
            'status' => 'passed',
            'issues' => array(),
            'score' => 100
        );
        
        // Check admin template for form accessibility
        $template_file = EXPLAINER_PLUGIN_PATH . 'templates/admin-settings.php';
        if (file_exists($template_file)) {
            $content = file_get_contents($template_file);
            
            // Check for label associations
            if (strpos($content, 'for=') === false && strpos($content, '<label') !== false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Form Labels',
                    'issue' => __('Form inputs missing proper label associations', 'explainer-plugin'),
                    'severity' => 'error'
                );
                $this->test_results[$test_name]['status'] = 'failed';
                $this->test_results[$test_name]['score'] -= 30;
            }
            
            // Check for fieldset usage
            if (strpos($content, '<fieldset') === false && strpos($content, 'input') !== false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Form Grouping',
                    'issue' => __('Missing fieldset elements for form grouping', 'explainer-plugin'),
                    'severity' => 'warning'
                );
                $this->test_results[$test_name]['score'] -= 15;
            }
            
            // Check for required field indicators
            if (strpos($content, 'required') === false && strpos($content, 'aria-required') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Required Fields',
                    'issue' => __('Missing required field indicators', 'explainer-plugin'),
                    'severity' => 'warning'
                );
                $this->test_results[$test_name]['score'] -= 10;
            }
        }
    }
    
    /**
     * Test focus management
     */
    private function test_focus_management() {
        $test_name = 'focus_management';
        $this->test_results[$test_name] = array(
            'name' => __('Focus Management', 'explainer-plugin'),
            'description' => __('Tests proper focus management and restoration', 'explainer-plugin'),
            'status' => 'passed',
            'issues' => array(),
            'score' => 100
        );
        
        $js_file = EXPLAINER_PLUGIN_PATH . 'assets/js/explainer.js';
        if (file_exists($js_file)) {
            $content = file_get_contents($js_file);
            
            // Check for focus management functions
            if (strpos($content, 'manageFocus') === false && strpos($content, '.focus()') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Focus Management',
                    'issue' => __('Missing focus management functionality', 'explainer-plugin'),
                    'severity' => 'error'
                );
                $this->test_results[$test_name]['status'] = 'failed';
                $this->test_results[$test_name]['score'] -= 40;
            }
            
            // Check for focus restoration
            if (strpos($content, 'lastFocusedElement') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Focus Restoration',
                    'issue' => __('Missing focus restoration after modal close', 'explainer-plugin'),
                    'severity' => 'warning'
                );
                $this->test_results[$test_name]['score'] -= 25;
            }
            
            // Check for focus trapping
            if (strpos($content, 'focusableElements') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Focus Trapping',
                    'issue' => __('Missing focus trapping in modal dialogs', 'explainer-plugin'),
                    'severity' => 'warning'
                );
                $this->test_results[$test_name]['score'] -= 20;
            }
        }
    }
    
    /**
     * Test screen reader support
     */
    private function test_screen_reader_support() {
        $test_name = 'screen_reader_support';
        $this->test_results[$test_name] = array(
            'name' => __('Screen Reader Support', 'explainer-plugin'),
            'description' => __('Tests screen reader compatibility and announcements', 'explainer-plugin'),
            'status' => 'passed',
            'issues' => array(),
            'score' => 100
        );
        
        $js_file = EXPLAINER_PLUGIN_PATH . 'assets/js/explainer.js';
        if (file_exists($js_file)) {
            $content = file_get_contents($js_file);
            
            // Check for screen reader announcements
            if (strpos($content, 'announceToScreenReader') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Screen Reader Announcements',
                    'issue' => __('Missing screen reader announcement functionality', 'explainer-plugin'),
                    'severity' => 'error'
                );
                $this->test_results[$test_name]['status'] = 'failed';
                $this->test_results[$test_name]['score'] -= 35;
            }
            
            // Check for sr-only class usage
            if (strpos($content, 'sr-only') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Screen Reader Only Text',
                    'issue' => __('Missing screen reader only text elements', 'explainer-plugin'),
                    'severity' => 'warning'
                );
                $this->test_results[$test_name]['score'] -= 20;
            }
            
            // Check for dynamic content announcements
            if (strpos($content, 'aria-live') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Live Regions',
                    'issue' => __('Missing aria-live regions for dynamic content', 'explainer-plugin'),
                    'severity' => 'warning'
                );
                $this->test_results[$test_name]['score'] -= 25;
            }
        }
    }
    
    /**
     * Test responsive accessibility
     */
    private function test_responsive_accessibility() {
        $test_name = 'responsive_accessibility';
        $this->test_results[$test_name] = array(
            'name' => __('Responsive Accessibility', 'explainer-plugin'),
            'description' => __('Tests accessibility across different screen sizes and devices', 'explainer-plugin'),
            'status' => 'passed',
            'issues' => array(),
            'score' => 100
        );
        
        $css_file = EXPLAINER_PLUGIN_PATH . 'assets/css/style.css';
        if (file_exists($css_file)) {
            $content = file_get_contents($css_file);
            
            // Check for mobile-specific accessibility
            if (strpos($content, '@media') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Responsive Design',
                    'issue' => __('Missing responsive design styles', 'explainer-plugin'),
                    'severity' => 'warning'
                );
                $this->test_results[$test_name]['score'] -= 30;
            }
            
            // Check for touch-friendly targets
            if (strpos($content, 'touch-action') === false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Touch Targets',
                    'issue' => __('Missing touch-friendly target optimizations', 'explainer-plugin'),
                    'severity' => 'warning'
                );
                $this->test_results[$test_name]['score'] -= 20;
            }
            
            // Check for zoom support
            if (strpos($content, 'zoom') !== false && strpos($content, 'user-scalable=no') !== false) {
                $this->test_results[$test_name]['issues'][] = array(
                    'element' => 'Zoom Support',
                    'issue' => __('Zoom functionality may be disabled', 'explainer-plugin'),
                    'severity' => 'error'
                );
                $this->test_results[$test_name]['status'] = 'failed';
                $this->test_results[$test_name]['score'] -= 40;
            }
        }
    }
    
    /**
     * Get test results
     */
    public function get_test_results() {
        if (!wp_verify_nonce($_POST['nonce'], 'explainer_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $results = get_option('explainer_accessibility_test_results', array());
        $last_test = get_option('explainer_accessibility_last_test', '');
        
        wp_send_json_success(array(
            'results' => $results,
            'last_test' => $last_test,
            'summary' => $this->get_test_summary($results)
        ));
    }
    
    /**
     * Get test summary
     */
    private function get_test_summary($results = null) {
        if ($results === null) {
            $results = $this->test_results;
        }
        
        $total_tests = count($results);
        $passed_tests = 0;
        $failed_tests = 0;
        $warning_tests = 0;
        $total_score = 0;
        $total_issues = 0;
        
        foreach ($results as $test) {
            switch ($test['status']) {
                case 'passed':
                    $passed_tests++;
                    break;
                case 'failed':
                    $failed_tests++;
                    break;
                case 'warning':
                    $warning_tests++;
                    break;
            }
            
            $total_score += $test['score'];
            $total_issues += count($test['issues']);
        }
        
        $average_score = $total_tests > 0 ? round($total_score / $total_tests, 1) : 0;
        
        return array(
            'total_tests' => $total_tests,
            'passed_tests' => $passed_tests,
            'failed_tests' => $failed_tests,
            'warning_tests' => $warning_tests,
            'average_score' => $average_score,
            'total_issues' => $total_issues,
            'compliance_level' => $this->get_compliance_level($average_score)
        );
    }
    
    /**
     * Get compliance level based on score
     */
    private function get_compliance_level($score) {
        if ($score >= 95) {
            return array(
                'level' => 'WCAG 2.1 AAA',
                'color' => '#27ae60',
                'description' => __('Excellent accessibility compliance', 'explainer-plugin')
            );
        } elseif ($score >= 85) {
            return array(
                'level' => 'WCAG 2.1 AA',
                'color' => '#2980b9',
                'description' => __('Good accessibility compliance', 'explainer-plugin')
            );
        } elseif ($score >= 70) {
            return array(
                'level' => 'WCAG 2.1 A',
                'color' => '#f39c12',
                'description' => __('Basic accessibility compliance', 'explainer-plugin')
            );
        } else {
            return array(
                'level' => 'Non-compliant',
                'color' => '#e74c3c',
                'description' => __('Accessibility improvements needed', 'explainer-plugin')
            );
        }
    }
    
    /**
     * Calculate contrast ratio between two colors
     */
    private function calculate_contrast_ratio($color1, $color2) {
        $l1 = $this->get_relative_luminance($color1);
        $l2 = $this->get_relative_luminance($color2);
        
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);
        
        return ($lighter + 0.05) / ($darker + 0.05);
    }
    
    /**
     * Get relative luminance of a color
     */
    private function get_relative_luminance($color) {
        // Convert hex to RGB
        $color = ltrim($color, '#');
        
        if (strlen($color) === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }
        
        $r = hexdec(substr($color, 0, 2)) / 255;
        $g = hexdec(substr($color, 2, 2)) / 255;
        $b = hexdec(substr($color, 4, 2)) / 255;
        
        // Apply gamma correction
        $r = ($r <= 0.03928) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = ($g <= 0.03928) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = ($b <= 0.03928) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
        
        // Calculate relative luminance
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
    
    /**
     * Render accessibility testing interface
     */
    public function render_testing_interface() {
        if (!current_user_can('manage_options')) {
            return '<p>' . __('Access denied.', 'explainer-plugin') . '</p>';
        }
        
        $last_test = get_option('explainer_accessibility_last_test', '');
        $results = get_option('explainer_accessibility_test_results', array());
        
        ob_start();
        ?>
        <div class="explainer-accessibility-testing">
            <h3><?php _e('Accessibility Testing', 'explainer-plugin'); ?></h3>
            <p><?php _e('Run comprehensive accessibility tests to ensure WCAG 2.1 AA compliance.', 'explainer-plugin'); ?></p>
            
            <div class="test-controls">
                <button type="button" id="run-accessibility-tests" class="button button-primary">
                    <?php _e('Run Accessibility Tests', 'explainer-plugin'); ?>
                </button>
                
                <?php if ($last_test): ?>
                <p class="description">
                    <?php printf(
                        __('Last tested: %s', 'explainer-plugin'),
                        wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_test))
                    ); ?>
                </p>
                <?php endif; ?>
            </div>
            
            <div id="accessibility-test-results" style="margin-top: 20px;">
                <?php if (!empty($results)): ?>
                    <?php echo $this->render_test_results($results); ?>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .explainer-accessibility-testing .test-result {
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
            padding: 15px;
        }
        
        .test-result.passed {
            border-left: 4px solid #27ae60;
            background: #f9fff9;
        }
        
        .test-result.warning {
            border-left: 4px solid #f39c12;
            background: #fffbf0;
        }
        
        .test-result.failed {
            border-left: 4px solid #e74c3c;
            background: #fff5f5;
        }
        
        .test-result h4 {
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .test-score {
            font-weight: normal;
            font-size: 0.9em;
            padding: 2px 8px;
            border-radius: 12px;
            background: #eee;
        }
        
        .test-issues {
            margin-top: 10px;
        }
        
        .test-issue {
            padding: 8px 12px;
            margin: 5px 0;
            border-radius: 3px;
            font-size: 0.9em;
        }
        
        .test-issue.error {
            background: #ffeaea;
            border-left: 3px solid #e74c3c;
        }
        
        .test-issue.warning {
            background: #fff3cd;
            border-left: 3px solid #f39c12;
        }
        </style>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render test results
     */
    private function render_test_results($results) {
        $summary = $this->get_test_summary($results);
        $compliance = $this->get_compliance_level($summary['average_score']);
        
        ob_start();
        ?>
        <div class="test-summary" style="background: #f0f0f1; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <h4><?php _e('Test Summary', 'explainer-plugin'); ?></h4>
            <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                <div>
                    <strong><?php _e('Compliance Level:', 'explainer-plugin'); ?></strong>
                    <span style="color: <?php echo esc_attr($compliance['color']); ?>; font-weight: bold;">
                        <?php echo esc_html($compliance['level']); ?>
                    </span>
                </div>
                <div>
                    <strong><?php _e('Average Score:', 'explainer-plugin'); ?></strong>
                    <?php echo esc_html($summary['average_score']); ?>%
                </div>
                <div>
                    <strong><?php _e('Tests Passed:', 'explainer-plugin'); ?></strong>
                    <?php echo esc_html($summary['passed_tests']); ?>/<?php echo esc_html($summary['total_tests']); ?>
                </div>
                <div>
                    <strong><?php _e('Issues Found:', 'explainer-plugin'); ?></strong>
                    <?php echo esc_html($summary['total_issues']); ?>
                </div>
            </div>
            <p style="margin: 10px 0 0 0; color: #666;">
                <?php echo esc_html($compliance['description']); ?>
            </p>
        </div>
        
        <div class="test-results">
            <?php foreach ($results as $test): ?>
                <div class="test-result <?php echo esc_attr($test['status']); ?>">
                    <h4>
                        <?php echo esc_html($test['name']); ?>
                        <span class="test-score"><?php echo esc_html($test['score']); ?>%</span>
                    </h4>
                    <p><?php echo esc_html($test['description']); ?></p>
                    
                    <?php if (!empty($test['issues'])): ?>
                        <div class="test-issues">
                            <strong><?php _e('Issues:', 'explainer-plugin'); ?></strong>
                            <?php foreach ($test['issues'] as $issue): ?>
                                <div class="test-issue <?php echo esc_attr($issue['severity']); ?>">
                                    <strong><?php echo esc_html($issue['element']); ?>:</strong>
                                    <?php echo esc_html($issue['issue']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
}

// Initialize accessibility testing
new Explainer_Accessibility_Testing();