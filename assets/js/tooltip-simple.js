/**
 * Frontend tooltip that matches admin preview design
 */

// Extend the main plugin namespace
window.ExplainerPlugin = window.ExplainerPlugin || {};

// Frontend tooltip functionality that matches admin design
function showTooltip(content, position, type = 'explanation') {
    // Remove any existing tooltip
    hideTooltip();
    
    // Create tooltip element with same structure as admin
    const tooltip = document.createElement('div');
    tooltip.className = 'explainer-tooltip visible';
    tooltip.setAttribute('role', 'tooltip');
    tooltip.setAttribute('aria-live', 'polite');
    
    // Create header structure (matches admin)
    const header = document.createElement('div');
    header.className = 'explainer-tooltip-header';
    
    const title = document.createElement('span');
    title.className = 'explainer-tooltip-title';
    title.textContent = type === 'error' ? 'Error' : 'Explanation';
    
    const closeButton = document.createElement('button');
    closeButton.className = 'explainer-tooltip-close';
    closeButton.setAttribute('aria-label', 'Close explanation');
    closeButton.setAttribute('type', 'button');
    closeButton.setAttribute('tabindex', '0');
    closeButton.innerHTML = '×';
    closeButton.addEventListener('click', hideTooltip);
    closeButton.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            hideTooltip();
        }
    });
    
    header.appendChild(title);
    header.appendChild(closeButton);
    
    // Create content structure (matches admin)
    const contentDiv = document.createElement('div');
    contentDiv.className = 'explainer-tooltip-content';
    contentDiv.textContent = content;
    
    // Add header and content to tooltip
    tooltip.appendChild(header);
    tooltip.appendChild(contentDiv);
    
    // Apply colors from admin settings
    applyTooltipColors(tooltip);
    
    // Add to DOM
    document.body.appendChild(tooltip);
    
    // Position tooltip
    positionTooltip(tooltip, position);
    
    // Animate in
    requestAnimationFrame(() => {
        tooltip.classList.add('visible');
    });
    
    // Store reference
    window.currentExplainerTooltip = tooltip;
    
    // Add click outside to close functionality
    setTimeout(() => {
        document.addEventListener('click', handleClickOutside);
        document.addEventListener('keydown', handleKeyDown);
    }, 100); // Small delay to prevent immediate closure
}

// Apply colors and font from admin settings
function applyTooltipColors(tooltip) {
    // Get colors from admin settings if available
    let bgColor = '#333333';
    let textColor = '#ffffff';
    
    if (typeof explainerAjax !== 'undefined' && explainerAjax.settings) {
        bgColor = explainerAjax.settings.tooltip_bg_color || bgColor;
        textColor = explainerAjax.settings.tooltip_text_color || textColor;
    }
    
    // Detect site's paragraph font
    const siteFont = detectSiteFont();
    
    // Set CSS custom properties so the styles work
    document.documentElement.style.setProperty('--explainer-tooltip-bg-color', bgColor);
    document.documentElement.style.setProperty('--explainer-tooltip-text-color', textColor);
    document.documentElement.style.setProperty('--explainer-site-font', siteFont);
}

// Detect the site's paragraph font
function detectSiteFont() {
    // Try to find a paragraph element to get its font
    const paragraph = document.querySelector('p, article p, main p, .content p, .entry-content p, .post-content p');
    
    if (paragraph) {
        const computedStyle = window.getComputedStyle(paragraph);
        const fontFamily = computedStyle.getPropertyValue('font-family');
        
        if (fontFamily && fontFamily !== 'inherit') {
            return fontFamily;
        }
    }
    
    // Fallback: check body font
    const body = document.body;
    if (body) {
        const bodyStyle = window.getComputedStyle(body);
        const bodyFont = bodyStyle.getPropertyValue('font-family');
        
        if (bodyFont && bodyFont !== 'inherit') {
            return bodyFont;
        }
    }
    
    // Final fallback to system font
    return '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
}

