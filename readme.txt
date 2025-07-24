=== AI Explainer ===
Contributors: billypatel
Tags: ai, explanation, tooltip, openai, claude
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple plugin that lets visitors get AI explanations for any text on your site.

== Description ==

WP AI Explainer makes your WordPress site more helpful by letting visitors highlight any text and get instant AI explanations. It's pretty straightforward - they select text, they get explanations in nice tooltips.

**Key Features:**

* **Choose Your AI**: Works with both OpenAI (GPT-3.5, GPT-4) and Claude (Sonnet, Haiku)
* **Smart Tooltips**: Explanations appear in tooltips that position themselves sensibly
* **Make It Yours**: Change colours, positioning, and add disclaimers to match your site
* **Multiple Languages**: Interface works in 7 languages including English, Spanish, German, French, Hindi, and Chinese
* **Secure Setup**: Your API keys are encrypted and never exposed to visitors
* **Easy Settings**: Clean admin interface with basic and advanced options
* **Custom Prompts**: Write your own AI prompts using simple placeholders
* **Control Content**: Choose which parts of your site should have explanations
* **Performance Minded**: Built-in caching and rate limiting to keep things fast and affordable
* **Works Everywhere**: Designed to work with any WordPress theme
* **Accessible**: Built with screen readers and keyboard navigation in mind
* **Mobile Friendly**: Works great on phones and tablets
* **Privacy Conscious**: GDPR compliant with minimal data collection

**How It Works:**

1. Install and activate the plugin
2. Pick your AI provider (OpenAI or Claude) and add your API key
3. Choose your language and customise how things look
4. Visitors highlight text to get instant explanations
5. Explanations show up in neat tooltips they can close when done

**Great For:**

* Educational sites and online courses
* Technical docs and knowledge bases
* News and magazine sites
* Any site with jargon or complex terms
* E-commerce sites with detailed products
* Basically anywhere people might need extra context

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
2. Search for "WP AI Explainer"
3. Click "Install Now" and then "Activate"
4. Go to Settings → Explainer Settings
5. Pick your AI provider (OpenAI or Claude)
6. Enter your API key and pick a model
7. Test your API key to make sure it works
8. Customise how things look if you want
9. Save your settings and try it out

**First Time Setup:**

After installation, you'll need to:
- Get an API account with OpenAI or Claude
- Grab an API key from whichever one you chose
- Set up the plugin in your WordPress admin
- Test it works on your site
- Tweak the appearance if you want

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

Yes, you need an API key from either OpenAI or Claude to make this work. You can pick whichever one you prefer in the settings.

= How much does it cost to use? =

The plugin is free, but you pay for API usage from OpenAI or Claude based on how much it gets used. The plugin has caching and rate limiting built in to keep costs reasonable.

= Will this slow down my website? =

No, it's built to be lightweight. Scripts only load when they're needed and the goal is to add less than 100ms to your page load time.

= Does it work with my theme? =

Yes, it should work with any WordPress theme. The CSS is designed to not interfere with your theme's styling.

= Is it accessible? =

Yes, it's built with accessibility in mind - keyboard navigation and screen readers work properly.

= Can I customise the appearance? =

Absolutely. You can change colours, positioning, prompts, disclaimers - pretty much everything to match your site.

= What about privacy and GDPR? =

It's built with privacy in mind and follows GDPR requirements. The selected text gets sent to your AI provider but isn't stored anywhere permanently.

= Can I limit usage? =

Yes, there's built-in rate limiting to prevent people from going crazy with requests. You can set different limits for logged-in users versus visitors.

= Does it work on mobile? =

Yes, it works great on phones and tablets with touch-friendly controls.

= What if the API is down? =

If the API goes down, it'll show friendly error messages instead of breaking. Since it supports multiple providers, you can switch if one's having issues.

= Can I use this plugin on a multisite installation? =

Yes, it works fine on multisite. Each site gets its own API keys and settings.

= Does this plugin work with caching plugins? =

Yes, it plays nicely with caching plugins. It also has its own caching system for API responses to make things faster.

= Will this plugin slow down my website? =

No, it's built to be lightweight. Scripts only load when needed and it aims to add less than 100ms to page load time.

= Can I disable the plugin for certain user roles? =

Yes, you can set different permissions and rate limits in the Advanced settings. You can even turn it off completely for visitors if you want.

= Is this plugin compatible with page builders? =

Yes, it works with page builders like Elementor, Gutenberg, and others. It uses CSS selectors to find the right content areas.

= Can I translate this plugin? =

Yes, it's ready for translation and the interface already works in 7 languages (English US/UK, Spanish, German, French, Hindi, Chinese). You can add more languages using WordPress translation tools.

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
Initial release of WP AI Explainer. Install to start providing AI-powered explanations on your WordPress site.

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
By using this plugin, you agree to follow the terms of service of whichever AI provider you choose. Let your users know that their selected text gets processed by external AI services.

== Support ==

For support, report issues or suggest features on GitHub at https://github.com/billymedia/WP-AI-Explainer/issues. For custom work, email Billy directly at billy@billymedia.co.uk. When reporting issues, include your WordPress version, PHP version, and what's going wrong.


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
* Beta testers and the community
* Security researchers for responsible disclosure and feedback