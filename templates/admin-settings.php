<?php
/**
 * Admin settings template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap explainer-admin-wrap">
    <h1><?php echo esc_html__('WP AI Explainer Settings', 'ai-explainer'); ?></h1>
    
    <div class="explainer-admin-header">
        <p><?php echo esc_html__('Configure your WP AI Explainer plugin settings below. This plugin helps users understand complex text by providing AI-generated explanations via tooltips.', 'ai-explainer'); ?></p>
    </div>
    
    <div class="explainer-admin-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#basic" class="nav-tab nav-tab-active"><?php echo esc_html__('Basic Settings', 'ai-explainer'); ?></a>
            <a href="#content" class="nav-tab"><?php echo esc_html__('Content Rules', 'ai-explainer'); ?></a>
            <a href="#performance" class="nav-tab"><?php echo esc_html__('Performance', 'ai-explainer'); ?></a>
            <a href="#appearance" class="nav-tab"><?php echo esc_html__('Appearance', 'ai-explainer'); ?></a>
            <a href="#advanced" class="nav-tab"><?php echo esc_html__('Advanced', 'ai-explainer'); ?></a>
            <a href="#help" class="nav-tab"><?php echo esc_html__('Help', 'ai-explainer'); ?></a>
            <a href="#support" class="nav-tab"><?php echo esc_html__('Support', 'ai-explainer'); ?></a>
        </nav>
    </div>
    
    <form method="post" action="options.php" id="explainer-settings-form">
        <?php settings_fields('explainer_settings'); ?>
        
        <!-- Basic Settings Tab -->
        <div class="tab-content" id="basic-tab">
            <h2><?php echo esc_html__('Basic Configuration', 'ai-explainer'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Plugin Status', 'ai-explainer'); ?></th>
                    <td>
                        <?php 
                        $is_auto_disabled = explainer_is_auto_disabled();
                        $is_enabled = get_option('explainer_enabled', true);
                        ?>
                        
                        <?php if ($is_auto_disabled): ?>
                            <!-- Auto-disabled state -->
                            <div class="explainer-status-disabled">
                                <p><span class="dashicons dashicons-warning" style="color: #dc3232;"></span> 
                                <strong style="color: #dc3232;"><?php echo esc_html__('Plugin Automatically Disabled', 'ai-explainer'); ?></strong></p>
                                
                                <?php 
                                $stats = explainer_get_usage_exceeded_stats();
                                if (!empty($stats['reason'])): ?>
                                    <p><strong><?php echo esc_html__('Reason:', 'ai-explainer'); ?></strong> <?php echo esc_html($stats['reason']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($stats['provider'])): ?>
                                    <p><strong><?php echo esc_html__('Provider:', 'ai-explainer'); ?></strong> <?php echo esc_html($stats['provider']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($stats['time_since'])): ?>
                                    <p><strong><?php echo esc_html__('Disabled:', 'ai-explainer'); ?></strong> <?php echo esc_html($stats['time_since']); ?></p>
                                <?php endif; ?>
                                
                                <div class="explainer-reenable-section" style="margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
                                    <h4><?php echo esc_html__('Re-enable Plugin', 'ai-explainer'); ?></h4>
                                    <p><?php echo esc_html__('Before re-enabling, please ensure you have resolved the API usage limit issues with your provider.', 'ai-explainer'); ?></p>
                                    <button type="button" class="button button-primary explainer-reenable-btn-settings" 
                                            data-nonce="<?php echo esc_attr(wp_create_nonce('explainer_reenable_plugin')); ?>">
                                        <?php echo esc_html__('Re-enable Plugin Now', 'ai-explainer'); ?>
                                    </button>
                                    <p class="description" style="margin-top: 10px;">
                                        <?php echo esc_html__('This will clear the auto-disable flag and restore normal plugin functionality.', 'ai-explainer'); ?>
                                    </p>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Normal enabled/disabled state -->
                            <fieldset>
                                <label for="explainer_enabled">
                                    <input type="checkbox" name="explainer_enabled" id="explainer_enabled" value="1" <?php checked($is_enabled, true); ?> />
                                    <?php echo esc_html__('Enable AI Explainer plugin', 'ai-explainer'); ?>
                                </label>
                                <p class="description"><?php echo esc_html__('Enable or disable the plugin functionality site-wide.', 'ai-explainer'); ?></p>
                                
                                <?php if (!$is_enabled): ?>
                                    <p style="color: #d63638; margin-top: 8px;">
                                        <span class="dashicons dashicons-info" style="color: #d63638;"></span>
                                        <?php echo esc_html__('Plugin is currently disabled manually.', 'ai-explainer'); ?>
                                    </p>
                                <?php endif; ?>
                            </fieldset>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_language"><?php echo esc_html__('Language', 'ai-explainer'); ?></label>
                    </th>
                    <td>
                        <select name="explainer_language" id="explainer_language">
                            <option value="en_US" <?php selected(get_option('explainer_language', 'en_GB'), 'en_US'); ?>>
                                <?php echo esc_html__('English (United States)', 'ai-explainer'); ?>
                            </option>
                            <option value="en_GB" <?php selected(get_option('explainer_language', 'en_GB'), 'en_GB'); ?>>
                                <?php echo esc_html__('English (United Kingdom)', 'ai-explainer'); ?>
                            </option>
                            <option value="es_ES" <?php selected(get_option('explainer_language', 'en_GB'), 'es_ES'); ?>>
                                <?php echo esc_html__('Spanish (Spain)', 'ai-explainer'); ?>
                            </option>
                            <option value="de_DE" <?php selected(get_option('explainer_language', 'en_GB'), 'de_DE'); ?>>
                                <?php echo esc_html__('German (Germany)', 'ai-explainer'); ?>
                            </option>
                            <option value="fr_FR" <?php selected(get_option('explainer_language', 'en_GB'), 'fr_FR'); ?>>
                                <?php echo esc_html__('French (France)', 'ai-explainer'); ?>
                            </option>
                            <option value="hi_IN" <?php selected(get_option('explainer_language', 'en_GB'), 'hi_IN'); ?>>
                                <?php echo esc_html__('Hindi (India)', 'ai-explainer'); ?>
                            </option>
                            <option value="zh_CN" <?php selected(get_option('explainer_language', 'en_GB'), 'zh_CN'); ?>>
                                <?php echo esc_html__('Chinese (Simplified)', 'ai-explainer'); ?>
                            </option>
                        </select>
                        <p class="description"><?php echo esc_html__('Select the language for the AI explanations.', 'ai-explainer'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_api_provider"><?php echo esc_html__('AI Provider', 'ai-explainer'); ?></label>
                    </th>
                    <td>
                        <select name="explainer_api_provider" id="explainer_api_provider">
                            <option value="openai" <?php selected(get_option('explainer_api_provider', 'openai'), 'openai'); ?>>
                                <?php echo esc_html__('OpenAI (GPT Models)', 'ai-explainer'); ?>
                            </option>
                            <option value="claude" <?php selected(get_option('explainer_api_provider', 'openai'), 'claude'); ?>>
                                <?php echo esc_html__('Claude (Anthropic)', 'ai-explainer'); ?>
                            </option>
                        </select>
                        <p class="description"><?php echo esc_html__('Choose your AI provider. Each provider has different models and pricing.', 'ai-explainer'); ?></p>
                    </td>
                </tr>
                
                <tr class="api-key-row openai-fields">
                    <th scope="row">
                        <label for="explainer_api_key"><?php echo esc_html__('OpenAI API Key', 'ai-explainer'); ?></label>
                    </th>
                    <td>
<?php
                        // Check if API key is configured (without exposing the key)
                        $openai_key_configured = !empty(get_option('explainer_api_key', ''));
                        if ($openai_key_configured) {
                            // Only decrypt for masking, never expose full key
                            $api_proxy = new ExplainerPlugin_API_Proxy();
                            $decrypted_api_key = $api_proxy->get_decrypted_api_key_for_provider('openai');
                            $masked_key = '';
                            if (!empty($decrypted_api_key)) {
                                // Show only first 3 and last 4 characters with ellipsis
                                $masked_key = substr($decrypted_api_key, 0, 3) . '...' . substr($decrypted_api_key, -4);
                            }
                        }
                        ?>
                        
                        <?php if ($openai_key_configured): ?>
                            <div class="api-key-status configured" style="margin-bottom: 10px;">
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                <strong><?php echo esc_html__('API Key Configured:', 'ai-explainer'); ?></strong> 
                                <code><?php echo esc_html($masked_key); ?></code>
                            </div>
                        <?php else: ?>
                            <div class="api-key-status not-configured" style="margin-bottom: 10px;">
                                <span class="dashicons dashicons-warning" style="color: #dba617;"></span>
                                <strong><?php echo esc_html__('No API Key Configured', 'ai-explainer'); ?></strong>
                            </div>
                        <?php endif; ?>
                        
                        <input type="password" name="explainer_api_key" id="explainer_api_key" value="" class="regular-text" placeholder="<?php echo esc_attr__('Enter new API key (leave empty to keep current)', 'ai-explainer'); ?>" />
                        <button type="button" class="button button-secondary" id="toggle-api-key-visibility">
                            <?php echo esc_html__('Show', 'ai-explainer'); ?>
                        </button>
                        <button type="button" class="button button-primary" id="test-api-key" style="margin-left: 10px;">
                            <?php echo esc_html__('Test API Key', 'ai-explainer'); ?>
                        </button>
                        <p class="description">
                            <?php echo esc_html__('Enter your OpenAI API key. Get one from', 'ai-explainer'); ?> 
                            <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">platform.openai.com</a>
                            <?php if ($openai_key_configured): ?>
                                <br><em><?php echo esc_html__('Leave empty to keep the current key, or enter a new key to replace it.', 'ai-explainer'); ?></em>
                            <?php endif; ?>
                        </p>
                        <div id="api-key-status"></div>
                    </td>
                </tr>
                
                <tr class="api-key-row claude-fields" style="display: none;">
                    <th scope="row">
                        <label for="explainer_claude_api_key"><?php echo esc_html__('Claude API Key', 'ai-explainer'); ?></label>
                    </th>
                    <td>
<?php
                        // Check if Claude API key is configured (without exposing the key)
                        $claude_key_configured = !empty(get_option('explainer_claude_api_key', ''));
                        if ($claude_key_configured) {
                            // Only decrypt for masking, never expose full key
                            $api_proxy = new ExplainerPlugin_API_Proxy();
                            $decrypted_claude_key = $api_proxy->get_decrypted_api_key_for_provider('claude');
                            $masked_claude_key = '';
                            if (!empty($decrypted_claude_key)) {
                                // Show only first 3 and last 4 characters with ellipsis
                                $masked_claude_key = substr($decrypted_claude_key, 0, 3) . '...' . substr($decrypted_claude_key, -4);
                            }
                        }
                        ?>
                        
                        <?php if ($claude_key_configured): ?>
                            <div class="api-key-status configured" style="margin-bottom: 10px;">
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                <strong><?php echo esc_html__('API Key Configured:', 'ai-explainer'); ?></strong> 
                                <code><?php echo esc_html($masked_claude_key); ?></code>
                            </div>
                        <?php else: ?>
                            <div class="api-key-status not-configured" style="margin-bottom: 10px;">
                                <span class="dashicons dashicons-warning" style="color: #dba617;"></span>
                                <strong><?php echo esc_html__('No API Key Configured', 'ai-explainer'); ?></strong>
                            </div>
                        <?php endif; ?>
                        
                        <input type="password" name="explainer_claude_api_key" id="explainer_claude_api_key" value="" class="regular-text" placeholder="<?php echo esc_attr__('Enter new API key (leave empty to keep current)', 'ai-explainer'); ?>" />
                        <button type="button" class="button button-secondary" id="toggle-claude-key-visibility">
                            <?php echo esc_html__('Show', 'ai-explainer'); ?>
                        </button>
                        <button type="button" class="button button-primary" id="test-claude-api-key" style="margin-left: 10px;">
                            <?php echo esc_html__('Test API Key', 'ai-explainer'); ?>
                        </button>
                        <p class="description">
                            <?php echo esc_html__('Enter your Claude API key. Get one from', 'ai-explainer'); ?> 
                            <a href="https://console.anthropic.com/account/keys" target="_blank" rel="noopener noreferrer">console.anthropic.com</a>
                            <?php if ($claude_key_configured): ?>
                                <br><em><?php echo esc_html__('Leave empty to keep the current key, or enter a new key to replace it.', 'ai-explainer'); ?></em>
                            <?php endif; ?>
                        </p>
                        <div id="claude-api-key-status"></div>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_api_model"><?php echo esc_html__('AI Model', 'ai-explainer'); ?></label>
                    </th>
                    <td>
                        <select name="explainer_api_model" id="explainer_api_model">
                            <!-- OpenAI Models -->
                            <optgroup label="<?php echo esc_attr__('OpenAI Models', 'ai-explainer'); ?>" class="openai-models">
                                <option value="gpt-3.5-turbo" <?php selected(get_option('explainer_api_model', 'gpt-3.5-turbo'), 'gpt-3.5-turbo'); ?>>
                                    <?php echo esc_html__('GPT-3.5 Turbo (Recommended)', 'ai-explainer'); ?>
                                </option>
                                <option value="gpt-4" <?php selected(get_option('explainer_api_model', 'gpt-3.5-turbo'), 'gpt-4'); ?>>
                                    <?php echo esc_html__('GPT-4 (Higher quality, more expensive)', 'ai-explainer'); ?>
                                </option>
                                <option value="gpt-4-turbo" <?php selected(get_option('explainer_api_model', 'gpt-3.5-turbo'), 'gpt-4-turbo'); ?>>
                                    <?php echo esc_html__('GPT-4 Turbo (Fast and efficient)', 'ai-explainer'); ?>
                                </option>
                            </optgroup>
                            <!-- Claude Models -->
                            <optgroup label="<?php echo esc_attr__('Claude Models', 'ai-explainer'); ?>" class="claude-models" style="display: none;">
                                <option value="claude-3-haiku-20240307" <?php selected(get_option('explainer_api_model', 'gpt-3.5-turbo'), 'claude-3-haiku-20240307'); ?>>
                                    <?php echo esc_html__('Claude 3 Haiku (Fast and efficient)', 'ai-explainer'); ?>
                                </option>
                                <option value="claude-3-sonnet-20240229" <?php selected(get_option('explainer_api_model', 'gpt-3.5-turbo'), 'claude-3-sonnet-20240229'); ?>>
                                    <?php echo esc_html__('Claude 3 Sonnet (Balanced)', 'ai-explainer'); ?>
                                </option>
                                <option value="claude-3-opus-20240229" <?php selected(get_option('explainer_api_model', 'gpt-3.5-turbo'), 'claude-3-opus-20240229'); ?>>
                                    <?php echo esc_html__('Claude 3 Opus (Highest quality)', 'ai-explainer'); ?>
                                </option>
                            </optgroup>
                        </select>
                        <p class="description"><?php echo esc_html__('Select the AI model to use for generating explanations. Models vary by provider.', 'ai-explainer'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_custom_prompt"><?php echo esc_html__('Custom Prompt Template', 'ai-explainer'); ?></label>
                    </th>
                    <td>
                        <textarea name="explainer_custom_prompt" id="explainer_custom_prompt" rows="4" cols="60" class="large-text code"><?php echo esc_textarea(get_option('explainer_custom_prompt', 'Please provide a clear, concise explanation of the following text in 1-2 sentences: {{snippet}}')); ?></textarea>
                        <p class="description">
                            <?php 
                            // translators: {{snippet}} is a placeholder that will be replaced with the user's selected text
                            echo esc_html__('Customize the prompt sent to the AI. Use {{snippet}} where you want the selected text to appear. Maximum 500 characters.', 'ai-explainer'); ?>
                        </p>
                        <p class="description">
                            <strong><?php echo esc_html__('Example:', 'ai-explainer'); ?></strong> <?php 
                            // translators: {{snippet}} is a placeholder that will be replaced with the user's selected text
                            echo esc_html__('"Explain this text in simple terms for a beginner: {{snippet}}"', 'ai-explainer'); ?>
                        </p>
                        <div class="prompt-actions">
                            <button type="button" class="button button-secondary" id="reset-prompt-default"><?php echo esc_html__('Reset to Default', 'ai-explainer'); ?></button>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Content Rules Tab -->
        <div class="tab-content" id="content-tab" style="display: none;">
            <h2><?php echo esc_html__('Content Selection Rules', 'ai-explainer'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Selection Length', 'ai-explainer'); ?></th>
                    <td>
                        <fieldset>
                            <label for="explainer_min_selection_length">
                                <?php echo esc_html__('Minimum characters:', 'ai-explainer'); ?>
                                <input type="number" name="explainer_min_selection_length" id="explainer_min_selection_length" value="<?php echo esc_attr(get_option('explainer_min_selection_length', 3)); ?>" min="1" max="50" class="small-text" />
                            </label>
                            <br><br>
                            <label for="explainer_max_selection_length">
                                <?php echo esc_html__('Maximum characters:', 'ai-explainer'); ?>
                                <input type="number" name="explainer_max_selection_length" id="explainer_max_selection_length" value="<?php echo esc_attr(get_option('explainer_max_selection_length', 200)); ?>" min="50" max="1000" class="small-text" />
                            </label>
                            <p class="description"><?php echo esc_html__('Set the minimum and maximum character limits for text selection.', 'ai-explainer'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Word Count', 'ai-explainer'); ?></th>
                    <td>
                        <fieldset>
                            <label for="explainer_min_words">
                                <?php echo esc_html__('Minimum words:', 'ai-explainer'); ?>
                                <input type="number" name="explainer_min_words" id="explainer_min_words" value="<?php echo esc_attr(get_option('explainer_min_words', 1)); ?>" min="1" max="10" class="small-text" />
                            </label>
                            <br><br>
                            <label for="explainer_max_words">
                                <?php echo esc_html__('Maximum words:', 'ai-explainer'); ?>
                                <input type="number" name="explainer_max_words" id="explainer_max_words" value="<?php echo esc_attr(get_option('explainer_max_words', 30)); ?>" min="5" max="100" class="small-text" />
                            </label>
                            <p class="description"><?php echo esc_html__('Set the minimum and maximum word count limits for text selection.', 'ai-explainer'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_included_selectors"><?php echo esc_html__('Included Areas', 'ai-explainer'); ?></label>
                    </th>
                    <td>
                        <textarea name="explainer_included_selectors" id="explainer_included_selectors" rows="4" cols="50" class="large-text"><?php echo esc_textarea(get_option('explainer_included_selectors', 'article, main, .content, .entry-content, .post-content')); ?></textarea>
                        <p class="description"><?php echo esc_html__('CSS selectors for areas where text selection is allowed (comma-separated).', 'ai-explainer'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_excluded_selectors"><?php echo esc_html__('Excluded Areas', 'ai-explainer'); ?></label>
                    </th>
                    <td>
                        <textarea name="explainer_excluded_selectors" id="explainer_excluded_selectors" rows="4" cols="50" class="large-text"><?php echo esc_textarea(get_option('explainer_excluded_selectors', 'nav, header, footer, aside, .widget, #wpadminbar, .admin-bar')); ?></textarea>
                        <p class="description"><?php echo esc_html__('CSS selectors for areas where text selection is blocked (comma-separated).', 'ai-explainer'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_blocked_words"><?php echo esc_html__('Blocked Words', 'ai-explainer'); ?></label>
                    </th>
                    <td>
                        <textarea name="explainer_blocked_words" id="explainer_blocked_words" rows="8" cols="50" class="large-text" placeholder="<?php echo esc_attr__('Enter one word or phrase per line', 'ai-explainer'); ?>"><?php echo esc_textarea(get_option('explainer_blocked_words', '')); ?></textarea>
                        <p class="description">
                            <?php echo esc_html__('Enter words or phrases that should be blocked from getting AI explanations (one per line).', 'ai-explainer'); ?>
                            <br>
                            <span id="blocked-words-count">0</span> <?php echo esc_html__('words blocked', 'ai-explainer'); ?>
                        </p>
                        
                        <div class="blocked-words-options" style="margin-top: 10px;">
                            <label>
                                <input type="checkbox" name="explainer_blocked_words_case_sensitive" id="explainer_blocked_words_case_sensitive" value="1" <?php checked(get_option('explainer_blocked_words_case_sensitive', false), true); ?> />
                                <?php echo esc_html__('Case sensitive matching', 'ai-explainer'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="explainer_blocked_words_whole_word" id="explainer_blocked_words_whole_word" value="1" <?php checked(get_option('explainer_blocked_words_whole_word', false), true); ?> />
                                <?php echo esc_html__('Match whole words only', 'ai-explainer'); ?>
                            </label>
                        </div>
                        
                        <div class="blocked-words-actions" style="margin-top: 10px;">
                            <button type="button" class="button" id="clear-blocked-words"><?php echo esc_html__('Clear All', 'ai-explainer'); ?></button>
                            <button type="button" class="button" id="load-default-blocked-words"><?php echo esc_html__('Load Common Inappropriate Words', 'ai-explainer'); ?></button>
                        </div>
                        
                        <p class="description" style="margin-top: 10px;">
                            <strong><?php echo esc_html__('Note:', 'ai-explainer'); ?></strong> 
                            <?php echo esc_html__('Maximum 500 words, 100 characters per word. Special characters will be removed.', 'ai-explainer'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Performance Tab -->
        <div class="tab-content" id="performance-tab" style="display: none;">
            <h2><?php echo esc_html__('Performance & Caching', 'ai-explainer'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Caching', 'ai-explainer'); ?></th>
                    <td>
                        <fieldset>
                            <label for="explainer_cache_enabled">
                                <input type="checkbox" name="explainer_cache_enabled" id="explainer_cache_enabled" value="1" <?php checked(get_option('explainer_cache_enabled', true), true); ?> />
                                <?php echo esc_html__('Enable caching to reduce API calls and costs', 'ai-explainer'); ?>
                            </label>
                            <br><br>
                            <label for="explainer_cache_duration">
                                <?php echo esc_html__('Cache Duration:', 'ai-explainer'); ?>
                                <input type="number" name="explainer_cache_duration" id="explainer_cache_duration" value="<?php echo esc_attr(get_option('explainer_cache_duration', 24)); ?>" min="1" max="168" class="small-text" />
                                <?php echo esc_html__('hours', 'ai-explainer'); ?>
                            </label>
                            <p class="description"><?php echo esc_html__('How long to cache explanations (1-168 hours).', 'ai-explainer'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Rate Limiting', 'ai-explainer'); ?></th>
                    <td>
                        <fieldset>
                            <label for="explainer_rate_limit_enabled">
                                <input type="checkbox" name="explainer_rate_limit_enabled" id="explainer_rate_limit_enabled" value="1" <?php checked(get_option('explainer_rate_limit_enabled', true), true); ?> />
                                <?php echo esc_html__('Enable rate limiting to prevent abuse', 'ai-explainer'); ?>
                            </label>
                            <br><br>
                            <label for="explainer_rate_limit_logged">
                                <?php echo esc_html__('Logged-in users:', 'ai-explainer'); ?>
                                <input type="number" name="explainer_rate_limit_logged" id="explainer_rate_limit_logged" value="<?php echo esc_attr(get_option('explainer_rate_limit_logged', 20)); ?>" min="1" max="100" class="small-text" />
                                <?php echo esc_html__('requests per minute', 'ai-explainer'); ?>
                            </label>
                            <br><br>
                            <label for="explainer_rate_limit_anonymous">
                                <?php echo esc_html__('Anonymous users:', 'ai-explainer'); ?>
                                <input type="number" name="explainer_rate_limit_anonymous" id="explainer_rate_limit_anonymous" value="<?php echo esc_attr(get_option('explainer_rate_limit_anonymous', 10)); ?>" min="1" max="50" class="small-text" />
                                <?php echo esc_html__('requests per minute', 'ai-explainer'); ?>
                            </label>
                            <p class="description"><?php echo esc_html__('Set different rate limits for logged-in and anonymous users.', 'ai-explainer'); ?></p>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Appearance Tab -->
        <div class="tab-content" id="appearance-tab" style="display: none;">
            <h2><?php echo esc_html__('Appearance Customization', 'ai-explainer'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Toggle Button Position', 'ai-explainer'); ?></th>
                    <td>
                        <select name="explainer_toggle_position" id="explainer_toggle_position">
                            <option value="bottom-right" <?php selected(get_option('explainer_toggle_position', 'bottom-right'), 'bottom-right'); ?>>
                                <?php echo esc_html__('Bottom Right', 'ai-explainer'); ?>
                            </option>
                            <option value="bottom-left" <?php selected(get_option('explainer_toggle_position', 'bottom-right'), 'bottom-left'); ?>>
                                <?php echo esc_html__('Bottom Left', 'ai-explainer'); ?>
                            </option>
                            <option value="top-right" <?php selected(get_option('explainer_toggle_position', 'bottom-right'), 'top-right'); ?>>
                                <?php echo esc_html__('Top Right', 'ai-explainer'); ?>
                            </option>
                            <option value="top-left" <?php selected(get_option('explainer_toggle_position', 'bottom-right'), 'top-left'); ?>>
                                <?php echo esc_html__('Top Left', 'ai-explainer'); ?>
                            </option>
                        </select>
                        <p class="description"><?php echo esc_html__('Choose where to position the toggle button on the page.', 'ai-explainer'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Tooltip Colors', 'ai-explainer'); ?></th>
                    <td>
                        <fieldset>
                            <label for="explainer_tooltip_bg_color">
                                <?php echo esc_html__('Background Color:', 'ai-explainer'); ?>
                                <input type="color" name="explainer_tooltip_bg_color" id="explainer_tooltip_bg_color" value="<?php echo esc_attr(get_option('explainer_tooltip_bg_color', '#333333')); ?>" />
                            </label>
                            <br><br>
                            <label for="explainer_tooltip_text_color">
                                <?php echo esc_html__('Text Color:', 'ai-explainer'); ?>
                                <input type="color" name="explainer_tooltip_text_color" id="explainer_tooltip_text_color" value="<?php echo esc_attr(get_option('explainer_tooltip_text_color', '#ffffff')); ?>" />
                            </label>
                            <p class="description"><?php echo esc_html__('Customize the tooltip background and text colors.', 'ai-explainer'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Toggle Button Colors', 'ai-explainer'); ?></th>
                    <td>
                        <fieldset>
                            <label for="explainer_button_enabled_color">
                                <?php echo esc_html__('Enabled Color:', 'ai-explainer'); ?>
                                <input type="color" name="explainer_button_enabled_color" id="explainer_button_enabled_color" value="<?php echo esc_attr(get_option('explainer_button_enabled_color', '#46b450')); ?>" />
                            </label>
                            <br><br>
                            <label for="explainer_button_disabled_color">
                                <?php echo esc_html__('Disabled Color:', 'ai-explainer'); ?>
                                <input type="color" name="explainer_button_disabled_color" id="explainer_button_disabled_color" value="<?php echo esc_attr(get_option('explainer_button_disabled_color', '#666666')); ?>" />
                            </label>
                            <br><br>
                            <label for="explainer_button_text_color">
                                <?php echo esc_html__('Button Text Color:', 'ai-explainer'); ?>
                                <input type="color" name="explainer_button_text_color" id="explainer_button_text_color" value="<?php echo esc_attr(get_option('explainer_button_text_color', '#ffffff')); ?>" />
                            </label>
                            <p class="description"><?php echo esc_html__('Customize the toggle button colors for enabled, disabled, and text.', 'ai-explainer'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Tooltip Footer', 'ai-explainer'); ?></th>
                    <td>
                        <fieldset>
                            <label for="explainer_show_disclaimer">
                                <input type="checkbox" name="explainer_show_disclaimer" id="explainer_show_disclaimer" value="1" <?php checked(get_option('explainer_show_disclaimer', true), true); ?> />
                                <?php echo esc_html__('Show accuracy disclaimer', 'ai-explainer'); ?>
                            </label>
                            <p class="description"><?php echo esc_html__('Displays "AI-generated content may not always be accurate" at the bottom of explanations.', 'ai-explainer'); ?></p>
                            <br>
                            <label for="explainer_show_provider">
                                <input type="checkbox" name="explainer_show_provider" id="explainer_show_provider" value="1" <?php checked(get_option('explainer_show_provider', true), true); ?> />
                                <?php echo esc_html__('Show AI provider attribution', 'ai-explainer'); ?>
                            </label>
                            <p class="description"><?php echo esc_html__('Displays "Powered by OpenAI" or "Powered by Claude" to credit the AI provider.', 'ai-explainer'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_tooltip_footer_color"><?php echo esc_html__('Footer Text Color', 'ai-explainer'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="explainer_tooltip_footer_color" id="explainer_tooltip_footer_color" value="<?php echo esc_attr(get_option('explainer_tooltip_footer_color', '#ffffff')); ?>" class="color-field" data-default-color="#ffffff" />
                        <p class="description"><?php echo esc_html__('Choose the color for footer text in tooltips (disclaimer and provider attribution).', 'ai-explainer'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Preview', 'ai-explainer'); ?></th>
                    <td>
                        <div id="tooltip-preview" class="tooltip-preview">
                            <div class="explainer-tooltip explainer-tooltip-preview">
                                <div class="explainer-tooltip-header">
                                    <span class="explainer-tooltip-title" id="preview-tooltip-title"><?php echo esc_html__('Explanation', 'ai-explainer'); ?></span>
                                    <button class="explainer-tooltip-close" type="button" id="preview-close-button">×</button>
                                </div>
                                <div class="explainer-tooltip-content">
                                    <span id="preview-tooltip-content"><?php echo esc_html__('This is how your tooltip will look with the selected colors. It matches the actual frontend design with proper spacing and typography.', 'ai-explainer'); ?></span>
                                </div>
                                <div class="explainer-tooltip-footer">
                                    <div class="explainer-disclaimer" id="preview-disclaimer"><?php echo esc_html__('AI-generated content may not always be accurate', 'ai-explainer'); ?></div>
                                    <div class="explainer-provider" id="preview-provider"><?php echo esc_html__('Powered by OpenAI', 'ai-explainer'); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="button-preview" class="button-preview" style="margin-top: 20px;">
                            <h4><?php echo esc_html__('Toggle Button Preview:', 'ai-explainer'); ?></h4>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <button type="button" class="preview-explainer-button enabled" id="preview-button-enabled">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                    <?php echo esc_html__('Explainer', 'ai-explainer'); ?>
                                </button>
                                <span><?php echo esc_html__('(Enabled)', 'ai-explainer'); ?></span>
                                
                                <button type="button" class="preview-explainer-button disabled" id="preview-button-disabled">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                    <?php echo esc_html__('Explainer', 'ai-explainer'); ?>
                                </button>
                                <span><?php echo esc_html__('(Disabled)', 'ai-explainer'); ?></span>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Advanced Tab -->
        <div class="tab-content" id="advanced-tab" style="display: none;">
            <h2><?php echo esc_html__('Advanced Configuration', 'ai-explainer'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Debug Mode', 'ai-explainer'); ?></th>
                    <td>
                        <label for="explainer_debug_mode">
                            <input type="checkbox" name="explainer_debug_mode" id="explainer_debug_mode" value="1" <?php checked(get_option('explainer_debug_mode', false), true); ?> />
                            <?php echo esc_html__('Enable debug mode for troubleshooting', 'ai-explainer'); ?>
                        </label>
                        <p class="description"><?php echo esc_html__('Enables detailed console logging and API prompt capture for debugging purposes. Only enable when troubleshooting issues.', 'ai-explainer'); ?></p>
                        
                        <?php if (get_option('explainer_debug_mode', false)): ?>
                        <div class="debug-actions" style="margin-top: 15px; padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;">
                            <h4><?php echo esc_html__('Debug Tools', 'ai-explainer'); ?></h4>
                            <p>
                                <button type="button" class="button button-secondary" id="view-debug-logs">
                                    <?php echo esc_html__('View Debug Logs', 'ai-explainer'); ?>
                                </button>
                                <button type="button" class="button button-secondary" id="delete-debug-logs">
                                    <?php echo esc_html__('Delete All Logs', 'ai-explainer'); ?>
                                </button>
                            </p>
                            <div id="debug-logs-viewer" style="display: none; margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 4px; max-height: 400px; overflow-y: auto;">
                                <p><?php echo esc_html__('No logs available.', 'ai-explainer'); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Cache Management', 'ai-explainer'); ?></th>
                    <td>
                        <div class="cache-management-section">
                            <div class="cache-info" style="margin-bottom: 15px;">
                                <p><strong><?php echo esc_html__('Current Cache Status:', 'ai-explainer'); ?></strong></p>
                                <p>
                                    <span id="cache-count-display"><?php echo esc_html__('Loading...', 'ai-explainer'); ?></span> 
                                    <?php echo esc_html__('cached explanations', 'ai-explainer'); ?>
                                </p>
                            </div>
                            <div class="cache-actions">
                                <button type="button" class="button button-secondary" id="clear-cache-advanced">
                                    <?php echo esc_html__('Clear Cache', 'ai-explainer'); ?>
                                </button>
                                <p class="description"><?php echo esc_html__('Clear all cached explanations. This will force new API requests for all future explanations.', 'ai-explainer'); ?></p>
                            </div>
                        </div>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Reset Settings', 'ai-explainer'); ?></th>
                    <td>
                        <div class="reset-settings-section">
                            <button type="button" class="button button-secondary" id="reset-settings">
                                <?php echo esc_html__('Reset to Defaults', 'ai-explainer'); ?>
                            </button>
                            <p class="description"><?php echo esc_html__('Reset all plugin settings to their default values. This action cannot be undone.', 'ai-explainer'); ?></p>
                        </div>
                    </td>
                </tr>
                
            </table>
        </div>
        
        <!-- Help Tab -->
        <div class="tab-content" id="help-tab" style="display: none;">
            <h2><?php echo esc_html__('How to Use WP AI Explainer', 'ai-explainer'); ?></h2>
            
            <div class="help-section">
                <h3><?php echo esc_html__('Quick Start Guide', 'ai-explainer'); ?></h3>
                <div class="help-steps">
                    <div class="help-step">
                        <h4><?php echo esc_html__('1. Choose Your AI Provider', 'ai-explainer'); ?></h4>
                        <p><?php echo esc_html__('Head to the Basic Settings tab and pick either OpenAI or Claude - both work great, it just depends on your preference.', 'ai-explainer'); ?></p>
                    </div>
                    
                    <div class="help-step">
                        <h4><?php echo esc_html__('2. Add Your API Key', 'ai-explainer'); ?></h4>
                        <p><?php echo esc_html__('Grab your API key from whichever provider you chose. Don\'t worry - we encrypt it properly so it stays safe.', 'ai-explainer'); ?></p>
                        <ul>
                            <li><strong>OpenAI:</strong> Get your key from <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a></li>
                            <li><strong>Claude:</strong> Get your key from <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a></li>
                        </ul>
                    </div>
                    
                    <div class="help-step">
                        <h4><?php echo esc_html__('3. Select AI Model', 'ai-explainer'); ?></h4>
                        <p><?php echo esc_html__('Pick the AI model that works for your situation and budget.', 'ai-explainer'); ?></p>
                        <ul>
                            <li><strong>OpenAI:</strong> GPT-3.5-turbo is quick and affordable, GPT-4 gives better quality</li>
                            <li><strong>Claude:</strong> Haiku is speedy, Sonnet gives more thoughtful responses</li>
                        </ul>
                    </div>
                    
                    <div class="help-step">
                        <h4><?php echo esc_html__('4. Test and Save', 'ai-explainer'); ?></h4>
                        <p><?php echo esc_html__('Hit the "Test API Key" button to make sure everything\'s working, then save your settings and you\'re good to go.', 'ai-explainer'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="help-section">
                <h3><?php echo esc_html__('How Users Get Explanations', 'ai-explainer'); ?></h3>
                <div class="help-usage">
                    <ol>
                        <li><?php echo esc_html__('Visitors highlight any text on your site', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('A small toggle button shows up so they can turn explanations on', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Once enabled, selecting text gives them instant AI explanations', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Explanations pop up in neat tooltips that they can close when done', 'ai-explainer'); ?></li>
                    </ol>
                </div>
            </div>
            
            <div class="help-section">
                <h3><?php echo esc_html__('Customisation Options', 'ai-explainer'); ?></h3>
                
                <div class="help-feature">
                    <h4><?php echo esc_html__('Appearance Tab', 'ai-explainer'); ?></h4>
                    <ul>
                        <li><?php echo esc_html__('Change tooltip colours to match your site\'s look', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Move the toggle button wherever it feels right', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Add disclaimers and show which AI provider you\'re using', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Tweak footer text colours so they\'re easy to read', 'ai-explainer'); ?></li>
                    </ul>
                </div>
                
                <div class="help-feature">
                    <h4><?php echo esc_html__('Content Rules Tab', 'ai-explainer'); ?></h4>
                    <ul>
                        <li><?php echo esc_html__('Set how much text people need to select before they get explanations', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Choose which parts of your site should have explanations and which shouldn\'t', 'ai-explainer'); ?></li>
                        <li><?php 
                        // translators: {{snippet}} is a placeholder that will be replaced with the user's selected text
                        echo esc_html__('Create custom AI prompts with {{snippet}} placeholders', 'ai-explainer'); ?></li>
                    </ul>
                </div>
                
                <div class="help-feature">
                    <h4><?php echo esc_html__('Performance Tab', 'ai-explainer'); ?></h4>
                    <ul>
                        <li><?php echo esc_html__('Turn on caching to save money on API calls and make things faster', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Set limits so people can\'t go crazy with requests', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Give different limits to logged-in users versus random visitors', 'ai-explainer'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="help-section">
                <h3><?php echo esc_html__('Troubleshooting', 'ai-explainer'); ?></h3>
                <div class="help-troubleshooting">
                    <div class="help-issue">
                        <h4><?php echo esc_html__('Explanations not working?', 'ai-explainer'); ?></h4>
                        <ul>
                            <li><?php echo esc_html__('Make sure the plugin is actually turned on in Basic Settings', 'ai-explainer'); ?></li>
                            <li><?php echo esc_html__('Double-check your API key - typos happen to everyone', 'ai-explainer'); ?></li>
                            <li><?php echo esc_html__('Use the "Test API Key" button to see if it\'s working', 'ai-explainer'); ?></li>
                            <li><?php echo esc_html__('Turn on Debug Mode in the Advanced tab if you need to see what\'s going wrong', 'ai-explainer'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="help-issue">
                        <h4><?php echo esc_html__('API costs getting a bit steep?', 'ai-explainer'); ?></h4>
                        <ul>
                            <li><?php echo esc_html__('Turn on caching in Performance settings - it really helps', 'ai-explainer'); ?></li>
                            <li><?php echo esc_html__('Set sensible rate limits so users don\'t go wild', 'ai-explainer'); ?></li>
                            <li><?php echo esc_html__('Try GPT-3.5-turbo or Claude Haiku - they\'re much cheaper', 'ai-explainer'); ?></li>
                            <li><?php echo esc_html__('Limit how much text people can select to keep prompts shorter', 'ai-explainer'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="help-issue">
                        <h4><?php echo esc_html__('Tooltips appearing in weird places?', 'ai-explainer'); ?></h4>
                        <ul>
                            <li><?php echo esc_html__('The plugin tries to position tooltips sensibly, but sometimes themes interfere', 'ai-explainer'); ?></li>
                            <li><?php echo esc_html__('Check your Content Rules - you might have conflicting CSS selectors', 'ai-explainer'); ?></li>
                            <li><?php echo esc_html__('Your theme might be overriding the plugin styles', 'ai-explainer'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="help-section">
                <h3><?php echo esc_html__('Cost Management Tips', 'ai-explainer'); ?></h3>
                <div class="help-costs">
                    <ul>
                        <li><?php echo esc_html__('Turn on caching from the start and be conservative with rate limits', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Keep an eye on your API usage in your provider\'s dashboard', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Set up usage alerts in your API account so you don\'t get surprised', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Keep your custom prompts shorter to use fewer tokens', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Faster models are usually cheaper - you don\'t always need the premium ones', 'ai-explainer'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Support Tab -->
        <div class="tab-content" id="support-tab" style="display: none;">
            <h2><?php echo esc_html__('Support & Contact', 'ai-explainer'); ?></h2>
            
            <div class="support-section">
                <h3><?php echo esc_html__('Developer Information', 'ai-explainer'); ?></h3>
                <div class="developer-info">
                    <p><strong><?php echo esc_html__('Developer:', 'ai-explainer'); ?></strong> Billy Patel</p>
                    <p><strong><?php echo esc_html__('Email:', 'ai-explainer'); ?></strong> <a href="mailto:billy@billymedia.co.uk">billy@billymedia.co.uk</a></p>
                    <p><strong><?php echo esc_html__('Website:', 'ai-explainer'); ?></strong> <a href="https://billymedia.co.uk" target="_blank">billymedia.co.uk</a></p>
                </div>
            </div>
            
            <div class="support-section">
                <h3><?php echo esc_html__('Project Links', 'ai-explainer'); ?></h3>
                <div class="project-links">
                    <p>
                        <strong><?php echo esc_html__('GitHub Repository:', 'ai-explainer'); ?></strong><br>
                        <a href="https://github.com/billymedia/wp-explainer" target="_blank" class="button button-secondary">
                            <?php echo esc_html__('View on GitHub', 'ai-explainer'); ?>
                        </a>
                    </p>
                    <p class="description">
                        <?php echo esc_html__('Visit our GitHub repository for documentation, source code, and contributing to the project.', 'ai-explainer'); ?>
                    </p>
                </div>
            </div>
            
            <div class="support-section">
                <h3><?php echo esc_html__('Getting Help', 'ai-explainer'); ?></h3>
                <div class="support-options">
                    <div class="support-option">
                        <h4><?php echo esc_html__('1. Check the Help Tab', 'ai-explainer'); ?></h4>
                        <p><?php echo esc_html__('The Help tab above has most of the common setup and troubleshooting stuff you\'ll need.', 'ai-explainer'); ?></p>
                    </div>
                    
                    <div class="support-option">
                        <h4><?php echo esc_html__('2. Enable Debug Mode', 'ai-explainer'); ?></h4>
                        <p><?php echo esc_html__('If things aren\'t working, flip on Debug Mode in the Advanced tab to see what\'s happening under the hood.', 'ai-explainer'); ?></p>
                    </div>
                    
                    <div class="support-option">
                        <h4><?php echo esc_html__('3. GitHub Issues', 'ai-explainer'); ?></h4>
                        <p><?php echo esc_html__('Found a bug or have an idea? Drop it on', 'ai-explainer'); ?> <a href="https://github.com/billymedia/wp-explainer/issues" target="_blank">GitHub</a> <?php echo esc_html__('and I\'ll take a look.', 'ai-explainer'); ?></p>
                    </div>
                    
                    <div class="support-option">
                        <h4><?php echo esc_html__('4. Custom Work', 'ai-explainer'); ?></h4>
                        <p><?php echo esc_html__('Need something custom or want professional help? Just email me directly at billy@billymedia.co.uk', 'ai-explainer'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="support-section">
                <h3><?php echo esc_html__('When Requesting Support', 'ai-explainer'); ?></h3>
                <div class="support-info-needed">
                    <p><?php echo esc_html__('When you need help, it really speeds things up if you can include:', 'ai-explainer'); ?></p>
                    <ul>
                        <li><?php echo esc_html__('WordPress version', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('PHP version', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Plugin version', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('AI provider being used (OpenAI/Claude)', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Browser and device information', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Detailed description of the issue', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Steps to reproduce the problem', 'ai-explainer'); ?></li>
                        <li><?php echo esc_html__('Any error messages or debug logs', 'ai-explainer'); ?></li>
                    </ul>
                </div>
            </div>
            
            
            <div class="support-section">
                <h3><?php echo esc_html__('System Information', 'ai-explainer'); ?></h3>
                <div class="system-info">
                    <table class="form-table">
                        <tr>
                            <th><?php echo esc_html__('Plugin Version:', 'ai-explainer'); ?></th>
                            <td><?php echo esc_html(EXPLAINER_PLUGIN_VERSION); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('WordPress Version:', 'ai-explainer'); ?></th>
                            <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('PHP Version:', 'ai-explainer'); ?></th>
                            <td><?php echo esc_html(PHP_VERSION); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('Current Theme:', 'ai-explainer'); ?></th>
                            <td><?php echo esc_html(wp_get_theme()->get('Name')); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <?php submit_button(); ?>
    </form>
    
    <div id="admin-messages"></div>
</div>

<script>
jQuery(document).ready(function($) {
    // Function to show a specific tab
    function showTab(tabHash) {
        // Remove active class from all tabs
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').hide();
        
        // Add active class to clicked tab
        $('.nav-tab[href="' + tabHash + '"]').addClass('nav-tab-active');
        
        // Show corresponding content
        const target = tabHash + '-tab';
        $(target).show();
        
        // Store the active tab in localStorage
        localStorage.setItem('explainer_active_tab', tabHash);
    }
    
    // Tab navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        const tabHash = $(this).attr('href');
        showTab(tabHash);
    });
    
    // Restore active tab on page load
    function restoreActiveTab() {
        const savedTab = localStorage.getItem('explainer_active_tab');
        
        // Check if saved tab exists and is valid
        if (savedTab && $('.nav-tab[href="' + savedTab + '"]').length > 0) {
            showTab(savedTab);
        } else {
            // Default to first tab (basic) if no saved tab or invalid tab
            showTab('#basic');
        }
    }
    
    // Initialize the correct tab on page load
    restoreActiveTab();
    
    // API key visibility toggle for OpenAI
    $('#toggle-api-key-visibility').on('click', function() {
        const input = $('#explainer_api_key');
        const button = $(this);
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            button.text('<?php echo esc_js(__('Hide', 'ai-explainer')); ?>');
        } else {
            input.attr('type', 'password');
            button.text('<?php echo esc_js(__('Show', 'ai-explainer')); ?>');
        }
    });
    
    // API key visibility toggle for Claude
    $('#toggle-claude-key-visibility').on('click', function() {
        const input = $('#explainer_claude_api_key');
        const button = $(this);
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            button.text('<?php echo esc_js(__('Hide', 'ai-explainer')); ?>');
        } else {
            input.attr('type', 'password');
            button.text('<?php echo esc_js(__('Show', 'ai-explainer')); ?>');
        }
    });
    
    // Provider selection is now handled in admin.js file
    
    // Real-time tooltip preview
    function updateTooltipPreview() {
        const bgColor = $('#explainer_tooltip_bg_color').val();
        const textColor = $('#explainer_tooltip_text_color').val();
        const footerColor = $('#explainer_tooltip_footer_color').val();
        
        // Detect site font from paragraph elements
        const siteFont = detectSiteFont();
        
        // Use CSS custom properties for dynamic updates (affects both background and arrow)
        document.documentElement.style.setProperty('--explainer-tooltip-bg-color', bgColor);
        document.documentElement.style.setProperty('--explainer-tooltip-text-color', textColor);
        document.documentElement.style.setProperty('--explainer-tooltip-footer-color', footerColor);
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
    
    // Real-time button preview
    function updateButtonPreview() {
        const enabledColor = $('#explainer_button_enabled_color').val();
        const disabledColor = $('#explainer_button_disabled_color').val();
        const textColor = $('#explainer_button_text_color').val();
        
        $('#preview-button-enabled').css({
            'background-color': enabledColor,
            'color': textColor
        });
        
        $('#preview-button-disabled').css({
            'background-color': disabledColor,
            'color': textColor
        });
    }
    
    $('#explainer_tooltip_bg_color, #explainer_tooltip_text_color, #explainer_tooltip_footer_color').on('input', updateTooltipPreview);
    $('#explainer_button_enabled_color, #explainer_button_disabled_color, #explainer_button_text_color').on('input', updateButtonPreview);
    
    // Initialize previews
    updateTooltipPreview();
    updateButtonPreview();
    
    // Language change handler
    $('#explainer_language').on('change', function() {
        updatePreviewLanguage();
    });
    
    // Update preview language
    function updatePreviewLanguage() {
        const selectedLanguage = $('#explainer_language').val();
        const selectedProvider = $('#explainer_api_provider').val();
        
        // Define localized strings
        const strings = {
            'en_US': {
                'title': 'Explanation',
                'content': 'This is how your tooltip will look with the selected colors. It matches the actual frontend design with proper spacing and typography.',
                'disclaimer': 'AI-generated content may not always be accurate',
                'powered_by': 'Powered by'
            },
            'en_GB': {
                'title': 'Explanation', 
                'content': 'This is how your tooltip will look with the selected colours. It matches the actual frontend design with proper spacing and typography.',
                'disclaimer': 'AI-generated content may not always be accurate',
                'powered_by': 'Powered by'
            },
            'es_ES': {
                'title': 'Explicación',
                'content': 'Así es como se verá tu tooltip con los colores seleccionados. Coincide con el diseño frontend real con el espaciado y tipografía adecuados.',
                'disclaimer': 'El contenido generado por IA puede no ser siempre preciso',
                'powered_by': 'Desarrollado por'
            },
            'de_DE': {
                'title': 'Erklärung',
                'content': 'So wird Ihr Tooltip mit den ausgewählten Farben aussehen. Es entspricht dem tatsächlichen Frontend-Design mit angemessenen Abständen und Typografie.',
                'disclaimer': 'KI-generierte Inhalte sind möglicherweise nicht immer korrekt',
                'powered_by': 'Unterstützt von'
            },
            'fr_FR': {
                'title': 'Explication',
                'content': 'Voici à quoi ressemblera votre tooltip avec les couleurs sélectionnées. Il correspond au design frontend réel avec un espacement et une typographie appropriés.',
                'disclaimer': 'Le contenu généré par IA peut ne pas toujours être précis',
                'powered_by': 'Propulsé par'
            },
            'hi_IN': {
                'title': 'व्याख्या',
                'content': 'चयनित रंगों के साथ आपका टूलटिप इस तरह दिखेगा। यह उचित स्पेसिंग और टाइपोग्राफी के साथ वास्तविक फ्रंटएंड डिज़ाइन से मेल खाता है।',
                'disclaimer': 'AI-जनरेटेड सामग्री हमेशा सटीक नहीं हो सकती',
                'powered_by': 'द्वारा संचालित'
            },
            'zh_CN': {
                'title': '解释',
                'content': '这是您的工具提示在所选颜色下的外观。它与实际的前端设计相匹配，具有适当的间距和排版。',
                'disclaimer': 'AI生成的内容可能并不总是准确的',
                'powered_by': '技术支持'
            }
        };
        
        // Get strings for selected language, fallback to English
        const langStrings = strings[selectedLanguage] || strings['en_GB'];
        
        // Update preview text
        $('#preview-tooltip-title').text(langStrings.title);
        $('#preview-tooltip-content').text(langStrings.content);
        $('#preview-disclaimer').text(langStrings.disclaimer);
        
        // Update provider text
        const providerName = selectedProvider === 'claude' ? 'Claude' : 'OpenAI';
        $('#preview-provider').text(langStrings.powered_by + ' ' + providerName);
    }
    
    // Initialize preview language
    updatePreviewLanguage();
    
    // Update preview when provider changes too
    $('#explainer_api_provider').on('change', function() {
        updatePreviewLanguage();
    });
    
    
    // Cache count functionality
    function loadCacheCount() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'explainer_get_cache_count',
                nonce: '<?php echo esc_js(wp_create_nonce('explainer_admin_nonce')); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#cache-count-display').text(response.data.count);
                } else {
                    $('#cache-count-display').text('0');
                }
            },
            error: function() {
                $('#cache-count-display').text('Error loading');
            }
        });
    }
    
    // Clear cache functionality (Advanced tab)
    $('#clear-cache-advanced').on('click', function() {
        const button = $(this);
        const originalText = button.text();
        
        button.text('<?php echo esc_js(__('Clearing...', 'ai-explainer')); ?>').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'explainer_clear_cache',
                nonce: '<?php echo esc_js(wp_create_nonce('explainer_admin_nonce')); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#cache-count-display').text('0');
                    $('#admin-messages').html('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                } else {
                    $('#admin-messages').html('<div class="notice notice-error is-dismissible"><p>' + (response.data.message || '<?php echo esc_js(__('Failed to clear cache', 'ai-explainer')); ?>') + '</p></div>');
                }
            },
            error: function() {
                $('#admin-messages').html('<div class="notice notice-error is-dismissible"><p><?php echo esc_js(__('Error clearing cache', 'ai-explainer')); ?></p></div>');
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Load cache count on page load and when Advanced tab is shown
    loadCacheCount();
    
    // Reload cache count when switching to Advanced tab
    $('.nav-tab[href="#advanced"]').on('click', function() {
        setTimeout(loadCacheCount, 100); // Small delay to ensure tab is shown
    });
    
});
</script>