function hideTooltip() {
    const tooltip = window.currentExplainerTooltip;
    if (tooltip && tooltip.parentNode) {
        // Remove event listeners
        document.removeEventListener('click', handleClickOutside);
        document.removeEventListener('keydown', handleKeyDown);
        
        // Animate out
        tooltip.classList.remove('visible');
        tooltip.classList.add('hidden');
        
        // Remove from DOM after animation
        setTimeout(() => {
            if (tooltip.parentNode) {
                tooltip.parentNode.removeChild(tooltip);
            }
        }, 300); // Match CSS transition duration
        
        window.currentExplainerTooltip = null;
    }
}

// Handle clicks outside the tooltip
function handleClickOutside(event) {
    const tooltip = window.currentExplainerTooltip;
    if (tooltip && !tooltip.contains(event.target)) {
        // Don't close if clicking on the toggle button
        if (event.target.closest('.explainer-toggle')) {
            return;
        }
        hideTooltip();
    }
}

// Handle keyboard events for tooltip
function handleKeyDown(event) {
    if (event.key === 'Escape') {
        hideTooltip();
    }
}

function positionTooltip(tooltip, position) {
    // Add initial positioning styles
    tooltip.style.position = 'fixed';
    tooltip.style.zIndex = '999999';
    
    if (!position) {
        // Center on screen as fallback
        tooltip.style.top = '50px';
        tooltip.style.left = '50%';
        tooltip.style.transform = 'translateX(-50%)';
        return;
    }
    
    // Force a layout to get accurate measurements
    tooltip.style.opacity = '0';
    tooltip.style.visibility = 'visible';
    
    // Get tooltip dimensions after it's been added to DOM
    const tooltipRect = tooltip.getBoundingClientRect();
    const viewport = {
        width: window.innerWidth,
        height: window.innerHeight
    };
    
    // The position.x is already the center of the selection (calculated in trackSelectionPosition)
    // Position tooltip centered below the selection
    let left = position.x - tooltipRect.width / 2;
    let top = position.y + (position.height || 0) + 10; // 10px below selection
    
    // Ensure tooltip stays within viewport horizontally
    if (left < 20) {
        left = 20;
    } else if (left + tooltipRect.width > viewport.width - 20) {
        left = viewport.width - tooltipRect.width - 20;
    }
    
    // Ensure tooltip stays within viewport vertically
    if (top + tooltipRect.height > viewport.height - 20) {
        // Position above selection instead
        top = position.y - tooltipRect.height - 10;
        tooltip.classList.add('above');
    } else {
        tooltip.classList.remove('above');
    }
    
    // Ensure tooltip doesn't go above viewport
    if (top < 20) {
        top = 20;
    }
    
    // Apply final position
    tooltip.style.left = left + 'px';
    tooltip.style.top = top + 'px';
    tooltip.style.transform = 'none';
    
    // Make tooltip visible again
    tooltip.style.opacity = '';
    tooltip.style.visibility = '';
}

function updateTooltipContent(content, type = 'explanation') {
    const tooltip = window.currentExplainerTooltip;
    if (tooltip) {
        // Update content
        const contentDiv = tooltip.querySelector('.explainer-tooltip-content');
        if (contentDiv) {
            contentDiv.textContent = content;
        }
        
        // Update title based on type
        const title = tooltip.querySelector('.explainer-tooltip-title');
        if (title) {
            title.textContent = type === 'error' ? 'Error' : 'Explanation';
        }
        
        // Update tooltip class for styling
        tooltip.className = `explainer-tooltip visible ${type}`;
        
        // Reapply colors from admin settings
        applyTooltipColors(tooltip);
    }
}

// Register functions
window.ExplainerPlugin.showTooltip = showTooltip;
window.ExplainerPlugin.hideTooltip = hideTooltip;
window.ExplainerPlugin.updateTooltipContent = updateTooltipContent;
window.ExplainerPlugin.isTooltipVisible = () => !!window.currentExplainerTooltip;
window.ExplainerPlugin.getCurrentTooltip = () => window.currentExplainerTooltip;