=== AI Explainer Plugin ===
Contributors: billypatel
Tags: ai, explanation, tooltip, openai, claude, text-selection, accessibility, multi-provider
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight WordPress plugin that uses multiple AI providers (OpenAI, Claude) to explain highlighted text via interactive tooltips. Select text, get AI-generated explanations with customisable disclaimers and provider attribution.

== Description ==

The AI Explainer Plugin transforms your WordPress site into an interactive learning experience. Visitors can simply select any text on your site and receive instant AI-generated explanations via elegant, customisable tooltips.

**Key Features:**

* **Multi-Provider AI Support**: Choose between OpenAI (GPT-3.5, GPT-4) and Claude (Sonnet, Haiku) models
* **Interactive Tooltips**: Smart positioning with manual user control (no auto-close)
* **Customisable Appearance**: Configure tooltip colours (background, text, footer), positioning, disclaimers, and provider attribution
* **Multi-Language Support**: Localized tooltips in 7 languages (English US/UK, Spanish, German, French, Hindi, Chinese)
* **Secure Integration**: API keys encrypted and never exposed to frontend
* **Advanced Admin Interface**: Tabbed settings with Basic and Advanced configuration options
* **Custom Prompt Templates**: Create personalised AI prompts with `{{snippet}}` placeholders
* **Smart Content Filtering**: Configurable text selection rules and content exclusions
* **Performance Optimised**: Intelligent caching, rate limiting, and minimal page load impact
* **Theme Compatible**: Responsive design that works seamlessly with any WordPress theme
* **Accessibility First**: WCAG AA compliant with full keyboard navigation and screen reader support
* **Mobile Optimised**: Touch-friendly interface with swipe gestures for all devices
* **Privacy Focused**: GDPR compliant with minimal data collection and secure processing

**How It Works:**

1. Install and activate the plugin
2. Choose your AI provider (OpenAI or Claude) and add your API key
3. Select your preferred language for tooltip interface elements
4. Customise tooltip appearance, prompts, and footer options
5. Visitors select text to get instant AI explanations
6. Explanations appear in responsive tooltips with localized disclaimers and attribution

**Perfect For:**

* Educational websites and online courses
* Technical documentation and knowledge bases
* News, magazine, and publishing sites
* Professional services with complex terminology
* E-commerce sites with detailed product information
* Any website with content that benefits from contextual explanations

**Technical Highlights:**

* **Multi-Provider Architecture**: Support for OpenAI and Claude with easy extensibility
* **Provider Factory Pattern**: Clean, maintainable code architecture
* **Encrypted API Storage**: WordPress salts-based encryption for maximum security
* **Smart Caching System**: Configurable response caching to reduce API costs
* **Advanced Rate Limiting**: Separate limits for logged-in and anonymous users
* **Vanilla JavaScript**: No framework dependencies for maximum compatibility
* **Responsive CSS**: Mobile-first design with CSS custom properties
* **WordPress Standards**: Follows all WordPress coding and security standards
* **Comprehensive Admin Interface**: Tabbed settings with real-time validation
* **Multi-Language Interface**: Localized tooltip headers, disclaimers, and provider attribution
* **Debug Tools**: Built-in logging and troubleshooting capabilities

== Installation ==

**From WordPress Admin:**

1. Go to Plugins → Add New
2. Search for "AI Explainer Plugin"
3. Click "Install Now" and then "Activate"
4. Go to Settings → Explainer Settings
5. Choose your AI provider (OpenAI or Claude)
6. Select your preferred language for tooltip interface
7. Enter your API key and select a model
8. Test your API key to ensure it works
9. Customise appearance and footer options
10. Configure advanced settings as needed
11. Save your settings and test on your site

**First Time Setup:**

After installation, you'll need to:
- Sign up for an API account with OpenAI or Claude
- Generate an API key from your chosen provider
- Configure the plugin settings in WordPress admin
- Test the functionality on your site
- Optionally customise appearance to match your theme

**Manual Installation:**

