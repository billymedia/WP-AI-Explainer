/**
 * AI Explainer Plugin - Tooltip System
 * Handles tooltip display, positioning, and interactions
 */

(function() {
    'use strict';
    
    // Extend the main plugin namespace
    window.ExplainerPlugin = window.ExplainerPlugin || {};
    
    // Tooltip configuration
    const tooltipConfig = {
        maxWidth: 300,
        minWidth: 200,
        offset: 30, // Increased offset for better spacing
        animationDuration: 300,
        autoCloseDelay: 10000, // 10 seconds
        zIndex: 999998
    };
    
    // Tooltip state
    let currentTooltip = null;
    let autoCloseTimer = null;
    let isTooltipVisible = false;
    let localizedStrings = null;
    
    /**
     * Load localized strings from server
     */
    function loadLocalizedStrings() {
        if (localizedStrings) {
            return Promise.resolve(localizedStrings);
        }
        
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', window.explainerAjax?.ajaxurl || '/wp-admin/admin-ajax.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success && response.data.strings) {
                            localizedStrings = response.data.strings;
                            resolve(localizedStrings);
                        } else {
                            // Fallback to English
                            localizedStrings = {
                                explanation: 'Explanation',
                                loading: 'Loading...',
                                error: 'Error',
                                disclaimer: 'AI-generated content may not always be accurate',
                                powered_by: 'Powered by'
                            };
                            resolve(localizedStrings);
                        }
                    } catch (e) {
                        // Fallback to English
                        localizedStrings = {
                            explanation: 'Explanation',
                            loading: 'Loading...',
                            error: 'Error',
                            disclaimer: 'AI-generated content may not always be accurate',
                            powered_by: 'Powered by'
                        };
                        resolve(localizedStrings);
                    }
                } else {
                    reject(new Error('Failed to load localized strings'));
                }
            };
            
            xhr.onerror = function() {
                reject(new Error('Network error'));
            };
            
            const data = 'action=explainer_get_localized_strings' + 
                        (window.explainerAjax?.nonce ? '&nonce=' + encodeURIComponent(window.explainerAjax.nonce) : '');
            xhr.send(data);
        });
    }
    
    /**
     * Get localized string
     */
    function getLocalizedString(key, fallback = '') {
        if (localizedStrings && localizedStrings[key]) {
            return localizedStrings[key];
        }
        return fallback || key;
    }
    
    /**
     * Show tooltip with content
     */
    function showTooltip(content, position, type = 'explanation', options = {}) {
        // Clear any existing tooltip
        hideTooltip();
        
        // Create tooltip element
        currentTooltip = createTooltipElement(content, type, options);
        
        // Add to DOM (starts invisible due to CSS opacity: 0)
        document.body.appendChild(currentTooltip);
        
        // Position tooltip while invisible
        positionTooltip(currentTooltip, position);
        
        // Force browser to process position changes before making visible
        // This ensures tooltip appears at final position without sliding
        currentTooltip.offsetHeight; // Force reflow
        
        // Show tooltip with animation
        setTimeout(() => {
            currentTooltip.classList.add('visible');
            isTooltipVisible = true;
        }, 10);
        
        // Don't set auto-close timer - let user control when to close
        
        // Add event listeners
        attachTooltipEventListeners();
        
        // Focus management for accessibility
        if (type !== 'loading') {
            focusTooltip();
        }
        
        return currentTooltip;
    }
    
    /**
     * Hide tooltip
     */
    function hideTooltip() {
        if (!currentTooltip) {
            return;
        }
        
        clearAutoCloseTimer();
        removeTooltipEventListeners();
        
        // Animate out
        currentTooltip.classList.remove('visible');
        isTooltipVisible = false;
        
        // Remove from DOM after animation
        setTimeout(() => {
            if (currentTooltip && currentTooltip.parentNode) {
                currentTooltip.parentNode.removeChild(currentTooltip);
            }
            currentTooltip = null;
        }, tooltipConfig.animationDuration);
    }
    
    /**
     * Create tooltip element with accessibility features
     */
    function createTooltipElement(content, type, options = {}) {
        const tooltip = document.createElement('div');
        tooltip.className = `explainer-tooltip ${type}`;
        tooltip.setAttribute('role', 'tooltip');
        tooltip.setAttribute('aria-live', 'polite');
        tooltip.setAttribute('aria-atomic', 'true');
        
        // Add unique ID for accessibility
        const tooltipId = 'explainer-tooltip-' + Date.now();
        tooltip.id = tooltipId;
        
        // Add accessibility attributes
        tooltip.setAttribute('aria-describedby', tooltipId + '-content');
        tooltip.setAttribute('tabindex', '-1');
        
        // Create tooltip structure
        const header = createTooltipHeader(type);
        const contentDiv = createTooltipContent(content, type, options);
        
        // Add ID to content for aria-describedby
        contentDiv.id = tooltipId + '-content';
        
        tooltip.appendChild(header);
        tooltip.appendChild(contentDiv);
        
        // Add footer for successful explanations only
        if (type === 'explanation') {
            const footer = createTooltipFooter(options);
            if (footer) {
                tooltip.appendChild(footer);
            }
        }
        
        return tooltip;
    }
    
    /**
     * Create tooltip header
     */
    function createTooltipHeader(type) {
        const header = document.createElement('div');
        header.className = 'explainer-tooltip-header';
        
        // Title based on type
        const title = document.createElement('span');
        title.className = 'explainer-tooltip-title';
        
        switch (type) {
            case 'loading':
                title.textContent = getLocalizedString('loading', 'Loading...');
                break;
            case 'error':
                title.textContent = getLocalizedString('error', 'Error');
                break;
            case 'explanation':
            default:
                title.textContent = getLocalizedString('explanation', 'Explanation');
                break;
        }
        
        header.appendChild(title);
        
        // Close button (not for loading states)
        if (type !== 'loading') {
            const closeButton = document.createElement('button');
            closeButton.className = 'explainer-tooltip-close';
            closeButton.setAttribute('aria-label', 'Close explanation');
            closeButton.setAttribute('type', 'button');
            closeButton.setAttribute('tabindex', '0');
            closeButton.innerHTML = '<span aria-hidden="true">×</span>';
            closeButton.addEventListener('click', hideTooltip);
            closeButton.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    hideTooltip();
                }
            });
            header.appendChild(closeButton);
        }
        
        return header;
    }
    
    /**
     * Create tooltip content
     */
    function createTooltipContent(content, type, options = {}) {
        const contentDiv = document.createElement('div');
        contentDiv.className = 'explainer-tooltip-content';
        
        if (type === 'loading') {
            // Loading state with spinner
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'explainer-loading';
            
            const spinner = document.createElement('div');
            spinner.className = 'explainer-spinner';
            spinner.setAttribute('aria-hidden', 'true');
            
            const loadingText = document.createElement('span');
            loadingText.textContent = content;
            
            loadingDiv.appendChild(spinner);
            loadingDiv.appendChild(loadingText);
            contentDiv.appendChild(loadingDiv);
        } else {
            // Regular content
            if (typeof content === 'string') {
                // Convert line breaks to paragraphs
                const paragraphs = content.split('\n').filter(p => p.trim().length > 0);
                paragraphs.forEach(paragraph => {
                    const p = document.createElement('p');
                    p.textContent = paragraph.trim();
                    contentDiv.appendChild(p);
                });
            } else {
                // HTML content
                contentDiv.appendChild(content);
            }
        }
        
        return contentDiv;
    }
    
    /**
     * Create tooltip footer
     */
    function createTooltipFooter(options = {}) {
        const { showDisclaimer = false, showProvider = false, provider = '' } = options;
        
        // Only create footer if at least one option is enabled
        if (!showDisclaimer && !showProvider) {
            return null;
        }
        
        const footerDiv = document.createElement('div');
        footerDiv.className = 'explainer-tooltip-footer';
        
        if (showDisclaimer) {
            const disclaimerDiv = document.createElement('div');
            disclaimerDiv.className = 'explainer-disclaimer';
            disclaimerDiv.textContent = getLocalizedString('disclaimer', 'AI-generated content may not always be accurate');
            footerDiv.appendChild(disclaimerDiv);
        }
        
        if (showProvider && provider) {
            const providerDiv = document.createElement('div');
            providerDiv.className = 'explainer-provider';
            
            // Capitalize provider name
            const providerName = provider.charAt(0).toUpperCase() + provider.slice(1);
            const poweredByText = getLocalizedString('powered_by', 'Powered by');
            providerDiv.textContent = `${poweredByText} ${providerName}`;
            footerDiv.appendChild(providerDiv);
        }
        
        return footerDiv;
    }
    
    /**
     * Position tooltip relative to selection
     */
    function positionTooltip(tooltip, position) {
        if (!position) {
            // Fallback to center of screen
            position = {
                x: window.innerWidth / 2,
                y: window.innerHeight / 2,
                scrollX: window.scrollX,
                scrollY: window.scrollY
            };
        }
        
        // Get tooltip dimensions
        const tooltipRect = tooltip.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        // Calculate position
        let left = position.x + position.scrollX;
        let top = position.y + position.scrollY;
        
        // Adjust for tooltip size and offset
        left -= tooltipRect.width / 2;
        top += tooltipConfig.offset; // Position below the selection
        
        // Viewport boundary checks
        const adjustedPosition = adjustForViewport(left, top, tooltipRect, viewportWidth, viewportHeight);
        left = adjustedPosition.left;
        top = adjustedPosition.top;
        
        // Apply position
        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
        
        // Update arrow position if needed
        updateArrowPosition(tooltip, position, adjustedPosition);
    }
    
    /**
     * Adjust tooltip position for viewport boundaries
     */
    function adjustForViewport(left, top, tooltipRect, viewportWidth, viewportHeight) {
        const scrollX = window.scrollX;
        const scrollY = window.scrollY;
        const margin = 10;
        
        // Horizontal adjustments
        if (left < scrollX + margin) {
            left = scrollX + margin;
        } else if (left + tooltipRect.width > scrollX + viewportWidth - margin) {
            left = scrollX + viewportWidth - tooltipRect.width - margin;
        }
        
        // Vertical adjustments
        if (top < scrollY + margin) {
            top = scrollY + margin;
        } else if (top + tooltipRect.height > scrollY + viewportHeight - margin) {
            top = scrollY + viewportHeight - tooltipRect.height - margin;
        }
        
        return { left, top };
    }
    
    /**
     * Update arrow position based on tooltip adjustment
     */
    function updateArrowPosition(tooltip, originalPosition, adjustedPosition) {
        const tooltipRect = tooltip.getBoundingClientRect();
        const originalLeft = originalPosition.x + originalPosition.scrollX - tooltipRect.width / 2;
        const actualLeft = adjustedPosition.left;
        
        // Calculate arrow offset
        const arrowOffset = originalLeft - actualLeft;
        const maxOffset = tooltipRect.width / 2 - 20; // 20px from edge
        
        // Apply arrow positioning
        const clampedOffset = Math.max(-maxOffset, Math.min(maxOffset, arrowOffset));
        tooltip.style.setProperty('--arrow-offset', clampedOffset + 'px');
    }
    
    /**
     * Set auto-close timer
     */
    function setAutoCloseTimer() {
        clearAutoCloseTimer();
        autoCloseTimer = setTimeout(() => {
            hideTooltip();
        }, tooltipConfig.autoCloseDelay);
    }
    
    /**
     * Clear auto-close timer
     */
    function clearAutoCloseTimer() {
        if (autoCloseTimer) {
            clearTimeout(autoCloseTimer);
            autoCloseTimer = null;
        }
    }
    
    /**
     * Attach tooltip event listeners
     */
    function attachTooltipEventListeners() {
        if (!currentTooltip) return;
        
        // Remove auto-close hover listeners since we're not using auto-close
        
        // Touch events for mobile (no auto-close needed)
        currentTooltip.addEventListener('touchend', handleTooltipTouchEnd);
        
        // Mobile swipe dismissal
        setupSwipeGestures(currentTooltip);
        
        // Keyboard navigation
        currentTooltip.addEventListener('keydown', handleTooltipKeydown);
        
        // Focus management (no auto-close)
    }
    
    /**
     * Remove tooltip event listeners
     */
    function removeTooltipEventListeners() {
        if (!currentTooltip) return;
        
        currentTooltip.removeEventListener('touchend', handleTooltipTouchEnd);
        currentTooltip.removeEventListener('keydown', handleTooltipKeydown);
    }
    
    /**
     * Handle tooltip keyboard navigation
     */
    function handleTooltipKeydown(event) {
        switch (event.key) {
            case 'Escape':
                event.preventDefault();
                hideTooltip();
                break;
            case 'Tab':
                // Handle tab navigation within tooltip
                handleTooltipTabNavigation(event);
                break;
        }
    }
    
    /**
     * Handle tab navigation within tooltip
     */
    function handleTooltipTabNavigation(event) {
        const focusableElements = currentTooltip.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length === 0) {
            return;
        }
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        if (event.shiftKey) {
            // Shift+Tab
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
    
    /**
     * Focus tooltip for accessibility
     */
    function focusTooltip() {
        if (!currentTooltip) return;
        
        // Announce tooltip to screen readers
        announceTooltip();
        
        // Focus close button if available
        const closeButton = currentTooltip.querySelector('.explainer-tooltip-close');
        if (closeButton) {
            closeButton.focus();
        } else {
            // Make tooltip focusable
            currentTooltip.setAttribute('tabindex', '-1');
            currentTooltip.focus();
        }
    }
    
    /**
     * Announce tooltip to screen readers
     */
    function announceTooltip() {
        const content = currentTooltip.querySelector('.explainer-tooltip-content');
        if (content) {
            const announcement = document.createElement('div');
            announcement.className = 'explainer-sr-only';
            announcement.setAttribute('aria-live', 'assertive');
            announcement.setAttribute('aria-atomic', 'true');
            announcement.textContent = 'Explanation dialog opened. ' + content.textContent;
            
            document.body.appendChild(announcement);
            
            setTimeout(() => {
                if (announcement.parentNode) {
                    announcement.parentNode.removeChild(announcement);
                }
            }, 1000);
        }
    }
    
    /**
     * Check if tooltip is currently visible
     */
    function isVisible() {
        return isTooltipVisible;
    }
    
    /**
     * Get current tooltip element
     */
    function getCurrentTooltip() {
        return currentTooltip;
    }
    
    /**
     * Update tooltip content (for loading -> success transitions)
     */
    function updateTooltipContent(content, type = 'explanation', options = {}) {
        if (!currentTooltip) return;
        
        // Update tooltip class
        currentTooltip.className = `explainer-tooltip ${type}`;
        
        // Update header
        const header = currentTooltip.querySelector('.explainer-tooltip-header');
        if (header) {
            header.parentNode.removeChild(header);
        }
        
        // Update content
        const contentDiv = currentTooltip.querySelector('.explainer-tooltip-content');
        if (contentDiv) {
            contentDiv.parentNode.removeChild(contentDiv);
        }
        
        // Remove existing footer
        const footer = currentTooltip.querySelector('.explainer-tooltip-footer');
        if (footer) {
            footer.parentNode.removeChild(footer);
        }
        
        // Add new header and content
        const newHeader = createTooltipHeader(type);
        const newContent = createTooltipContent(content, type, options);
        
        currentTooltip.appendChild(newHeader);
        currentTooltip.appendChild(newContent);
        
        // Add footer for successful explanations only
        if (type === 'explanation') {
            const newFooter = createTooltipFooter(options);
            if (newFooter) {
                currentTooltip.appendChild(newFooter);
            }
        }
        
        // Reattach event listeners
        attachTooltipEventListeners();
        
        // Recalculate position after content update (accounts for footer height changes)
        if (window.ExplainerPlugin.state && window.ExplainerPlugin.state.selectionPosition) {
            positionTooltip(currentTooltip, window.ExplainerPlugin.state.selectionPosition);
        }
        
        // Force browser to process position changes before making visible
        currentTooltip.offsetHeight; // Force reflow
        
        // Ensure tooltip is visible after repositioning
        currentTooltip.classList.add('visible');
        
        // Focus tooltip for non-loading states (no auto-close)
        if (type !== 'loading') {
            focusTooltip();
        }
    }
    
    /**
     * Handle tooltip touch end
     */
    function handleTooltipTouchEnd(event) {
        // No auto-close functionality needed
    }
    
    /**
     * Setup swipe gestures for mobile dismissal
     */
    function setupSwipeGestures(tooltip) {
        let startX = 0;
        let startY = 0;
        let endX = 0;
        let endY = 0;
        
        tooltip.addEventListener('touchstart', (event) => {
            startX = event.touches[0].clientX;
            startY = event.touches[0].clientY;
        });
        
        tooltip.addEventListener('touchmove', (event) => {
            endX = event.touches[0].clientX;
            endY = event.touches[0].clientY;
        });
        
        tooltip.addEventListener('touchend', (event) => {
            const deltaX = endX - startX;
            const deltaY = endY - startY;
            const minSwipeDistance = 50;
            
            // Swipe up or down to dismiss
            if (Math.abs(deltaY) > minSwipeDistance && Math.abs(deltaY) > Math.abs(deltaX)) {
                if (deltaY < 0 || deltaY > 0) { // Up or down swipe
                    hideTooltip();
                }
            }
            
            // Swipe left or right to dismiss
            if (Math.abs(deltaX) > minSwipeDistance && Math.abs(deltaX) > Math.abs(deltaY)) {
                if (deltaX < 0 || deltaX > 0) { // Left or right swipe
                    hideTooltip();
                }
            }
        });
    }
    
    /**
     * Handle window resize
     */
    function handleWindowResize() {
        if (currentTooltip && isTooltipVisible) {
            // Reposition tooltip
            const position = window.ExplainerPlugin.state?.selectionPosition;
            if (position) {
                positionTooltip(currentTooltip, position);
            }
        }
    }
    
    /**
     * Handle window scroll
     */
    function handleWindowScroll() {
        if (currentTooltip && isTooltipVisible) {
            // Update position for scroll changes
            const position = window.ExplainerPlugin.state?.selectionPosition;
            if (position) {
                positionTooltip(currentTooltip, position);
            }
        }
    }
    
    /**
     * Initialize tooltip system
     */
    function initializeTooltips() {
        // Load localized strings first
        loadLocalizedStrings().catch(error => {
            console.warn('ExplainerPlugin: Failed to load localized strings, using fallback:', error);
        });
        
        // Add window event listeners
        window.addEventListener('resize', handleWindowResize);
        window.addEventListener('scroll', handleWindowScroll);
        
        // Add global click listener for outside clicks
        document.addEventListener('click', (event) => {
            if (currentTooltip && !currentTooltip.contains(event.target) && 
                !event.target.closest('.explainer-toggle')) {
                hideTooltip();
            }
        });
        
        // Add global escape key listener
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isTooltipVisible) {
                hideTooltip();
            }
        });
    }
    
    /**
     * Cleanup tooltip system
     */
    function cleanupTooltips() {
        hideTooltip();
        window.removeEventListener('resize', handleWindowResize);
        window.removeEventListener('scroll', handleWindowScroll);
    }
    
    // Public API
    window.ExplainerPlugin.showTooltip = showTooltip;
    window.ExplainerPlugin.hideTooltip = hideTooltip;
    window.ExplainerPlugin.updateTooltipContent = updateTooltipContent;
    window.ExplainerPlugin.isTooltipVisible = isVisible;
    window.ExplainerPlugin.getCurrentTooltip = getCurrentTooltip;
    window.ExplainerPlugin.initializeTooltips = initializeTooltips;
    window.ExplainerPlugin.cleanupTooltips = cleanupTooltips;
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeTooltips);
    } else {
        initializeTooltips();
    }
    
})();