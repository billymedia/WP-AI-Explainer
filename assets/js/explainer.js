/**
 * AI Explainer Plugin - Main JavaScript
 * Handles text selection, validation, and UI interactions
 */

(function() {
    'use strict';
    
    // Namespace for the plugin
    window.ExplainerPlugin = window.ExplainerPlugin || {};
    
    // Plugin configuration
    const config = {
        minSelectionLength: 3,
        maxSelectionLength: 200,
        minWords: 1,
        maxWords: 30,
        debounceDelay: 300,
        contextLength: 50,
        enabled: true, // Start enabled by default
        includedSelectors: '', // Will be loaded from settings
        excludedSelectors: '', // Will be loaded from settings
        throttleDelay: 100,
        maxConcurrentRequests: 1,
        debugMode: false // Will be loaded from settings
    };
    
    // Plugin state
    const state = {
        currentSelection: null,
        selectionPosition: null,
        selectionContext: null,
        isProcessing: false,
        lastSelection: null,
        activeRequests: 0,
        requestQueue: [],
        debounceTimer: null,
        throttleTimer: null,
        cache: new Map(),
        observers: []
    };
    
    // DOM elements
    const elements = {
        toggleButton: null,
        tooltip: null
    };
    
    // Debug logging function
    const debugLog = function(message, data = {}) {
        if (config.debugMode && console && console.log) {
            console.log('[ExplainerPlugin Debug]', message, data);
        }
    };
    
    // Performance utilities
    const utils = {
        debounce: (func, delay) => {
            return function(...args) {
                if (state.debounceTimer) {
                    clearTimeout(state.debounceTimer);
                }
                state.debounceTimer = setTimeout(() => func.apply(this, args), delay);
            };
        },
        
        throttle: (func, delay) => {
            return function(...args) {
                if (!state.throttleTimer) {
                    state.throttleTimer = setTimeout(() => {
                        func.apply(this, args);
                        state.throttleTimer = null;
                    }, delay);
                }
            };
        },
        
        memoize: (func, keyFunc) => {
            return function(...args) {
                const key = keyFunc ? keyFunc(...args) : args[0];
                if (state.cache.has(key)) {
                    return state.cache.get(key);
                }
                const result = func.apply(this, args);
                state.cache.set(key, result);
                return result;
            };
        },
        
        cleanupObservers: () => {
            state.observers.forEach(observer => {
                if (observer.disconnect) {
                    observer.disconnect();
                } else if (observer.removeEventListener) {
                    observer.removeEventListener();
                }
            });
            state.observers = [];
        },
        
        requestIdleCallback: (callback) => {
            if (window.requestIdleCallback) {
                return window.requestIdleCallback(callback);
            }
            return setTimeout(callback, 1);
        }
    };

    /**
     * Initialize the plugin with enhanced accessibility
     */
    function init() {
        debugLog('Attempting to initialize...');
        
        // Check if plugin should be loaded
        if (!shouldLoadPlugin()) {
            debugLog('shouldLoadPlugin returned false, stopping initialization');
            return;
        }
        
        debugLog('Plugin should load, continuing initialization...');
        
        // Load settings from localized data
        loadSettings();
        
        // Validate and enhance color contrast
        validateColorContrast();
        
        // Create enhanced skip links
        createEnhancedSkipLinks();
        
        // Create UI elements
        createToggleButton();
        
        // Apply color settings to tooltips
        applyTooltipColors();
        
        // Enhance ARIA support
        enhanceARIASupport();
        
        // Set up event listeners with performance optimization
        setupEventListeners();
        
        // Initialize selection system
        initializeSelectionSystem();
        
        // Set up cleanup on page unload
        window.addEventListener('beforeunload', cleanup);
        
        // Announce plugin availability to screen readers
        announceToScreenReader('AI Explainer plugin loaded. Press Ctrl+Shift+E to toggle, or use the button in the bottom right corner.');
        
        debugLog('AI Explainer Plugin initialized with accessibility enhancements');
    }
    
    /**
     * Check if plugin should be loaded
     */
    function shouldLoadPlugin() {
        // Don't load on admin pages
        if (document.body.classList.contains('wp-admin')) {
            return false;
        }
        
        // Don't load if explicitly disabled (fallback to enabled if not defined)
        if (typeof explainerAjax !== 'undefined' && explainerAjax.settings && !explainerAjax.settings.enabled) {
            return false;
        }
        
        // Add debug logging
        if (typeof explainerAjax === 'undefined') {
            console.warn('ExplainerPlugin: explainerAjax object not found, plugin may not be properly localized');
        } else {
            debugLog('explainerAjax found:', explainerAjax);
        }
        
        // Check if selection API is supported
        if (!window.getSelection) {
            console.warn('Text selection not supported in this browser');
            return false;
        }
        
        return true;
    }
    
    /**
     * Load settings from WordPress localized data
     */
    function loadSettings() {
        debugLog('Loading settings...');
        if (typeof explainerAjax !== 'undefined' && explainerAjax.settings) {
            debugLog('Found explainerAjax.settings:', explainerAjax.settings);
            config.minSelectionLength = parseInt(explainerAjax.settings.min_selection_length) || 3;
            config.maxSelectionLength = parseInt(explainerAjax.settings.max_selection_length) || 200;
            config.minWords = parseInt(explainerAjax.settings.min_words) || 1;
            config.maxWords = parseInt(explainerAjax.settings.max_words) || 30;
            config.enabled = explainerAjax.settings.enabled !== false;
            config.showDisclaimer = explainerAjax.settings.show_disclaimer !== false;
            config.showProvider = explainerAjax.settings.show_provider !== false;
            config.apiProvider = explainerAjax.settings.api_provider || 'openai';
            config.debugMode = explainerAjax.settings.debug_mode === true;
            
            debugLog('Debug mode enabled:', config.debugMode);
            
            // Use server settings directly, no fallback to hardcoded values
            config.includedSelectors = explainerAjax.settings.included_selectors || '';
            config.excludedSelectors = explainerAjax.settings.excluded_selectors || '';
            
            debugLog('Loaded included selectors:', config.includedSelectors);
            debugLog('Loaded excluded selectors:', config.excludedSelectors);
        } else {
            debugLog('No explainerAjax.settings found, using minimal defaults');
            // Set sensible defaults when no server settings available
            config.includedSelectors = 'article, main, .content, .entry-content, .post-content';
            config.excludedSelectors = '';
            config.minSelectionLength = 3;
            config.maxSelectionLength = 200;
            config.minWords = 1;
            config.maxWords = 30;
            config.enabled = true;
            config.showDisclaimer = true;
            config.showProvider = true;
            config.apiProvider = 'openai';
        }
        debugLog('Final config:', config);
    }
    
    /**
     * Cleanup function for performance
     */
    function cleanup() {
        // Clear timers
        if (state.debounceTimer) {
            clearTimeout(state.debounceTimer);
        }
        if (state.throttleTimer) {
            clearTimeout(state.throttleTimer);
        }
        
        // Clear cache
        state.cache.clear();
        
        // Cleanup observers
        utils.cleanupObservers();
        
        // Remove event listeners
        document.removeEventListener('selectionchange', handleSelectionChange);
        document.removeEventListener('click', handleDocumentClick);
        
        // Clear active requests
        state.activeRequests = 0;
        state.requestQueue = [];
    }
    
    /**
     * Create toggle button with accessibility enhancements
     */
    function createToggleButton() {
        debugLog('Creating toggle button...');
        
        elements.toggleButton = document.createElement('button');
        elements.toggleButton.id = 'explainer-toggle';
        elements.toggleButton.className = 'explainer-toggle';
        elements.toggleButton.setAttribute('aria-label', 'Toggle AI Explainer feature');
        elements.toggleButton.setAttribute('aria-pressed', config.enabled ? 'true' : 'false');
        elements.toggleButton.setAttribute('aria-describedby', 'explainer-description');
        elements.toggleButton.setAttribute('role', 'switch');
        elements.toggleButton.setAttribute('tabindex', '0');
        
        // Create the button content with icon and text
        const buttonHTML = `
            <svg class="explainer-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            <span class="explainer-text">Explainer</span>
        `;
        
        elements.toggleButton.innerHTML = buttonHTML;
        
        // Apply custom colors from settings
        applyButtonColors();
        
        // Add position class based on settings
        const position = getTogglePosition();
        if (position !== 'bottom-right') {
            elements.toggleButton.classList.add(position);
        }
        
        // Add screen reader description
        const description = document.createElement('span');
        description.id = 'explainer-description';
        description.className = 'explainer-sr-only';
        description.textContent = 'Toggle AI text explanation feature on or off';
        
        // Add to page
        document.body.appendChild(elements.toggleButton);
        document.body.appendChild(description);
        
        debugLog('Toggle button created and added to DOM');
        debugLog('Button element:', elements.toggleButton);
        
        // Set initial state
        updateToggleButton();
        
        // Add click handler directly to button
        elements.toggleButton.addEventListener('click', function(e) {
            debugLog('Toggle button clicked!', e);
            debugLog('Current enabled state:', config.enabled);
            e.stopPropagation(); // Prevent event bubbling
            e.preventDefault(); // Prevent default behavior
            togglePlugin();
            debugLog('New enabled state:', config.enabled);
        });
        
        // Also prevent mouseup from triggering selection handler
        elements.toggleButton.addEventListener('mouseup', function(e) {
            e.stopPropagation(); // Prevent event bubbling to document
            e.preventDefault();
        });
        
        // And prevent touchend from triggering selection handler
        elements.toggleButton.addEventListener('touchend', function(e) {
            e.stopPropagation(); // Prevent event bubbling to document
            e.preventDefault();
        });
        
        debugLog('Click handler added to toggle button');
    }
    
    /**
     * Update toggle button state
     */
    function updateToggleButton() {
        if (!elements.toggleButton) {
            debugLog('updateToggleButton - no button element found');
            return;
        }
        
        debugLog('updateToggleButton called, enabled:', config.enabled);
        debugLog('Button current classes:', elements.toggleButton.className);
        
        elements.toggleButton.classList.toggle('enabled', config.enabled);
        elements.toggleButton.setAttribute('aria-pressed', config.enabled ? 'true' : 'false');
        elements.toggleButton.title = config.enabled ? 'Disable AI Explainer' : 'Enable AI Explainer';
        
        debugLog('Button updated classes:', elements.toggleButton.className);
        
        // Update colors when state changes
        applyButtonColors();
    }
    
    /**
     * Apply custom button colors from settings
     */
    function applyButtonColors() {
        if (!elements.toggleButton) return;
        
        // Get colors from settings - settings should always be available
        let enabledColor = '#46b450';
        let disabledColor = '#666666';
        let textColor = '#ffffff';
        
        // Get colors from WordPress settings
        if (typeof explainerAjax !== 'undefined' && explainerAjax.settings) {
            enabledColor = explainerAjax.settings.button_enabled_color || enabledColor;
            disabledColor = explainerAjax.settings.button_disabled_color || disabledColor;
            textColor = explainerAjax.settings.button_text_color || textColor;
        }
        
        // Update CSS custom properties
        document.documentElement.style.setProperty('--explainer-button-enabled', enabledColor);
        document.documentElement.style.setProperty('--explainer-button-disabled', disabledColor);
        document.documentElement.style.setProperty('--explainer-button-text', textColor);
        
        // Force update the button's visual state by ensuring CSS classes are properly applied
        if (config.enabled) {
            elements.toggleButton.classList.add('enabled');
        } else {
            elements.toggleButton.classList.remove('enabled');
        }
        
        debugLog('Button colors applied', {
            enabled: enabledColor,
            disabled: disabledColor,
            text: textColor,
            state: config.enabled ? 'enabled' : 'disabled',
            hasEnabledClass: elements.toggleButton.classList.contains('enabled')
        });
    }
    
    /**
     * Apply custom tooltip colors and font from settings
     */
    function applyTooltipColors() {
        // Get colors from settings - settings should always be available
        let bgColor = '#333333';
        let textColor = '#ffffff';
        let footerColor = '#ffffff';
        
        // Get colors from WordPress settings
        if (typeof explainerAjax !== 'undefined' && explainerAjax.settings) {
            bgColor = explainerAjax.settings.tooltip_bg_color || bgColor;
            textColor = explainerAjax.settings.tooltip_text_color || textColor;
            footerColor = explainerAjax.settings.tooltip_footer_color || footerColor;
        }
        
        // Detect site's paragraph font
        const siteFont = detectSiteFont();
        
        // Update CSS custom properties
        document.documentElement.style.setProperty('--explainer-tooltip-bg-color', bgColor);
        document.documentElement.style.setProperty('--explainer-tooltip-text-color', textColor);
        document.documentElement.style.setProperty('--explainer-tooltip-footer-color', footerColor);
        document.documentElement.style.setProperty('--explainer-site-font', siteFont);
        
        debugLog('Tooltip colors and font applied', {
            background: bgColor,
            text: textColor,
            footer: footerColor,
            font: siteFont
        });
    }
    
    /**
     * Detect the site's paragraph font
     */
    function detectSiteFont() {
        // Try to find a paragraph element to get its font
        const paragraph = document.querySelector('p, article p, main p, .content p, .entry-content p, .post-content p');
        
        if (paragraph) {
            const computedStyle = window.getComputedStyle(paragraph);
            const fontFamily = computedStyle.getPropertyValue('font-family');
            
            if (fontFamily && fontFamily !== 'inherit') {
                debugLog('Site font detected from paragraph', { font: fontFamily });
                return fontFamily;
            }
        }
        
        // Fallback: check body font
        const body = document.body;
        if (body) {
            const bodyStyle = window.getComputedStyle(body);
            const bodyFont = bodyStyle.getPropertyValue('font-family');
            
            if (bodyFont && bodyFont !== 'inherit') {
                debugLog('Site font detected from body', { font: bodyFont });
                return bodyFont;
            }
        }
        
        // Final fallback to system font
        const systemFont = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
        debugLog('Using system font fallback', { font: systemFont });
        return systemFont;
    }
    
    /**
     * Get toggle button position from settings
     */
    function getTogglePosition() {
        if (typeof explainerAjax !== 'undefined' && explainerAjax.settings) {
            return explainerAjax.settings.toggle_position || 'bottom-right';
        }
        return 'bottom-right';
    }
    
    /**
     * Set up event listeners with accessibility support
     */
    function setupEventListeners() {
        // Toggle button keyboard only (click handler already added in createToggleButton)
        if (elements.toggleButton) {
            elements.toggleButton.addEventListener('keydown', handleToggleKeydown);
        }
        
        // Document-level selection events
        debugLog('Setting up selection event listeners...');
        const debouncedHandler = utils.debounce(handleSelection, config.debounceDelay);
        document.addEventListener('mouseup', debouncedHandler);
        document.addEventListener('touchend', debouncedHandler);
        
        // Add direct mouseup handler for testing
        document.addEventListener('mouseup', function(e) {
            debugLog('Direct mouseup event detected', e);
        });
        
        // Keyboard events
        document.addEventListener('keydown', handleKeyDown);
        
        // Click outside to dismiss
        document.addEventListener('click', handleOutsideClick);
        
        // Window resize
        window.addEventListener('resize', handleResize);
        
        // Focus management
        document.addEventListener('focusin', handleFocusIn);
        document.addEventListener('focusout', handleFocusOut);
    }
    
    /**
     * Initialize selection system
     */
    function initializeSelectionSystem() {
        // Load user preferences
        loadUserPreferences();
        
        // Update button state after loading preferences
        updateToggleButton();
        
        // Set up selection highlighting
        setupSelectionHighlighting();
        
        debugLog('Selection system initialized');
    }
    
    /**
     * Handle text selection
     */
    function handleSelection(event) {
        debugLog('handleSelection called', { enabled: config.enabled, isProcessing: state.isProcessing });
        
        // Check if the event came from the toggle button or related elements
        if (event && event.target) {
            const isToggleButton = event.target.closest('.explainer-toggle') || 
                                   event.target.classList.contains('explainer-toggle') ||
                                   event.target.id === 'explainer-toggle';
            if (isToggleButton) {
                debugLog('Selection ignored - click on toggle button');
                return;
            }
        }
        
        if (!config.enabled || state.isProcessing) {
            debugLog('Selection ignored', { enabled: config.enabled, isProcessing: state.isProcessing });
            return;
        }
        
        try {
            const selection = window.getSelection();
            debugLog('Selection object retrieved', { 
                rangeCount: selection.rangeCount,
                toString: selection.toString(),
                type: selection.type
            });
            
            // Clear previous selection
            clearPreviousSelection();
            
            // Check if there's a valid selection
            if (!selection || selection.rangeCount === 0) {
                debugLog('No valid selection found');
                return;
            }
            
            const selectedText = selection.toString().trim();
            debugLog('Selected text:', selectedText);
            
            // Validate selection
            if (!validateSelection(selectedText, selection)) {
                debugLog('Selection validation failed');
                return;
            }
            
            // Check if selection is in allowed content
            if (!isSelectableContent(selection)) {
                return;
            }
            
            // Store selection data
            storeSelectionData(selectedText, selection);
            
            // Track selection position
            trackSelectionPosition(selection);
            
            // Extract context
            extractSelectionContext(selection);
            
            // Trigger explanation request
            requestExplanation();
            
        } catch (error) {
            console.error('Selection handling error:', error);
            showError('Selection processing failed');
        }
    }
    
    /**
     * Validate text selection
     */
    function validateSelection(text, selection) {
        debugLog('validateSelection called with text:', text);
        debugLog('text length:', text.length);
        debugLog('config limits:', {
            minLength: config.minSelectionLength,
            maxLength: config.maxSelectionLength,
            minWords: config.minWords,
            maxWords: config.maxWords
        });
        
        if (!text || text.length === 0) {
            debugLog('Validation failed - empty text');
            return false;
        }
        
        // Check minimum length
        if (text.length < config.minSelectionLength) {
            debugLog('Validation failed - too short');
            showValidationMessage(`Selection too short (minimum ${config.minSelectionLength} characters)`);
            return false;
        }
        
        // Check maximum length
        if (text.length > config.maxSelectionLength) {
            debugLog('Validation failed - too long');
            showValidationMessage(`Selection too long (maximum ${config.maxSelectionLength} characters)`);
            return false;
        }
        
        // Check word count
        const wordCount = countWords(text);
        debugLog('Word count:', wordCount);
        if (wordCount < config.minWords || wordCount > config.maxWords) {
            debugLog('Validation failed - word count out of range');
            showValidationMessage(`Selection must be between ${config.minWords} and ${config.maxWords} words`);
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if selection is in allowed content
     */
    function isSelectableContent(selection) {
        debugLog('isSelectableContent called');
        debugLog('selection.anchorNode:', selection.anchorNode);
        
        if (!selection.anchorNode) {
            debugLog('No anchor node found');
            return false;
        }
        
        const element = selection.anchorNode.nodeType === Node.TEXT_NODE ? 
            selection.anchorNode.parentElement : selection.anchorNode;
        
        debugLog('Target element:', element);
        debugLog('Element tag:', element?.tagName);
        debugLog('Element classes:', element?.className);
        
        // Check if element is in excluded areas
        // Always exclude our own elements and form inputs
        const alwaysExcluded = [
            '.explainer-tooltip', '.explainer-toggle', 
            'button', 'input', 'select', 'textarea'
        ];
        
        // Get user-configured excluded selectors
        const userExcluded = config.excludedSelectors ? 
            config.excludedSelectors.split(',').map(s => s.trim()).filter(s => s.length > 0) : 
            [];
        
        const excludedSelectors = [...alwaysExcluded, ...userExcluded];
        debugLog('Final excluded selectors:', excludedSelectors);
        
        debugLog('Checking excluded selectors...');
        for (const selector of excludedSelectors) {
            if (element.closest(selector)) {
                debugLog('Found excluded selector:', selector);
                return false;
            }
        }
        debugLog('No excluded selectors found');
        
        // Check if element is in allowed areas
        // Get user-configured allowed selectors, with fallback to sensible defaults
        const userAllowed = config.includedSelectors ? 
            config.includedSelectors.split(',').map(s => s.trim()).filter(s => s.length > 0) : 
            ['article', 'main', '.content', '.entry-content', '.post-content'];
        
        // Add common content elements as fallback
        const fallbackAllowed = ['p', 'div', 'span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        const allowedSelectors = [...userAllowed, ...fallbackAllowed];
        debugLog('Final allowed selectors:', allowedSelectors);
        
        debugLog('Checking allowed selectors...');
        for (const selector of allowedSelectors) {
            if (element.closest(selector)) {
                debugLog('Found allowed selector:', selector);
                return true;
            }
        }
        
        debugLog('No allowed selectors found');
        return false;
    }
    
    /**
     * Store selection data
     */
    function storeSelectionData(text, selection) {
        state.currentSelection = {
            text: text,
            range: selection.getRangeAt(0).cloneRange(),
            element: selection.anchorNode.nodeType === Node.TEXT_NODE ? 
                selection.anchorNode.parentElement : selection.anchorNode,
            timestamp: Date.now()
        };
    }
    
    /**
     * Track selection position for tooltip placement
     */
    function trackSelectionPosition(selection) {
        try {
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            
            state.selectionPosition = {
                x: rect.left + (rect.width / 2),
                y: rect.top,
                width: rect.width,
                height: rect.height,
                scrollX: window.scrollX,
                scrollY: window.scrollY
            };
        } catch (error) {
            console.error('Position tracking error:', error);
            state.selectionPosition = null;
        }
    }
    
    /**
     * Extract context around selection
     */
    function extractSelectionContext(selection) {
        try {
            const range = selection.getRangeAt(0);
            const container = range.commonAncestorContainer;
            const containerText = container.textContent || '';
            
            // Find selection within container
            const selectionStart = containerText.indexOf(state.currentSelection.text);
            if (selectionStart === -1) {
                state.selectionContext = null;
                return;
            }
            
            // Extract context before and after
            const beforeStart = Math.max(0, selectionStart - config.contextLength);
            const afterEnd = Math.min(containerText.length, selectionStart + state.currentSelection.text.length + config.contextLength);
            
            state.selectionContext = {
                before: containerText.substring(beforeStart, selectionStart),
                after: containerText.substring(selectionStart + state.currentSelection.text.length, afterEnd),
                full: containerText.substring(beforeStart, afterEnd)
            };
        } catch (error) {
            console.error('Context extraction error:', error);
            state.selectionContext = null;
        }
    }
    
    /**
     * Clear previous selection
     */
    function clearPreviousSelection() {
        // Clear selection state
        state.lastSelection = state.currentSelection;
        state.currentSelection = null;
        state.selectionPosition = null;
        state.selectionContext = null;
        
        // Clear visual highlights
        clearSelectionHighlight();
        
        // Hide any existing tooltips
        hideTooltip();
    }
    
    /**
     * Request explanation from API
     */
    function requestExplanation() {
        debugLog('requestExplanation called', { 
            hasSelection: !!state.currentSelection,
            selectionText: state.currentSelection?.text || 'none'
        });
        
        if (!state.currentSelection) {
            debugLog('No current selection, aborting request');
            return;
        }
        
        state.isProcessing = true;
        
        // Show loading state
        showLoadingState();
        
        // Prepare request data
        const requestData = {
            action: 'explainer_get_explanation',
            nonce: explainerAjax.nonce,
            text: state.currentSelection.text,
            context: state.selectionContext
        };
        
        debugLog('Making API request with data:', requestData);
        debugLog('AJAX URL:', explainerAjax.ajaxurl);
        
        // Make Ajax request
        fetch(explainerAjax.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(requestData)
        })
        .then(response => response.json())
        .then(data => {
            state.isProcessing = false;
            debugLog('API response received', { 
                success: data.success,
                hasData: !!data.data,
                cached: data.data?.cached || false,
                tokensUsed: data.data?.tokens_used || 0,
                responseTime: data.data?.response_time || 0
            });
            
            if (data.success) {
                debugLog('Explanation received successfully', {
                    explanationLength: data.data.explanation?.length || 0,
                    cached: data.data.cached || false
                });
                showExplanation(data.data.explanation, data.data.provider);
            } else {
                // Handle different error response formats
                let errorMessage = 'Failed to get explanation';
                if (data.data && data.data.message) {
                    errorMessage = data.data.message;
                } else if (data.message) {
                    errorMessage = data.message;
                } else if (typeof data === 'string') {
                    errorMessage = data;
                }
                debugLog('API error occurred', { errorMessage });
                showError(errorMessage);
            }
        })
        .catch(error => {
            state.isProcessing = false;
            debugLog('API request failed', { error: error.message || error });
            console.error('API request error:', error);
            showError('Connection error. Please try again.');
        });
    }
    
    /**
     * Show loading state
     */
    function showLoadingState() {
        if (window.ExplainerPlugin.showTooltip) {
            window.ExplainerPlugin.showTooltip('Loading explanation...', state.selectionPosition, 'loading');
        }
    }
    
    /**
     * Show explanation in tooltip with enhanced accessibility
     */
    function showExplanation(explanation, provider = null) {
        // Prepare footer options from admin settings
        const footerOptions = {
            showDisclaimer: config.showDisclaimer || false,
            showProvider: config.showProvider || false,
            provider: provider || config.apiProvider || 'openai'
        };
        
        if (window.ExplainerPlugin.updateTooltipContent) {
            // Update existing loading tooltip
            window.ExplainerPlugin.updateTooltipContent(explanation, 'explanation', footerOptions);
        } else if (window.ExplainerPlugin.showTooltip) {
            // Show new tooltip
            window.ExplainerPlugin.showTooltip(explanation, state.selectionPosition, 'explanation', footerOptions);
        }
        
        // Enhanced screen reader announcement with summary
        const summary = explanation.length > 100 ? 
            explanation.substring(0, 100) + '... (full explanation in tooltip)' : 
            explanation;
        announceToScreenReader('Explanation loaded: ' + summary, 'assertive');
        
        // Add landmark role for screen readers
        const tooltip = document.querySelector('.explainer-tooltip.visible');
        if (tooltip) {
            tooltip.setAttribute('role', 'region');
            tooltip.setAttribute('aria-labelledby', 'explainer-tooltip-title');
            tooltip.setAttribute('aria-describedby', 'explainer-tooltip-content');
        }
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        if (window.ExplainerPlugin.updateTooltipContent) {
            // Update existing loading tooltip
            window.ExplainerPlugin.updateTooltipContent(message, 'error');
        } else if (window.ExplainerPlugin.showTooltip) {
            // Show new tooltip
            window.ExplainerPlugin.showTooltip(message, state.selectionPosition, 'error');
        }
    }
    
    /**
     * Show validation message
     */
    function showValidationMessage(message) {
        debugLog('Validation:', message);
        
        // Show a temporary error tooltip at the current selection
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            
            // Create error tooltip
            const errorTooltip = document.createElement('div');
            errorTooltip.className = 'explainer-validation-error';
            errorTooltip.textContent = message;
            errorTooltip.style.cssText = `
                position: fixed;
                top: ${rect.top - 40}px;
                left: ${rect.left}px;
                background: #d63638;
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 13px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                z-index: 999999;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                max-width: 300px;
                word-wrap: break-word;
                pointer-events: none;
            `;
            
            document.body.appendChild(errorTooltip);
            
            // Remove after 3 seconds
            setTimeout(() => {
                if (errorTooltip.parentNode) {
                    errorTooltip.parentNode.removeChild(errorTooltip);
                }
            }, 3000);
        }
    }
    
    /**
     * Hide tooltip
     */
    function hideTooltip() {
        if (window.ExplainerPlugin.hideTooltip) {
            window.ExplainerPlugin.hideTooltip();
        }
    }
    
    /**
     * Toggle plugin enabled state
     */
    function togglePlugin() {
        debugLog('togglePlugin called, current state:', config.enabled);
        config.enabled = !config.enabled;
        debugLog('togglePlugin new state:', config.enabled);
        updateToggleButton();
        saveUserPreferences();
        
        if (!config.enabled) {
            clearPreviousSelection();
        }
        
        // Announce state change to screen reader
        announceToScreenReader(config.enabled ? 
            'AI Explainer enabled. Select text to get explanations.' : 
            'AI Explainer disabled.'
        );
        
        debugLog('togglePlugin completed, final state:', config.enabled);
    }
    
    /**
     * Handle keyboard events with enhanced accessibility support
     */
    function handleKeyDown(event) {
        // Escape key dismisses tooltip
        if (event.key === 'Escape') {
            clearPreviousSelection();
            announceToScreenReader('Explanation closed', 'assertive');
            // Return focus to last focused element
            if (state.lastFocusedElement && typeof state.lastFocusedElement.focus === 'function') {
                state.lastFocusedElement.focus();
            }
        }
        
        // F1 key activates explainer on selected text
        if (event.key === 'F1' && event.altKey) {
            event.preventDefault();
            const selection = window.getSelection();
            if (selection && selection.toString().trim()) {
                announceToScreenReader('Getting explanation for selected text', 'assertive');
                handleSelection(event);
            } else {
                announceToScreenReader('Please select text first to get an explanation', 'assertive');
            }
        }
        
        // Ctrl+Shift+E toggles plugin (alternative to button)
        if (event.key === 'E' && event.ctrlKey && event.shiftKey) {
            event.preventDefault();
            togglePlugin();
            const status = config.enabled ? 'enabled' : 'disabled';
            announceToScreenReader(`AI Explainer ${status}`, 'assertive');
        }
        
        // Tab key navigation
        if (event.key === 'Tab') {
            handleTabNavigation(event);
        }
        
        // Arrow keys for tooltip navigation when focused
        if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(event.key)) {
            handleArrowNavigation(event);
        }
    }
    
    /**
     * Handle toggle button keyboard events
     */
    function handleToggleKeydown(event) {
        // Enter or Space activates toggle
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            togglePlugin();
        }
    }
    
    /**
     * Handle tab navigation
     */
    function handleTabNavigation(event) {
        const tooltip = document.querySelector('.explainer-tooltip.visible');
        if (tooltip) {
            const focusableElements = tooltip.querySelectorAll('button, a, input, select, textarea, [tabindex]:not([tabindex="-1"])');
            
            if (focusableElements.length > 0) {
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];
                
                if (event.shiftKey) {
                    // Shift + Tab
                    if (document.activeElement === firstElement) {
                        event.preventDefault();
                        lastElement.focus();
                    }
                } else {
                    // Tab
                    if (document.activeElement === lastElement) {
                        event.preventDefault();
                        firstElement.focus();
                    }
                }
            }
        }
    }
    
    /**
     * Handle focus events
     */
    function handleFocusIn(event) {
        // Track focus for accessibility
        state.lastFocusedElement = event.target;
    }
    
    /**
     * Handle focus out events
     */
    function handleFocusOut(event) {
        // Handle focus management
        setTimeout(() => {
            const tooltip = document.querySelector('.explainer-tooltip.visible');
            if (tooltip && !tooltip.contains(document.activeElement)) {
                // Focus moved outside tooltip
                const nextElement = event.relatedTarget;
                if (!nextElement || !tooltip.contains(nextElement)) {
                    // Consider closing tooltip if focus completely left
                    if (!elements.toggleButton.contains(document.activeElement)) {
                        clearPreviousSelection();
                    }
                }
            }
        }, 0);
    }
    
    /**
     * Handle outside clicks
     */
    function handleOutsideClick(event) {
        // Clear selection if clicking outside
        if (!event.target.closest('.explainer-tooltip, .explainer-toggle')) {
            clearPreviousSelection();
        }
    }
    
    /**
     * Handle window resize
     */
    function handleResize() {
        // Update tooltip position if visible
        if (state.currentSelection && state.selectionPosition) {
            hideTooltip();
        }
    }
    
    /**
     * Utility functions
     */
    
    /**
     * Count words in text
     */
    function countWords(text) {
        return text.trim().split(/\s+/).filter(word => word.length > 0).length;
    }
    
    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Setup selection highlighting
     */
    function setupSelectionHighlighting() {
        // Add CSS for selection highlighting
        const style = document.createElement('style');
        style.textContent = `
            .explainer-selection-highlight {
                background-color: rgba(255, 255, 0, 0.3);
                border-radius: 2px;
            }
        `;
        document.head.appendChild(style);
    }
    
    /**
     * Clear selection highlight
     */
    function clearSelectionHighlight() {
        const highlighted = document.querySelectorAll('.explainer-selection-highlight');
        highlighted.forEach(el => {
            el.classList.remove('explainer-selection-highlight');
        });
    }
    
    /**
     * Load user preferences
     */
    function loadUserPreferences() {
        try {
            const saved = localStorage.getItem('explainer-plugin-enabled');
            if (saved !== null) {
                config.enabled = saved === 'true';
                debugLog('Restored saved state from localStorage:', config.enabled);
            } else {
                debugLog('No saved state found, using default:', config.enabled);
            }
        } catch (error) {
            console.warn('Could not load preferences:', error);
        }
    }
    
    /**
     * Save user preferences
     */
    function saveUserPreferences() {
        try {
            localStorage.setItem('explainer-plugin-enabled', config.enabled);
            debugLog('Saved state to localStorage:', config.enabled);
        } catch (error) {
            console.warn('Could not save preferences:', error);
        }
    }
    
    /**
     * Announce messages to screen readers with enhanced support
     */
    function announceToScreenReader(message, priority = 'polite') {
        // Create or reuse announcement container
        let announcementContainer = document.getElementById('explainer-announcements');
        if (!announcementContainer) {
            announcementContainer = document.createElement('div');
            announcementContainer.id = 'explainer-announcements';
            announcementContainer.className = 'explainer-sr-only';
            announcementContainer.setAttribute('aria-live', 'polite');
            announcementContainer.setAttribute('aria-atomic', 'true');
            document.body.appendChild(announcementContainer);
        }
        
        // Update priority if needed
        if (priority === 'assertive') {
            announcementContainer.setAttribute('aria-live', 'assertive');
        }
        
        // Clear previous content and add new message
        announcementContainer.textContent = '';
        setTimeout(() => {
            announcementContainer.textContent = message;
        }, 100);
        
        // Reset to polite after assertive announcements
        if (priority === 'assertive') {
            setTimeout(() => {
                announcementContainer.setAttribute('aria-live', 'polite');
            }, 1000);
        }
    }
    
    /**
     * Create skip link for keyboard navigation
     */
    function createSkipLink() {
        const skipLink = document.createElement('a');
        skipLink.href = '#explainer-toggle';
        skipLink.className = 'explainer-skip-link';
        skipLink.textContent = 'Skip to AI Explainer';
        skipLink.setAttribute('tabindex', '0');
        
        document.body.insertBefore(skipLink, document.body.firstChild);
        
        return skipLink;
    }
    
    /**
     * Manage focus for accessibility with enhanced features
     */
    function manageFocus(element, options = {}) {
        if (!element || typeof element.focus !== 'function') {
            return false;
        }
        
        // Store current focus for restoration
        if (!options.skipStore) {
            state.lastFocusedElement = document.activeElement;
        }
        
        // Focus the element
        try {
            element.focus({ preventScroll: options.preventScroll || false });
            
            // Ensure focus is visible
            if (element.scrollIntoView && !options.preventScroll) {
                element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'nearest'
                });
            }
            
            // Add temporary focus ring for better visibility
            if (options.enhancedFocus) {
                element.classList.add('explainer-enhanced-focus');
                setTimeout(() => {
                    element.classList.remove('explainer-enhanced-focus');
                }, 2000);
            }
            
            return true;
        } catch (error) {
            console.warn('Focus management error:', error);
            return false;
        }
    }
    
    /**
     * Check if element is keyboard accessible
     */
    function isKeyboardAccessible(element) {
        const tabIndex = element.getAttribute('tabindex');
        return tabIndex !== '-1' && 
               !element.disabled && 
               element.offsetWidth > 0 && 
               element.offsetHeight > 0;
    }
    
    /**
     * Handle arrow key navigation for enhanced accessibility
     */
    function handleArrowNavigation(event) {
        const tooltip = document.querySelector('.explainer-tooltip.visible');
        if (!tooltip || !tooltip.contains(document.activeElement)) {
            return;
        }
        
        const focusableElements = tooltip.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length <= 1) {
            return;
        }
        
        const currentIndex = Array.from(focusableElements).indexOf(document.activeElement);
        let newIndex = currentIndex;
        
        switch (event.key) {
            case 'ArrowDown':
            case 'ArrowRight':
                event.preventDefault();
                newIndex = (currentIndex + 1) % focusableElements.length;
                break;
            case 'ArrowUp':
            case 'ArrowLeft':
                event.preventDefault();
                newIndex = currentIndex === 0 ? focusableElements.length - 1 : currentIndex - 1;
                break;
        }
        
        if (newIndex !== currentIndex) {
            manageFocus(focusableElements[newIndex], { skipStore: true });
        }
    }
    
    /**
     * Create enhanced skip links for better navigation
     */
    function createEnhancedSkipLinks() {
        const skipLinksContainer = document.createElement('div');
        skipLinksContainer.id = 'explainer-skip-links';
        skipLinksContainer.className = 'explainer-skip-links';
        
        const skipToToggle = document.createElement('a');
        skipToToggle.href = '#explainer-toggle';
        skipToToggle.className = 'explainer-skip-link';
        skipToToggle.textContent = 'Skip to AI Explainer toggle';
        skipToToggle.setAttribute('tabindex', '0');
        
        const skipToContent = document.createElement('a');
        skipToContent.href = '#main, #content, article, .content';
        skipToContent.className = 'explainer-skip-link';
        skipToContent.textContent = 'Skip to main content';
        skipToContent.setAttribute('tabindex', '0');
        
        skipLinksContainer.appendChild(skipToToggle);
        skipLinksContainer.appendChild(skipToContent);
        
        document.body.insertBefore(skipLinksContainer, document.body.firstChild);
        
        return skipLinksContainer;
    }
    
    /**
     * Enhance color contrast validation
     */
    function validateColorContrast() {
        // Check if current page has sufficient contrast for our elements
        const computedStyle = window.getComputedStyle(document.body);
        const backgroundColor = computedStyle.backgroundColor;
        const textColor = computedStyle.color;
        
        // Basic contrast check - in a full implementation, this would use
        // proper contrast ratio calculations
        const isDarkBackground = backgroundColor.includes('rgb(0') || 
                                backgroundColor.includes('rgb(1') || 
                                backgroundColor.includes('rgb(2');
        
        if (isDarkBackground) {
            document.documentElement.style.setProperty(
                '--explainer-primary-color', 
                '#4a9eff'
            );
        }
    }
    
    /**
     * Add comprehensive ARIA labels and descriptions
     */
    function enhanceARIASupport() {
        // Enhanced toggle button ARIA
        if (elements.toggleButton) {
            elements.toggleButton.setAttribute('aria-expanded', 'false');
            elements.toggleButton.setAttribute('aria-haspopup', 'dialog');
            elements.toggleButton.setAttribute('aria-controls', 'explainer-tooltip-region');
            
            // Add comprehensive description
            const description = document.getElementById('explainer-description');
            if (description) {
                description.textContent = 'Activate this button to enable AI text explanations. When enabled, select any text to receive an AI-generated explanation in a popup tooltip.';
            }
        }
        
        // Add landmark roles for better navigation
        const mainContent = document.querySelector('main, #main, #content, article, .content');
        if (mainContent && !mainContent.getAttribute('role')) {
            mainContent.setAttribute('role', 'main');
        }
    }
    
    // Conflict resolution for common plugins
    function resolvePluginConflicts() {
        // Save original functions that might be overridden
        const originalGetSelection = window.getSelection;
        
        // Protect against jQuery conflicts
        if (window.jQuery) {
            const $ = window.jQuery;
            // Ensure our event handlers don't get overridden
            $(document).on('ready', function() {
                // Re-initialize if needed
                if (!elements.toggleButton) {
                    init();
                }
            });
        }
        
        // Protect against selection library conflicts
        if (window.rangy) {
            // Use native selection if rangy is present
            window.getSelection = originalGetSelection;
        }
        
        // Elementor compatibility
        if (window.elementorFrontend) {
            window.elementorFrontend.hooks.addAction('frontend/element_ready/global', function() {
                // Re-initialize after Elementor loads
                utils.requestIdleCallback(init);
            });
        }
        
        // Gutenberg compatibility
        if (window.wp && window.wp.domReady) {
            window.wp.domReady(function() {
                // Re-initialize after Gutenberg loads
                utils.requestIdleCallback(init);
            });
        }
    }

    // Public API
    window.ExplainerPlugin = {
        init: init,
        toggle: togglePlugin,
        config: config,
        state: state,
        clearSelection: clearPreviousSelection,
        resolveConflicts: resolvePluginConflicts
    };
    
        // Check if tooltip functions are available
    function checkTooltipAvailability() {
        debugLog('Checking tooltip availability');
        debugLog('ExplainerPlugin namespace:', window.ExplainerPlugin);
        debugLog('showTooltip function:', window.ExplainerPlugin?.showTooltip);
        
        if (!window.ExplainerPlugin?.showTooltip) {
            debugLog('Tooltip functions not found, loading dynamically...');
            loadTooltipScript();
        } else {
            debugLog('Tooltip functions are available!');
        }
    }
    
    // Load tooltip script dynamically as fallback
    function loadTooltipScript() {
        if (window.tooltipScriptLoading) return; // Prevent multiple loads
        window.tooltipScriptLoading = true;
        
        debugLog('Loading tooltip script dynamically');
        
        const scriptUrl = explainerAjax.settings?.tooltip_url || '/wp-content/plugins/explainer-plugin/assets/js/tooltip-test.js';
        debugLog('Script URL:', scriptUrl);
        
        const script = document.createElement('script');
        script.src = scriptUrl;
        script.onload = function() {
            debugLog('Tooltip script loaded successfully');
            debugLog('Checking if functions are now available:', !!window.ExplainerPlugin?.showTooltip);
            window.tooltipScriptLoading = false;
        };
        script.onerror = function(error) {
            console.error('ExplainerPlugin: Failed to load tooltip script', error);
            console.error('ExplainerPlugin: Script URL was:', scriptUrl);
            window.tooltipScriptLoading = false;
        };
        
        debugLog('Adding script to head');
        document.head.appendChild(script);
    }

// Auto-initialize when DOM is ready
    debugLog('Auto-initialization starting, readyState:', document.readyState);
    
    if (document.readyState === 'loading') {
        debugLog('DOM still loading, waiting for DOMContentLoaded...');
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('DOMContentLoaded fired, initializing...');
            resolvePluginConflicts();
            init();
            checkTooltipAvailability();
        });
    } else {
        debugLog('DOM already ready, initializing immediately...');
        resolvePluginConflicts();
        init();
        checkTooltipAvailability();
    }
    
})();