1. Download the plugin zip file
2. Upload to `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu
4. Configure settings as described above

**Getting API Keys:**

**For OpenAI:**
1. Visit https://platform.openai.com/
2. Create an account or log in
3. Navigate to API Keys section
4. Create a new secret key
5. Copy and paste into plugin settings

**For Claude (Anthropic):**
1. Visit https://console.anthropic.com/
2. Create an account or log in
3. Navigate to API Keys section
4. Generate a new API key
5. Copy and paste into plugin settings

== Frequently Asked Questions ==

= Do I need an API key? =

Yes, you need an API key from either OpenAI or Claude (Anthropic) to use this plugin. You can choose your preferred provider in the settings.

= How much does it cost to use? =

The plugin itself is free. You only pay for API usage from your chosen provider (OpenAI or Claude) based on your usage volume. The plugin includes intelligent caching and rate limiting to minimise costs.

= Will this slow down my website? =

No, the plugin is designed for minimal performance impact. Scripts load asynchronously and only when needed. Target is <100ms added to page load time.

= Does it work with my theme? =

Yes, the plugin is designed to work with any WordPress theme. It uses CSS namespacing to prevent conflicts.

= Is it accessible? =

Yes, the plugin is WCAG 2.1 AA compliant with full keyboard navigation and screen reader support.

= Can I customise the appearance? =

Yes, the comprehensive admin panel includes options to customise tooltip colours (background, text, and footer text), positioning, styling, custom prompts, disclaimers, and provider attribution to match your site perfectly.

= What about privacy and GDPR? =

The plugin is designed with privacy in mind and includes comprehensive GDPR compliance features. Selected text is sent to your chosen AI provider for processing but is not stored permanently by the plugin.

= Can I limit usage? =

Yes, built-in rate limiting prevents abuse. You can configure different limits for logged-in and anonymous users.

= Does it work on mobile? =

Yes, the plugin is fully responsive and touch-friendly on all mobile devices.

= What if the API is down? =

The plugin includes robust error handling and will show user-friendly messages if the API is unavailable. With multi-provider support, you can also switch providers if needed.

= Can I use this plugin on a multisite installation? =

Yes, the plugin supports WordPress multisite installations. Each site can have its own API keys and settings.

= Does this plugin work with caching plugins? =

Yes, the plugin is designed to work with popular caching plugins. It includes its own intelligent caching system for API responses to improve performance.

= Will this plugin slow down my website? =

No, the plugin is designed for minimal performance impact. Scripts only load when needed, and the target is less than 100ms added to page load time.

= Can I disable the plugin for certain user roles? =

Yes, you can configure user permissions and rate limits in the Advanced settings. You can also disable the feature entirely for anonymous users.

= Is this plugin compatible with page builders? =

Yes, the plugin works with popular page builders like Elementor, Gutenberg, and others. It uses CSS selectors to target content areas.

= Can I translate this plugin? =

Yes, the plugin is fully internationalized and ready for translation. The tooltip interface is already localized in 7 languages (English US/UK, Spanish, German, French, Hindi, Chinese), and you can contribute additional translations or create your own using WordPress translation tools.

== Screenshots ==

1. **Text Selection in Action** - User selects text and sees the explanation tooltip with footer
2. **Basic Settings Tab** - Provider selection, API keys, and essential configuration
3. **Advanced Settings Tab** - Custom prompts, appearance, and performance options
4. **Tooltip with Footer** - Configurable disclaimers and provider attribution
5. **Toggle Button** - Users can enable/disable the feature per page
6. **Mobile Experience** - Responsive design with touch gestures on mobile devices

== Changelog ==

= 1.0.0 =
* **Multi-Provider Support**: OpenAI and Claude integration with model selection
* **Advanced Tooltip System**: Smart positioning, manual control, and configurable footers
* **Multi-Language Interface**: Localized tooltips in 7 languages with real-time language switching
* **Comprehensive Admin Interface**: Tabbed settings with Basic and Advanced options
* **Custom Prompt Templates**: Personalised AI prompts with validation
* **Encrypted API Storage**: Secure WordPress salts-based encryption
* **Provider Factory Architecture**: Clean, extensible codebase for future providers
* **Enhanced Security**: Comprehensive validation, sanitisation, and capability checks
* **Performance Optimisation**: Intelligent caching, rate limiting, and minimal impact
* **Accessibility Excellence**: WCAG AA compliance with full keyboard and screen reader support
* **Mobile Excellence**: Touch-friendly interface with swipe gestures
* **Theme Compatibility**: Responsive design that works with any WordPress theme
* **Privacy Compliance**: GDPR-ready with minimal data collection
* **Debug Tools**: Comprehensive logging and troubleshooting capabilities

== Upgrade Notice ==

= 1.0.0 =
Initial release of the AI Explainer Plugin. Install to start providing AI-powered explanations on your WordPress site.

== External Services & Privacy ==

**Third-Party Service Usage:**

This plugin connects to external AI services to provide explanations. When a user selects text and requests an explanation, the selected text is sent to your chosen AI provider's API for processing.

**Supported Services:**
* **OpenAI API** (api.openai.com)
  - Privacy Policy: https://openai.com/privacy/
  - Terms of Service: https://openai.com/terms/
  - Used when OpenAI is selected as provider

* **Anthropic Claude API** (api.anthropic.com)
  - Privacy Policy: https://www.anthropic.com/privacy
  - Terms of Service: https://www.anthropic.com/terms
  - Used when Claude is selected as provider

**Data Transmission:**
- Only user-selected text is sent to the chosen AI provider
- No personal information, user data, or site content is transmitted
- API keys are encrypted and never exposed to frontend
- No permanent storage of user selections or explanations

**GDPR Compliance:**
- Users control when explanations are requested
- No tracking cookies or personal data collection
- Clear indication when external services are used
- Option to disable the service entirely
- Complete data removal on plugin uninstall

**Legal Compliance:**
By using this plugin, site administrators agree to comply with the terms of service of their chosen AI provider. Users should be informed that their text selections may be processed by external AI services.

== Support ==

For support, please report issues or request features via GitHub Issues at https://github.com/billymedia/wp-explainer/issues. For custom modifications or professional services, contact Billy directly at billy@billymedia.co.uk. Include your WordPress version, PHP version, and detailed description when reporting issues.

== Contributing ==

Contributions are welcome! Please visit the GitHub repository at https://github.com/billymedia/wp-explainer for development guidelines and to submit pull requests.

== Technical Requirements ==

* WordPress 5.0 or higher
* PHP 7.4 or higher
* API key from OpenAI or Claude (Anthropic)
* HTTPS recommended for security
* Modern browser with JavaScript enabled
* SSL certificate recommended for API communication

== Credits ==

* OpenAI for the GPT API and models
* Anthropic for the Claude API and models
* WordPress community for development standards and best practices
* Contributors, beta testers, and the open source community
* Security researchers for responsible disclosure and feedback