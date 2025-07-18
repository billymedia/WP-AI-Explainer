/**
 * Accessibility Testing Interface
 * Handles the admin interface for running accessibility tests
 */

(function($) {
    'use strict';
    
    let isTestRunning = false;
    
    $(document).ready(function() {
        initializeAccessibilityTesting();
    });
    
    function initializeAccessibilityTesting() {
        $('#run-accessibility-tests').on('click', runAccessibilityTests);
        
        // Auto-refresh results every 30 seconds when visible
        if ($('#accessibility-test-results').length) {
            setInterval(checkForUpdatedResults, 30000);
        }
    }
    
    function runAccessibilityTests() {
        if (isTestRunning) {
            return;
        }
        
        isTestRunning = true;
        const $button = $('#run-accessibility-tests');
        const $results = $('#accessibility-test-results');
        
        // Update button state
        $button.prop('disabled', true).text(explainerAccessibilityTest.strings.running_tests);
        
        // Show loading indicator
        $results.html(`
            <div class="accessibility-test-loading" style="text-align: center; padding: 40px;">
                <div class="spinner is-active" style="float: none; margin: 0 auto;"></div>
                <p style="margin-top: 10px;">${explainerAccessibilityTest.strings.running_tests}</p>
            </div>
        `);
        
        // Make AJAX request
        $.ajax({
            url: explainerAccessibilityTest.ajaxurl,
            type: 'POST',
            data: {
                action: 'explainer_run_accessibility_tests',
                nonce: explainerAccessibilityTest.nonce
            },
            timeout: 60000, // 60 second timeout
            success: function(response) {
                if (response.success) {
                    displayTestResults(response.data.results, response.data.summary);
                    showNotification(explainerAccessibilityTest.strings.tests_complete, 'success');
                } else {
                    showNotification('Test failed: ' + (response.data || 'Unknown error'), 'error');
                    $results.html('<p>Error running tests. Please try again.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Accessibility test error:', error);
                showNotification('Error running tests: ' + error, 'error');
                $results.html('<p>Error running tests. Please check the console for details.</p>');
            },
            complete: function() {
                // Reset button state
                $button.prop('disabled', false).text('Run Accessibility Tests');
                isTestRunning = false;
            }
        });
    }
    
    function displayTestResults(results, summary) {
        const $results = $('#accessibility-test-results');
        
        let html = buildSummaryHTML(summary);
        html += '<div class="test-results">';
        
        Object.keys(results).forEach(function(testKey) {
            const test = results[testKey];
            html += buildTestResultHTML(test);
        });
        
        html += '</div>';
        
        $results.html(html);
        
        // Add click handlers for expandable details
        $results.find('.test-result h4').on('click', function() {
            $(this).next('.test-details').slideToggle();
        });
        
        // Add accessibility attributes
        $results.find('.test-result').each(function() {
            const $this = $(this);
            const testName = $this.find('h4').text();
            const status = $this.hasClass('passed') ? 'passed' : 
                          $this.hasClass('warning') ? 'warning' : 'failed';
            
            $this.attr({
                'role': 'region',
                'aria-labelledby': 'test-' + testName.replace(/\s+/g, '-').toLowerCase(),
                'aria-expanded': 'false'
            });
            
            $this.find('h4').attr({
                'id': 'test-' + testName.replace(/\s+/g, '-').toLowerCase(),
                'role': 'button',
                'tabindex': '0',
                'aria-label': `${testName} test ${status}. Click to expand details.`
            });
        });
        
        // Keyboard navigation for test results
        $results.find('.test-result h4').on('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                $(this).click();
            }
        });
    }
    
    function buildSummaryHTML(summary) {
        const compliance = summary.compliance_level;
        const statusIcon = getStatusIcon(compliance.level);
        
        return `
            <div class="test-summary" style="background: #f0f0f1; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 10px;">
                    ${statusIcon}
                    Accessibility Test Summary
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 10px;">
                    <div>
                        <strong>Compliance Level:</strong><br>
                        <span style="color: ${compliance.color}; font-weight: bold; font-size: 1.1em;">
                            ${compliance.level}
                        </span>
                    </div>
                    <div>
                        <strong>Average Score:</strong><br>
                        <span style="font-size: 1.2em; font-weight: bold;">
                            ${summary.average_score}%
                        </span>
                    </div>
                    <div>
                        <strong>Tests Status:</strong><br>
                        <span style="color: #27ae60;">${summary.passed_tests} passed</span>, 
                        <span style="color: #f39c12;">${summary.warning_tests} warnings</span>, 
                        <span style="color: #e74c3c;">${summary.failed_tests} failed</span>
                    </div>
                    <div>
                        <strong>Issues Found:</strong><br>
                        <span style="font-size: 1.1em;">${summary.total_issues}</span>
                    </div>
                </div>
                <p style="margin: 0; color: #666; font-style: italic;">
                    ${compliance.description}
                </p>
                ${summary.total_issues > 0 ? buildRecommendationsHTML(summary) : ''}
            </div>
        `;
    }
    
    function buildTestResultHTML(test) {
        const statusIcon = getStatusIcon(test.status);
        const issuesHTML = test.issues.length > 0 ? buildIssuesHTML(test.issues) : '';
        
        return `
            <div class="test-result ${test.status}" style="border: 1px solid #ddd; border-radius: 4px; margin: 10px 0; padding: 15px; border-left: 4px solid ${getStatusColor(test.status)}; background: ${getStatusBackground(test.status)};">
                <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        ${statusIcon}
                        ${test.name}
                    </span>
                    <span class="test-score" style="font-weight: normal; font-size: 0.9em; padding: 2px 8px; border-radius: 12px; background: #eee;">
                        ${test.score}%
                    </span>
                </h4>
                <div class="test-details" style="display: none;">
                    <p style="margin: 0 0 10px 0; color: #666;">
                        ${test.description}
                    </p>
                    ${issuesHTML}
                    ${buildRecommendationsForTest(test)}
                </div>
            </div>
        `;
    }
    
    function buildIssuesHTML(issues) {
        let html = '<div class="test-issues" style="margin-top: 15px;"><strong>Issues Found:</strong>';
        
        issues.forEach(function(issue) {
            const iconColor = issue.severity === 'error' ? '#e74c3c' : '#f39c12';
            const bgColor = issue.severity === 'error' ? '#ffeaea' : '#fff3cd';
            const borderColor = issue.severity === 'error' ? '#e74c3c' : '#f39c12';
            
            html += `
                <div class="test-issue ${issue.severity}" style="padding: 12px; margin: 8px 0; border-radius: 4px; background: ${bgColor}; border-left: 3px solid ${borderColor};">
                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                        <span style="color: ${iconColor}; font-weight: bold; min-width: 20px;">
                            ${issue.severity === 'error' ? '❌' : '⚠️'}
                        </span>
                        <div>
                            <strong style="display: block; margin-bottom: 4px;">${issue.element}:</strong>
                            <span>${issue.issue}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }
    
    function buildRecommendationsHTML(summary) {
        let recommendations = [];
        
        if (summary.failed_tests > 0) {
            recommendations.push('Address critical accessibility issues marked as failed');
        }
        
        if (summary.warning_tests > 0) {
            recommendations.push('Review and fix warning-level accessibility issues');
        }
        
        if (summary.average_score < 85) {
            recommendations.push('Consider implementing additional accessibility features');
        }
        
        if (recommendations.length === 0) {
            return '';
        }
        
        let html = '<div style="background: #e8f4fd; border: 1px solid #2980b9; border-radius: 4px; padding: 12px; margin-top: 15px;">';
        html += '<h5 style="margin: 0 0 8px 0; color: #2980b9;">📋 Recommendations:</h5>';
        html += '<ul style="margin: 0; padding-left: 20px;">';
        
        recommendations.forEach(function(rec) {
            html += `<li style="margin-bottom: 4px;">${rec}</li>`;
        });
        
        html += '</ul></div>';
        return html;
    }
    
    function buildRecommendationsForTest(test) {
        if (test.status === 'passed') {
            return '<div style="background: #d4edda; border: 1px solid #27ae60; border-radius: 4px; padding: 10px; margin-top: 10px; color: #155724;">✅ This test passed successfully!</div>';
        }
        
        const recommendations = getTestRecommendations(test.name, test.issues);
        
        if (recommendations.length === 0) {
            return '';
        }
        
        let html = '<div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 12px; margin-top: 10px;">';
        html += '<h6 style="margin: 0 0 8px 0; color: #495057;">💡 How to Fix:</h6>';
        html += '<ul style="margin: 0; padding-left: 20px; color: #6c757d;">';
        
        recommendations.forEach(function(rec) {
            html += `<li style="margin-bottom: 4px;">${rec}</li>`;
        });
        
        html += '</ul></div>';
        return html;
    }
    
    function getTestRecommendations(testName, issues) {
        const recommendations = [];
        
        switch (testName) {
            case 'Color Contrast':
                recommendations.push('Adjust background and text colors to meet WCAG AA contrast ratios');
                recommendations.push('Use online contrast checkers to validate color combinations');
                recommendations.push('Consider providing a high contrast theme option');
                break;
                
            case 'ARIA Implementation':
                recommendations.push('Add aria-label attributes to interactive elements');
                recommendations.push('Implement aria-live regions for dynamic content updates');
                recommendations.push('Use semantic HTML5 elements with proper roles');
                break;
                
            case 'Keyboard Navigation':
                recommendations.push('Implement comprehensive tab navigation');
                recommendations.push('Add escape key handling for modal dismissal');
                recommendations.push('Provide arrow key navigation where appropriate');
                break;
                
            case 'Focus Management':
                recommendations.push('Implement proper focus restoration after modal close');
                recommendations.push('Add focus trapping in modal dialogs');
                recommendations.push('Ensure all interactive elements are keyboard accessible');
                break;
                
            case 'Screen Reader Support':
                recommendations.push('Add screen reader announcements for state changes');
                recommendations.push('Use aria-live regions for dynamic content');
                recommendations.push('Provide descriptive text for screen readers');
                break;
                
            default:
                recommendations.push('Review the specific issues listed above');
                recommendations.push('Consult WCAG 2.1 guidelines for detailed requirements');
                break;
        }
        
        return recommendations;
    }
    
    function getStatusIcon(status) {
        switch (status) {
            case 'passed':
            case 'WCAG 2.1 AAA':
            case 'WCAG 2.1 AA':
                return '<span style="color: #27ae60; font-size: 1.2em;">✅</span>';
            case 'warning':
            case 'WCAG 2.1 A':
                return '<span style="color: #f39c12; font-size: 1.2em;">⚠️</span>';
            case 'failed':
            case 'Non-compliant':
                return '<span style="color: #e74c3c; font-size: 1.2em;">❌</span>';
            default:
                return '<span style="color: #95a5a6; font-size: 1.2em;">❔</span>';
        }
    }
    
    function getStatusColor(status) {
        switch (status) {
            case 'passed': return '#27ae60';
            case 'warning': return '#f39c12';
            case 'failed': return '#e74c3c';
            default: return '#95a5a6';
        }
    }
    
    function getStatusBackground(status) {
        switch (status) {
            case 'passed': return '#f9fff9';
            case 'warning': return '#fffbf0';
            case 'failed': return '#fff5f5';
            default: return '#f8f9fa';
        }
    }
    
    function showNotification(message, type) {
        const bgColor = type === 'success' ? '#d4edda' : 
                       type === 'warning' ? '#fff3cd' : '#f8d7da';
        const borderColor = type === 'success' ? '#c3e6cb' : 
                           type === 'warning' ? '#ffeaa7' : '#f5c6cb';
        const textColor = type === 'success' ? '#155724' : 
                         type === 'warning' ? '#856404' : '#721c24';
        
        const $notification = $(`
            <div class="accessibility-notification" style="
                position: fixed;
                top: 32px;
                right: 20px;
                background: ${bgColor};
                border: 1px solid ${borderColor};
                color: ${textColor};
                padding: 12px 20px;
                border-radius: 4px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                z-index: 100000;
                max-width: 400px;
                font-weight: 500;
            ">
                ${message}
                <button type="button" style="
                    background: none;
                    border: none;
                    color: inherit;
                    font-size: 16px;
                    margin-left: 10px;
                    cursor: pointer;
                    padding: 0;
                    line-height: 1;
                " aria-label="Close notification">&times;</button>
            </div>
        `);
        
        $('body').append($notification);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Click to close
        $notification.find('button').on('click', function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Announce to screen readers
        if (type === 'success') {
            announceToScreenReader(message);
        }
    }
    
    function checkForUpdatedResults() {
        if (isTestRunning) {
            return;
        }
        
        // Only check if the results container is visible
        if (!$('#accessibility-test-results').is(':visible')) {
            return;
        }
        
        $.ajax({
            url: explainerAccessibilityTest.ajaxurl,
            type: 'POST',
            data: {
                action: 'explainer_get_test_results',
                nonce: explainerAccessibilityTest.nonce
            },
            success: function(response) {
                if (response.success && response.data.results && Object.keys(response.data.results).length > 0) {
                    // Only update if we don't have current results
                    if ($('#accessibility-test-results .test-results').length === 0) {
                        displayTestResults(response.data.results, response.data.summary);
                    }
                }
            },
            error: function() {
                // Silently fail for background requests
            }
        });
    }
    
    function announceToScreenReader(message) {
        const $announcement = $('<div>', {
            'aria-live': 'polite',
            'aria-atomic': 'true',
            'class': 'screen-reader-text',
            'style': 'position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;',
            'text': message
        });
        
        $('body').append($announcement);
        
        setTimeout(function() {
            $announcement.remove();
        }, 1000);
    }
    
})(jQuery);