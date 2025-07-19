/**
 * Admin JavaScript for AI Explainer Plugin
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize admin functionality
        initializeAdmin();
        
        // Initialize form validation
        initializeFormValidation();
        
        // Initialize color pickers
        initializeColorPickers();
        
    });
    
    /**
     * Initialize admin functionality
     */
    function initializeAdmin() {
        // Test API key button
        $('#test-api-key').on('click', testApiKey);
        
        // Clear cache button
        $('#clear-cache').on('click', clearCache);
        
        // Debug log functionality
        $('#view-debug-logs').on('click', viewDebugLogs);
        $('#delete-debug-logs').on('click', deleteDebugLogs);
        
        // Form validation
        $('form').on('submit', validateForm);
        
        // API key input handling
        $('input[name="explainer_api_key"]').on('input', handleApiKeyInput);
        $('input[name="explainer_claude_api_key"]').on('input', handleClaudeApiKeyInput);
        
        // Provider selection handling
        $('#explainer_api_provider').on('change', handleProviderSelection);
        
        // Initialize provider selection on page load
        initializeProviderSelection();
        
        // Cache duration validation
        $('input[name="explainer_cache_duration"]').on('input', validateCacheDuration);
        
        // Rate limit validation
        $('input[name="explainer_rate_limit_logged"], input[name="explainer_rate_limit_anonymous"]').on('input', validateRateLimit);
        
        // Reset settings button
        $('#reset-settings').on('click', resetSettings);
        
        // Re-enable plugin button (for auto-disabled state)
        $('.explainer-reenable-btn-settings').on('click', reEnablePlugin);
        
        
        // Prompt functionality
        $('#reset-prompt-default').on('click', resetPromptToDefault);
        $('textarea[name="explainer_custom_prompt"]').on('input', validateCustomPrompt);
        
        // Blocked words functionality
        $('#explainer_blocked_words').on('input', updateBlockedWordsCount);
        $('#clear-blocked-words').on('click', clearBlockedWords);
        $('#load-default-blocked-words').on('click', loadDefaultBlockedWords);
        
        // Initialize blocked words count
        updateBlockedWordsCount();
    }
    
    /**
     * Test API key for current provider
     */
    function testApiKey() {
        var button = $(this);
        var originalText = button.text();
        
        // Get the current provider
        var provider = $('#explainer_api_provider').val();
        
        // Get the appropriate API key
        var apiKey;
        if (provider === 'openai') {
            apiKey = $('input[name="explainer_api_key"]').val().trim();
        } else if (provider === 'claude') {
            apiKey = $('input[name="explainer_claude_api_key"]').val().trim();
        }
        
        // Validate API key before testing
        if (!apiKey) {
            showMessage('Please enter an API key first.', 'error');
            return;
        }
        
        // Validate based on provider
        var isValid = false;
        if (provider === 'openai') {
            isValid = isValidOpenAIApiKey(apiKey);
        } else if (provider === 'claude') {
            isValid = isValidClaudeApiKey(apiKey);
        }
        
        if (!isValid) {
            var providerName = provider === 'openai' ? 'OpenAI' : 'Claude';
            showMessage('Invalid ' + providerName + ' API key format.', 'error');
            return;
        }
        
        // Show loading state
        button.text('Testing...').prop('disabled', true);
        
        // Clear previous messages
        $('#admin-messages').empty();
        
        $.ajax({
            url: explainerAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'explainer_test_api_key',
                api_key: apiKey,
                provider: provider,
                nonce: explainerAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('Connection error. Please try again.', 'error');
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    }
    
    /**
     * Clear cache
     */
    function clearCache() {
        var button = $(this);
        var originalText = button.text();
        
        if (!confirm('Are you sure you want to clear the cache?')) {
            return;
        }
        
        // Show loading state
        button.text('Clearing...').prop('disabled', true);
        
        // Clear previous messages
        $('#admin-messages').empty();
        
        $.ajax({
            url: explainerAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'explainer_clear_cache',
                nonce: explainerAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('Connection error. Please try again.', 'error');
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    }
    
    /**
     * View debug logs
     */
    function viewDebugLogs() {
        var button = $(this);
        var originalText = button.text();
        
        // Show loading state
        button.text('Loading...').prop('disabled', true);
        
        // Clear previous messages
        $('#admin-messages').empty();
        
        $.ajax({
            url: explainerAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'explainer_view_debug_logs',
                nonce: explainerAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayDebugLogs(response.data);
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('Connection error. Please try again.', 'error');
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    }
    
    /**
     * Delete debug logs
     */
    function deleteDebugLogs() {
        if (!confirm('Are you sure you want to delete all debug logs?')) {
            return;
        }
        
        var button = $(this);
        var originalText = button.text();
        
        // Show loading state
        button.text('Deleting...').prop('disabled', true);
        
        // Clear previous messages
        $('#admin-messages').empty();
        
        $.ajax({
            url: explainerAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'explainer_delete_debug_logs',
                nonce: explainerAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    // Clear the log viewer
                    $('#debug-logs-viewer').hide().find('pre').text('No logs available.');
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('Connection error. Please try again.', 'error');
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    }
    
    /**
     * Display debug logs
     */
    function displayDebugLogs(data) {
        var viewer = $('#debug-logs-viewer');
        
        if (data.logs && data.logs.length > 0) {
            var html = '<pre style="font-family: monospace; font-size: 12px; white-space: pre-wrap;">';
            data.logs.forEach(function(log) {
                html += '[' + log.timestamp + '] ' + log.level + ': ' + log.message + '\n';
            });
            html += '</pre>';
            viewer.html(html).show();
        } else {
            viewer.html('<p>No debug logs found.</p>').show();
        }
    }
    
    /**
     * Validate form before submission
     */
    function validateForm(e) {
        var errors = [];
        
        // Validate API keys based on provider
        var provider = $('#explainer_api_provider').val();
        
        if (provider === 'openai') {
            var apiKey = $('input[name="explainer_api_key"]').val();
            if (apiKey && !isValidOpenAIApiKey(apiKey)) {
                errors.push('Invalid OpenAI API key format. Keys should start with "sk-" and contain only alphanumeric characters, hyphens, and underscores.');
            }
        } else if (provider === 'claude') {
            var claudeKey = $('input[name="explainer_claude_api_key"]').val();
            if (claudeKey && !isValidClaudeApiKey(claudeKey)) {
                errors.push('Invalid Claude API key format. Keys should start with "sk-ant-" and contain only alphanumeric characters, hyphens, and underscores.');
            }
        }
        
        // Validate cache duration
        var cacheDuration = parseInt($('input[name="explainer_cache_duration"]').val());
        if (cacheDuration < 1 || cacheDuration > 168) {
            errors.push('Cache duration must be between 1 and 168 hours.');
        }
        
        // Validate rate limits
        var rateLimitLogged = parseInt($('input[name="explainer_rate_limit_logged"]').val());
        var rateLimitAnon = parseInt($('input[name="explainer_rate_limit_anonymous"]').val());
        
        if (rateLimitLogged < 1 || rateLimitLogged > 100) {
            errors.push('Rate limit for logged users must be between 1 and 100.');
        }
        
        if (rateLimitAnon < 1 || rateLimitAnon > 50) {
            errors.push('Rate limit for anonymous users must be between 1 and 50.');
        }
        
        // Validate custom prompt
        var customPrompt = $('textarea[name="explainer_custom_prompt"]').val();
        if (customPrompt.indexOf('{{snippet}}') === -1) {
            errors.push('Custom prompt must contain {{snippet}} placeholder.');
        }
        
        if (customPrompt.length > 500) {
            errors.push('Custom prompt cannot exceed 500 characters.');
        }
        
        if (customPrompt.length < 10) {
            errors.push('Custom prompt must be at least 10 characters.');
        }
        
        if (errors.length > 0) {
            e.preventDefault();
            showMessage(errors.join('<br>'), 'error');
            return false;
        }
        
        return true;
    }
    
    /**
     * Handle OpenAI API key input
     */
    function handleApiKeyInput() {
        var apiKey = $(this).val();
        var fieldName = $(this).attr('name');
        var feedback = $(this).siblings('.api-key-feedback[data-field="' + fieldName + '"]');
        
        // Remove any existing feedback elements for this field
        $(this).siblings('.api-key-feedback').remove();
        
        if (feedback.length === 0) {
            feedback = $('<div class="api-key-feedback" data-field="' + fieldName + '"></div>');
            $(this).after(feedback);
        }
        
        if (apiKey.length === 0) {
            feedback.empty();
            return;
        }
        
        if (isValidOpenAIApiKey(apiKey)) {
            feedback.html('').removeClass('error').addClass('success');
        } else {
            feedback.html('<span class="invalid">✗ Invalid OpenAI API key format</span>').removeClass('success').addClass('error');
        }
    }
    
    /**
     * Handle Claude API key input
     */
    function handleClaudeApiKeyInput() {
        var apiKey = $(this).val();
        var fieldName = $(this).attr('name');
        var feedback = $(this).siblings('.api-key-feedback[data-field="' + fieldName + '"]');
        
        // Remove any existing feedback elements for this field
        $(this).siblings('.api-key-feedback').remove();
        
        if (feedback.length === 0) {
            feedback = $('<div class="api-key-feedback" data-field="' + fieldName + '"></div>');
            $(this).after(feedback);
        }
        
        if (apiKey.length === 0) {
            feedback.empty();
            return;
        }
        
        if (isValidClaudeApiKey(apiKey)) {
            feedback.html('').removeClass('error').addClass('success');
        } else {
            feedback.html('<span class="invalid">✗ Invalid Claude API key format</span>').removeClass('success').addClass('error');
        }
    }
    
    /**
     * Handle provider selection
     */
    function handleProviderSelection() {
        var provider = $(this).val();
        
        // Hide all provider-specific fields
        $('.api-key-row').hide();
        
        // Hide all model optgroups and disable their options
        $('#explainer_api_model optgroup').hide();
        $('#explainer_api_model option').prop('disabled', true);
        
        // Show relevant fields and models based on provider
        if (provider === 'openai') {
            $('.openai-fields').show();
            $('#explainer_api_model .openai-models').show();
            $('#explainer_api_model .openai-models option').prop('disabled', false);
            
            // Set default OpenAI model if current model is not compatible
            var currentModel = $('#explainer_api_model').val();
            if (!currentModel || currentModel.indexOf('claude-') === 0) {
                $('#explainer_api_model').val('gpt-3.5-turbo');
            }
            
            // Remove Claude body class, add OpenAI class
            $('body').removeClass('claude-provider').addClass('openai-provider');
            
        } else if (provider === 'claude') {
            $('.claude-fields').show();
            $('#explainer_api_model .claude-models').show();
            $('#explainer_api_model .claude-models option').prop('disabled', false);
            
            // Set default Claude model if current model is not compatible
            var currentModel = $('#explainer_api_model').val();
            if (!currentModel || currentModel.indexOf('gpt-') === 0 || currentModel === 'gpt-3.5-turbo' || currentModel === 'gpt-4' || currentModel === 'gpt-4-turbo') {
                $('#explainer_api_model').val('claude-3-haiku-20240307');
            }
            
            // Remove OpenAI body class, add Claude class
            $('body').removeClass('openai-provider').addClass('claude-provider');
        }
        
        // Update the test API key button text
        updateTestButtonText(provider);
        
        // Trigger change event on model dropdown to update any dependent fields
        $('#explainer_api_model').trigger('change');
    }
    
    /**
     * Update test API key button text based on provider
     */
    function updateTestButtonText(provider) {
        var button = $('#test-api-key');
        var providerName = provider === 'openai' ? 'OpenAI' : (provider === 'claude' ? 'Claude' : 'API');
        button.text('Test ' + providerName + ' Key');
    }
    
    /**
     * Initialize provider selection on page load
     */
    function initializeProviderSelection() {
        // Get the current provider value
        var currentProvider = $('#explainer_api_provider').val() || 'openai';
        
        // Trigger the change event to set up the UI correctly
        $('#explainer_api_provider').val(currentProvider).trigger('change');
        
        // Also initialize any model-specific UI based on current selection
        var currentModel = $('#explainer_api_model').val();
        if (currentModel) {
            // Validate that the current model matches the provider
            if (currentProvider === 'openai' && currentModel.indexOf('claude-') === 0) {
                $('#explainer_api_model').val('gpt-3.5-turbo');
            } else if (currentProvider === 'claude' && (currentModel.indexOf('gpt-') === 0 || currentModel === 'gpt-3.5-turbo' || currentModel === 'gpt-4' || currentModel === 'gpt-4-turbo')) {
                $('#explainer_api_model').val('claude-3-haiku-20240307');
            }
        }
    }
    
    /**
     * Validate cache duration
     */
    function validateCacheDuration() {
        var duration = parseInt($(this).val());
        var fieldName = $(this).attr('name');
        
        // Find the parent label element
        var parentLabel = $(this).closest('label');
        
        // Remove all existing feedback elements for this field
        parentLabel.siblings('.duration-feedback').remove();
        
        // Only create feedback if there's an error
        if (duration < 1 || duration > 168) {
            var feedback = $('<div class="duration-feedback" data-field="' + fieldName + '"></div>');
            feedback.html('<span class="invalid">Must be between 1 and 168 hours</span>').addClass('error');
            parentLabel.after(feedback);
        }
    }
    
    /**
     * Validate rate limit
     */
    function validateRateLimit() {
        var value = parseInt($(this).val());
        var isLogged = $(this).attr('name') === 'explainer_rate_limit_logged';
        var max = isLogged ? 100 : 50;
        var fieldName = $(this).attr('name');
        
        // Find the parent label element
        var parentLabel = $(this).closest('label');
        
        // Remove all existing feedback elements for this field
        parentLabel.siblings('.rate-limit-feedback').remove();
        
        // Only create feedback if there's an error
        if (value < 1 || value > max) {
            var feedback = $('<div class="rate-limit-feedback" data-field="' + fieldName + '"></div>');
            feedback.html('<span class="invalid">Must be between 1 and ' + max + '</span>').addClass('error');
            parentLabel.after(feedback);
        }
    }
    
    /**
     * Check if OpenAI API key has valid format
     */
    function isValidOpenAIApiKey(apiKey) {
        if (!apiKey || typeof apiKey !== 'string') {
            return false;
        }
        
        // Remove any whitespace
        apiKey = apiKey.trim();
        
        // OpenAI API keys start with 'sk-'
        if (!apiKey.startsWith('sk-')) {
            return false;
        }
        
        // Check minimum length (should be at least 20 characters)
        if (apiKey.length < 20) {
            return false;
        }
        
        // Check maximum reasonable length (should not exceed 200 characters)
        if (apiKey.length > 200) {
            return false;
        }
        
        // Check that it contains only valid characters (alphanumeric, hyphens, underscores)
        if (!/^sk-[a-zA-Z0-9_-]+$/.test(apiKey)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if Claude API key has valid format
     */
    function isValidClaudeApiKey(apiKey) {
        if (!apiKey || typeof apiKey !== 'string') {
            return false;
        }
        
        // Remove any whitespace
        apiKey = apiKey.trim();
        
        // Claude API keys start with 'sk-ant-'
        if (!apiKey.startsWith('sk-ant-')) {
            return false;
        }
        
        // Check minimum length (should be at least 20 characters)
        if (apiKey.length < 20) {
            return false;
        }
        
        // Check maximum reasonable length (should not exceed 200 characters)
        if (apiKey.length > 200) {
            return false;
        }
        
        // Check that it contains only valid characters (alphanumeric, hyphens, underscores)
        if (!/^sk-ant-[a-zA-Z0-9_-]+$/.test(apiKey)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Reset settings to defaults
     */
    function resetSettings() {
        if (!confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
            return;
        }
        
        var button = $(this);
        var originalText = button.text();
        
        // Show loading state
        button.text('Resetting...').prop('disabled', true);
        
        // Clear previous messages
        $('#admin-messages').empty();
        
        $.ajax({
            url: explainerAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'explainer_reset_settings',
                nonce: explainerAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    // Reload page to show reset values
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('Connection error. Please try again.', 'error');
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    }
    
    
    /**
     * Initialize form validation
     */
    function initializeFormValidation() {
        // Real-time validation for numeric fields (excluding cache duration and rate limits which have specific handlers)
        $('input[type="number"]:not([name="explainer_cache_duration"]):not([name="explainer_rate_limit_logged"]):not([name="explainer_rate_limit_anonymous"])').on('input', function() {
            var field = $(this);
            var value = parseInt(field.val());
            var min = parseInt(field.attr('min'));
            var max = parseInt(field.attr('max'));
            var fieldName = field.attr('name');
            var feedback = field.siblings('.field-feedback[data-field="' + fieldName + '"]');
            
            // Remove any existing feedback elements for this field
            field.siblings('.field-feedback').remove();
            
            if (feedback.length === 0) {
                feedback = $('<div class="field-feedback" data-field="' + fieldName + '"></div>');
                field.after(feedback);
            }
            
            if (value < min || value > max) {
                feedback.html('<span class="invalid">Must be between ' + min + ' and ' + max + '</span>').removeClass('success').addClass('error');
                field.addClass('error');
            } else {
                feedback.html('').removeClass('error').addClass('success');
                field.removeClass('error');
            }
        });
        
        // CSS selector validation
        $('textarea[name="explainer_included_selectors"], textarea[name="explainer_excluded_selectors"]').on('input', function() {
            var field = $(this);
            var value = field.val();
            var fieldName = field.attr('name');
            var feedback = field.siblings('.field-feedback[data-field="' + fieldName + '"]');
            
            // Remove any existing feedback elements for this field
            field.siblings('.field-feedback').remove();
            
            if (feedback.length === 0) {
                feedback = $('<div class="field-feedback" data-field="' + fieldName + '"></div>');
                field.after(feedback);
            }
            
            if (validateCSSSelectors(value)) {
                feedback.html('').removeClass('error').addClass('success');
                field.removeClass('error');
            } else {
                feedback.html('<span class="invalid">Invalid CSS selector format</span>').removeClass('success').addClass('error');
                field.addClass('error');
            }
        });
    }
    
    /**
     * Initialize color pickers
     */
    function initializeColorPickers() {
        // WordPress color picker
        if ($.fn.wpColorPicker) {
            $('#explainer_tooltip_bg_color, #explainer_tooltip_text_color, #explainer_tooltip_footer_color').wpColorPicker({
                change: function() {
                    setTimeout(updateTooltipPreview, 10);
                }
            });
        }
        
        // Fallback for regular color inputs
        $('#explainer_tooltip_bg_color, #explainer_tooltip_text_color, #explainer_tooltip_footer_color').on('input', updateTooltipPreview);
    }
    
    
    /**
     * Update tooltip preview
     */
    function updateTooltipPreview() {
        var bgColor = $('#explainer_tooltip_bg_color').val();
        var textColor = $('#explainer_tooltip_text_color').val();
        var footerColor = $('#explainer_tooltip_footer_color').val();
        
        $('.explainer-tooltip-preview').css({
            'background-color': bgColor,
            'color': textColor,
            '--explainer-tooltip-bg-color': bgColor,
            '--explainer-tooltip-text-color': textColor,
            '--explainer-tooltip-footer-color': footerColor
        });
        
        // Update footer color specifically
        $('.explainer-tooltip-preview .explainer-disclaimer, .explainer-tooltip-preview .explainer-provider').css({
            'color': footerColor
        });
    }
    
    /**
     * Validate CSS selectors
     */
    function validateCSSSelectors(selectors) {
        if (!selectors.trim()) {
            return true; // Empty is valid
        }
        
        try {
            var selectorList = selectors.split(',');
            for (var i = 0; i < selectorList.length; i++) {
                var selector = selectorList[i].trim();
                if (selector) {
                    // Try to create a CSS rule to validate selector
                    document.querySelector(selector);
                }
            }
            return true;
        } catch (e) {
            return false;
        }
    }
    
    /**
     * Show admin message
     */
    function showMessage(message, type) {
        var messageClass = 'notice notice-' + type;
        if (type === 'error') {
            messageClass += ' is-dismissible';
        }
        
        var messageHtml = '<div class="' + messageClass + '">';
        messageHtml += '<p>' + message + '</p>';
        if (type === 'error') {
            messageHtml += '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
        }
        messageHtml += '</div>';
        
        $('#admin-messages').html(messageHtml);
        
        // Auto-dismiss success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $('#admin-messages').fadeOut();
            }, 5000);
        }
        
        // Handle dismiss button
        $('.notice-dismiss').on('click', function() {
            $(this).parent().fadeOut();
        });
    }
    
    /**
     * Reset prompt to default
     */
    function resetPromptToDefault() {
        var defaultPrompt = 'Please provide a clear, concise explanation of the following text in 1-2 sentences: {{snippet}}';
        $('textarea[name="explainer_custom_prompt"]').val(defaultPrompt);
        validateCustomPrompt.call($('textarea[name="explainer_custom_prompt"]')[0]);
        showMessage('Prompt reset to default. Remember to save your settings.', 'success');
    }
    
    
    /**
     * Validate custom prompt
     */
    function validateCustomPrompt() {
        var prompt = $(this).val();
        var fieldName = $(this).attr('name');
        var feedback = $(this).siblings('.field-feedback[data-field="' + fieldName + '"]');
        
        // Remove any existing feedback elements for this field
        $(this).siblings('.field-feedback').remove();
        
        if (feedback.length === 0) {
            feedback = $('<div class="field-feedback" data-field="' + fieldName + '"></div>');
            $(this).after(feedback);
        }
        
        var errors = [];
        
        // Check for {{snippet}} placeholder
        if (prompt.indexOf('{{snippet}}') === -1) {
            errors.push('Must contain {{snippet}} placeholder');
        }
        
        // Check length
        if (prompt.length > 500) {
            errors.push('Maximum 500 characters (' + prompt.length + ' current)');
        }
        
        // Check minimum length
        if (prompt.length < 10) {
            errors.push('Minimum 10 characters');
        }
        
        if (errors.length > 0) {
            feedback.html('<span class="invalid">✗ ' + errors.join(', ') + '</span>').removeClass('success').addClass('error');
            $(this).addClass('error');
        } else {
            feedback.html('').removeClass('error').addClass('success');
            $(this).removeClass('error');
        }
    }
    
    /**
     * Handle re-enable plugin button in settings page
     */
    function reEnablePlugin(e) {
        e.preventDefault();
        
        var button = $(this);
        var nonce = button.data('nonce');
        var originalText = button.text();
        
        if (!confirm('Are you sure you want to re-enable the AI Explainer plugin? Make sure you have resolved the usage limit issues first.')) {
            return;
        }
        
        // Show loading state
        button.prop('disabled', true).text('Re-enabling...');
        
        // Clear previous messages
        $('.explainer-status-message').remove();
        
        $.post(ajaxurl, {
            action: 'explainer_reenable_plugin',
            nonce: nonce
        })
        .done(function(response) {
            if (response.success) {
                // Show success message
                button.closest('.explainer-status-disabled').before('<div class="explainer-status-message notice notice-success"><p>Plugin has been successfully re-enabled.</p></div>');
                
                // Reload page after a short delay to reflect enabled state
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                alert('Error re-enabling plugin: ' + (response.data.message || 'Unknown error'));
                button.prop('disabled', false).text(originalText);
            }
        })
        .fail(function() {
            alert('Failed to re-enable plugin. Please try again.');
            button.prop('disabled', false).text(originalText);
        });
    }
    
    /**
     * Update blocked words count
     */
    function updateBlockedWordsCount() {
        var textarea = $('#explainer_blocked_words');
        var text = textarea.val();
        var words = text.split('\n').filter(function(word) {
            return word.trim().length > 0;
        });
        var count = words.length;
        
        $('#blocked-words-count').text(count);
        
        // Show warning if approaching limit
        if (count > 450) {
            $('#blocked-words-count').css('color', '#dc3232').css('font-weight', 'bold');
        } else if (count > 400) {
            $('#blocked-words-count').css('color', '#dba617').css('font-weight', 'bold');
        } else {
            $('#blocked-words-count').css('color', '').css('font-weight', '');
        }
    }
    
    /**
     * Clear all blocked words
     */
    function clearBlockedWords() {
        if (confirm('Are you sure you want to clear all blocked words?')) {
            $('#explainer_blocked_words').val('');
            updateBlockedWordsCount();
        }
    }
    
    /**
     * Load default blocked words
     */
    function loadDefaultBlockedWords() {
        var defaultWords = [
            'abuse',
            'addiction',
            'alcohol',
            'attack',
            'cannabis',
            'cocaine',
            'death',
            'drug',
            'gambling',
            'hate',
            'heroin',
            'illegal',
            'kill',
            'murder',
            'poison',
            'porn',
            'rape',
            'sex',
            'suicide',
            'tobacco',
            'violence',
            'weapon'
        ];
        
        if (confirm('This will replace your current blocked words list with common inappropriate words. Continue?')) {
            $('#explainer_blocked_words').val(defaultWords.join('\n'));
            updateBlockedWordsCount();
        }
    }
    
})(jQuery);