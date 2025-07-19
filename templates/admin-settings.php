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
    <h1><?php echo esc_html__('WP AI Explainer Settings', 'explainer-plugin'); ?></h1>
    
    <div class="explainer-admin-header">
        <p><?php echo esc_html__('Configure your WP AI Explainer plugin settings below. This plugin helps users understand complex text by providing AI-generated explanations via tooltips.', 'explainer-plugin'); ?></p>
    </div>
    
    <div class="explainer-admin-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#basic" class="nav-tab nav-tab-active"><?php echo esc_html__('Basic Settings', 'explainer-plugin'); ?></a>
            <a href="#content" class="nav-tab"><?php echo esc_html__('Content Rules', 'explainer-plugin'); ?></a>
            <a href="#performance" class="nav-tab"><?php echo esc_html__('Performance', 'explainer-plugin'); ?></a>
            <a href="#appearance" class="nav-tab"><?php echo esc_html__('Appearance', 'explainer-plugin'); ?></a>
            <a href="#advanced" class="nav-tab"><?php echo esc_html__('Advanced', 'explainer-plugin'); ?></a>
            <a href="#help" class="nav-tab"><?php echo esc_html__('Help', 'explainer-plugin'); ?></a>
            <a href="#support" class="nav-tab"><?php echo esc_html__('Support', 'explainer-plugin'); ?></a>
        </nav>
    </div>
    
    <form method="post" action="options.php" id="explainer-settings-form">
        <?php settings_fields('explainer_settings'); ?>
        
        <!-- Basic Settings Tab -->
        <div class="tab-content" id="basic-tab">
            <h2><?php echo esc_html__('Basic Configuration', 'explainer-plugin'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Plugin Status', 'explainer-plugin'); ?></th>
                    <td>
                        <?php 
                        $is_auto_disabled = explainer_is_auto_disabled();
                        $is_enabled = get_option('explainer_enabled', true);
                        ?>
                        
                        <?php if ($is_auto_disabled): ?>
                            <!-- Auto-disabled state -->
                            <div class="explainer-status-disabled">
                                <p><span class="dashicons dashicons-warning" style="color: #dc3232;"></span> 
                                <strong style="color: #dc3232;"><?php echo esc_html__('Plugin Automatically Disabled', 'explainer-plugin'); ?></strong></p>
                                
                                <?php 
                                $stats = explainer_get_usage_exceeded_stats();
                                if (!empty($stats['reason'])): ?>
                                    <p><strong><?php echo esc_html__('Reason:', 'explainer-plugin'); ?></strong> <?php echo esc_html($stats['reason']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($stats['provider'])): ?>
                                    <p><strong><?php echo esc_html__('Provider:', 'explainer-plugin'); ?></strong> <?php echo esc_html($stats['provider']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($stats['time_since'])): ?>
                                    <p><strong><?php echo esc_html__('Disabled:', 'explainer-plugin'); ?></strong> <?php echo esc_html($stats['time_since']); ?></p>
                                <?php endif; ?>
                                
                                <div class="explainer-reenable-section" style="margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
                                    <h4><?php echo esc_html__('Re-enable Plugin', 'explainer-plugin'); ?></h4>
                                    <p><?php echo esc_html__('Before re-enabling, please ensure you have resolved the API usage limit issues with your provider.', 'explainer-plugin'); ?></p>
                                    <button type="button" class="button button-primary explainer-reenable-btn-settings" 
                                            data-nonce="<?php echo wp_create_nonce('explainer_reenable_plugin'); ?>">
                                        <?php echo esc_html__('Re-enable Plugin Now', 'explainer-plugin'); ?>
                                    </button>
                                    <p class="description" style="margin-top: 10px;">
                                        <?php echo esc_html__('This will clear the auto-disable flag and restore normal plugin functionality.', 'explainer-plugin'); ?>
                                    </p>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Normal enabled/disabled state -->
                            <fieldset>
                                <label for="explainer_enabled">
                                    <input type="checkbox" name="explainer_enabled" id="explainer_enabled" value="1" <?php checked($is_enabled, true); ?> />
                                    <?php echo esc_html__('Enable AI Explainer plugin', 'explainer-plugin'); ?>
                                </label>
                                <p class="description"><?php echo esc_html__('Enable or disable the plugin functionality site-wide.', 'explainer-plugin'); ?></p>
                                
                                <?php if (!$is_enabled): ?>
                                    <p style="color: #d63638; margin-top: 8px;">
                                        <span class="dashicons dashicons-info" style="color: #d63638;"></span>
                                        <?php echo esc_html__('Plugin is currently disabled manually.', 'explainer-plugin'); ?>
                                    </p>
                                <?php endif; ?>
                            </fieldset>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_language"><?php echo esc_html__('Language', 'explainer-plugin'); ?></label>
                    </th>
                    <td>
                        <select name="explainer_language" id="explainer_language">
                            <option value="en_US" <?php selected(get_option('explainer_language', 'en_GB'), 'en_US'); ?>>
                                <?php echo esc_html__('English (United States)', 'explainer-plugin'); ?>
                            </option>
                            <option value="en_GB" <?php selected(get_option('explainer_language', 'en_GB'), 'en_GB'); ?>>
                                <?php echo esc_html__('English (United Kingdom)', 'explainer-plugin'); ?>
                            </option>
                            <option value="es_ES" <?php selected(get_option('explainer_language', 'en_GB'), 'es_ES'); ?>>
                                <?php echo esc_html__('Spanish (Spain)', 'explainer-plugin'); ?>
                            </option>
                            <option value="de_DE" <?php selected(get_option('explainer_language', 'en_GB'), 'de_DE'); ?>>
                                <?php echo esc_html__('German (Germany)', 'explainer-plugin'); ?>
                            </option>
                            <option value="fr_FR" <?php selected(get_option('explainer_language', 'en_GB'), 'fr_FR'); ?>>
                                <?php echo esc_html__('French (France)', 'explainer-plugin'); ?>
                            </option>
                            <option value="hi_IN" <?php selected(get_option('explainer_language', 'en_GB'), 'hi_IN'); ?>>
                                <?php echo esc_html__('Hindi (India)', 'explainer-plugin'); ?>
                            </option>
                            <option value="zh_CN" <?php selected(get_option('explainer_language', 'en_GB'), 'zh_CN'); ?>>
                                <?php echo esc_html__('Chinese (Simplified)', 'explainer-plugin'); ?>
                            </option>
                        </select>
                        <p class="description"><?php echo esc_html__('Select the language for the AI explanations.', 'explainer-plugin'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_api_provider"><?php echo esc_html__('AI Provider', 'explainer-plugin'); ?></label>
                    </th>
                    <td>
                        <select name="explainer_api_provider" id="explainer_api_provider">
                            <option value="openai" <?php selected(get_option('explainer_api_provider', 'openai'), 'openai'); ?>>
                                <?php echo esc_html__('OpenAI (GPT Models)', 'explainer-plugin'); ?>
                            </option>
                            <option value="claude" <?php selected(get_option('explainer_api_provider', 'openai'), 'claude'); ?>>
                                <?php echo esc_html__('Claude (Anthropic)', 'explainer-plugin'); ?>
                            </option>
                        </select>
                        <p class="description"><?php echo esc_html__('Choose your AI provider. Each provider has different models and pricing.', 'explainer-plugin'); ?></p>
                    </td>
                </tr>
                
                <tr class="api-key-row openai-fields">
                    <th scope="row">
                        <label for="explainer_api_key"><?php echo esc_html__('OpenAI API Key', 'explainer-plugin'); ?></label>
                    </th>
                    <td>
<?php
                        // Get masked API key for display (security improvement)
                        $api_proxy = new ExplainerPlugin_API_Proxy();
                        $decrypted_api_key = $api_proxy->get_decrypted_api_key();
                        $masked_key = '';
                        if (!empty($decrypted_api_key)) {
                            // Show only first 3 and last 4 characters
                            $masked_key = substr($decrypted_api_key, 0, 3) . str_repeat('*', max(0, strlen($decrypted_api_key) - 7)) . substr($decrypted_api_key, -4);
                        }
                        ?>
                        <input type="password" name="explainer_api_key" id="explainer_api_key" value="<?php echo esc_attr($decrypted_api_key ?: ''); ?>" class="regular-text" placeholder="<?php echo esc_attr($masked_key); ?>" />
                        <button type="button" class="button button-secondary" id="toggle-api-key-visibility">
                            <?php echo esc_html__('Show', 'explainer-plugin'); ?>
                        </button>
                        <p class="description">
                            <?php echo esc_html__('Enter your OpenAI API key. Get one from', 'explainer-plugin'); ?> 
                            <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">platform.openai.com</a>
                        </p>
                        <div id="api-key-status"></div>
                    </td>
                </tr>
                
                <tr class="api-key-row claude-fields" style="display: none;">
                    <th scope="row">
                        <label for="explainer_claude_api_key"><?php echo esc_html__('Claude API Key', 'explainer-plugin'); ?></label>
                    </th>
                    <td>
<?php
                        // Get masked Claude API key for display (using proper decryption)
                        $api_proxy = new ExplainerPlugin_API_Proxy();
                        $decrypted_claude_key = $api_proxy->get_decrypted_api_key_for_provider('claude');
                        $masked_claude_key = '';
                        if (!empty($decrypted_claude_key)) {
                            // Show only first 3 and last 4 characters
                            $masked_claude_key = substr($decrypted_claude_key, 0, 3) . str_repeat('*', max(0, strlen($decrypted_claude_key) - 7)) . substr($decrypted_claude_key, -4);
                        }
                        ?>
                        <input type="password" name="explainer_claude_api_key" id="explainer_claude_api_key" value="<?php echo esc_attr($decrypted_claude_key ?: ''); ?>" class="regular-text" placeholder="<?php echo esc_attr($masked_claude_key); ?>" />
                        <button type="button" class="button button-secondary" id="toggle-claude-key-visibility">
                            <?php echo esc_html__('Show', 'explainer-plugin'); ?>
                        </button>
                        <p class="description">
                            <?php echo esc_html__('Enter your Claude API key. Get one from', 'explainer-plugin'); ?> 
                            <a href="https://console.anthropic.com/account/keys" target="_blank" rel="noopener noreferrer">console.anthropic.com</a>
                        </p>
                        <div id="claude-api-key-status"></div>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_api_model"><?php echo esc_html__('AI Model', 'explainer-plugin'); ?></label>
                    </th>
                    <td>
                        <select name="explainer_api_model" id="explainer_api_model">
                            <!-- OpenAI Models -->
                            <optgroup label="<?php echo esc_attr__('OpenAI Models', 'explainer-plugin'); ?>" class="openai-models">
                                <option value="gpt-3.5-turbo" <?php selected(get_option('explainer_api_model', 'gpt-3.5-turbo'), 'gpt-3.5-turbo'); ?>>
                                    <?php echo esc_html__('GPT-3.5 Turbo (Recommended)', 'explainer-plugin'); ?>
                                </option>
                                <option value="gpt-4" <?php selected(get_option('explainer_api_model', 'gpt-3.5-turbo'), 'gpt-4'); ?>>
                                    <?php echo esc_html__('GPT-4 (Higher quality, more expensive)', 'explainer-plugin'); ?>
                                </option>
                                <option value="gpt-4-turbo" <?php selected(get_option('explainer_api_model', 'gpt-3.5-turbo'), 'gpt-4-turbo'); ?>>
                                    <?php echo esc_html__('GPT-4 Turbo (Fast and efficient)', 'explainer-plugin'); ?>
                                </option>
                            </optgroup>
                            <!-- Claude Models -->
                            <optgroup label="<?php echo esc_attr__('Claude Models', 'explainer-plugin'); ?>" class="claude-models" style="display: none;">
                                <option value="claude-3-haiku-20240307" <?php selected(get_option('explainer_api_model', 'gpt-3.5-turbo'), 'claude-3-haiku-20240307'); ?>>
                                    <?php echo esc_html__('Claude 3 Haiku (Fast and efficient)', 'explainer-plugin'); ?>
                                </option>
                                <option value="claude-3-sonnet-20240229" <?php selected(get_option('explainer_api_model', 'gpt-3.5-turbo'), 'claude-3-sonnet-20240229'); ?>>
                                    <?php echo esc_html__('Claude 3 Sonnet (Balanced)', 'explainer-plugin'); ?>
                                </option>
                                <option value="claude-3-opus-20240229" <?php selected(get_option('explainer_api_model', 'gpt-3.5-turbo'), 'claude-3-opus-20240229'); ?>>
                                    <?php echo esc_html__('Claude 3 Opus (Highest quality)', 'explainer-plugin'); ?>
                                </option>
                            </optgroup>
                        </select>
                        <p class="description"><?php echo esc_html__('Select the AI model to use for generating explanations. Models vary by provider.', 'explainer-plugin'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_custom_prompt"><?php echo esc_html__('Custom Prompt Template', 'explainer-plugin'); ?></label>
                    </th>
                    <td>
                        <textarea name="explainer_custom_prompt" id="explainer_custom_prompt" rows="4" cols="60" class="large-text code"><?php echo esc_textarea(get_option('explainer_custom_prompt', 'Please provide a clear, concise explanation of the following text in 1-2 sentences: {{snippet}}')); ?></textarea>
                        <p class="description">
                            <?php echo esc_html__('Customize the prompt sent to the AI. Use {{snippet}} where you want the selected text to appear. Maximum 500 characters.', 'explainer-plugin'); ?>
                        </p>
                        <p class="description">
                            <strong><?php echo esc_html__('Example:', 'explainer-plugin'); ?></strong> <?php echo esc_html__('"Explain this text in simple terms for a beginner: {{snippet}}"', 'explainer-plugin'); ?>
                        </p>
                        <div class="prompt-actions">
                            <button type="button" class="button button-secondary" id="reset-prompt-default"><?php echo esc_html__('Reset to Default', 'explainer-plugin'); ?></button>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Content Rules Tab -->
        <div class="tab-content" id="content-tab" style="display: none;">
            <h2><?php echo esc_html__('Content Selection Rules', 'explainer-plugin'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Selection Length', 'explainer-plugin'); ?></th>
                    <td>
                        <fieldset>
                            <label for="explainer_min_selection_length">
                                <?php echo esc_html__('Minimum characters:', 'explainer-plugin'); ?>
                                <input type="number" name="explainer_min_selection_length" id="explainer_min_selection_length" value="<?php echo esc_attr(get_option('explainer_min_selection_length', 3)); ?>" min="1" max="50" class="small-text" />
                            </label>
                            <br><br>
                            <label for="explainer_max_selection_length">
                                <?php echo esc_html__('Maximum characters:', 'explainer-plugin'); ?>
                                <input type="number" name="explainer_max_selection_length" id="explainer_max_selection_length" value="<?php echo esc_attr(get_option('explainer_max_selection_length', 200)); ?>" min="50" max="1000" class="small-text" />
                            </label>
                            <p class="description"><?php echo esc_html__('Set the minimum and maximum character limits for text selection.', 'explainer-plugin'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Word Count', 'explainer-plugin'); ?></th>
                    <td>
                        <fieldset>
                            <label for="explainer_min_words">
                                <?php echo esc_html__('Minimum words:', 'explainer-plugin'); ?>
                                <input type="number" name="explainer_min_words" id="explainer_min_words" value="<?php echo esc_attr(get_option('explainer_min_words', 1)); ?>" min="1" max="10" class="small-text" />
                            </label>
                            <br><br>
                            <label for="explainer_max_words">
                                <?php echo esc_html__('Maximum words:', 'explainer-plugin'); ?>
                                <input type="number" name="explainer_max_words" id="explainer_max_words" value="<?php echo esc_attr(get_option('explainer_max_words', 30)); ?>" min="5" max="100" class="small-text" />
                            </label>
                            <p class="description"><?php echo esc_html__('Set the minimum and maximum word count limits for text selection.', 'explainer-plugin'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_included_selectors"><?php echo esc_html__('Included Areas', 'explainer-plugin'); ?></label>
                    </th>
                    <td>
                        <textarea name="explainer_included_selectors" id="explainer_included_selectors" rows="4" cols="50" class="large-text"><?php echo esc_textarea(get_option('explainer_included_selectors', 'article, main, .content, .entry-content, .post-content')); ?></textarea>
                        <p class="description"><?php echo esc_html__('CSS selectors for areas where text selection is allowed (comma-separated).', 'explainer-plugin'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_excluded_selectors"><?php echo esc_html__('Excluded Areas', 'explainer-plugin'); ?></label>
                    </th>
                    <td>
                        <textarea name="explainer_excluded_selectors" id="explainer_excluded_selectors" rows="4" cols="50" class="large-text"><?php echo esc_textarea(get_option('explainer_excluded_selectors', 'nav, header, footer, aside, .widget, #wpadminbar, .admin-bar')); ?></textarea>
                        <p class="description"><?php echo esc_html__('CSS selectors for areas where text selection is blocked (comma-separated).', 'explainer-plugin'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_blocked_words"><?php echo esc_html__('Blocked Words', 'explainer-plugin'); ?></label>
                    </th>
                    <td>
                        <textarea name="explainer_blocked_words" id="explainer_blocked_words" rows="8" cols="50" class="large-text" placeholder="<?php echo esc_attr__('Enter one word or phrase per line', 'explainer-plugin'); ?>"><?php echo esc_textarea(get_option('explainer_blocked_words', '')); ?></textarea>
                        <p class="description">
                            <?php echo esc_html__('Enter words or phrases that should be blocked from getting AI explanations (one per line).', 'explainer-plugin'); ?>
                            <br>
                            <span id="blocked-words-count">0</span> <?php echo esc_html__('words blocked', 'explainer-plugin'); ?>
                        </p>
                        
                        <div class="blocked-words-options" style="margin-top: 10px;">
                            <label>
                                <input type="checkbox" name="explainer_blocked_words_case_sensitive" id="explainer_blocked_words_case_sensitive" value="1" <?php checked(get_option('explainer_blocked_words_case_sensitive', false), true); ?> />
                                <?php echo esc_html__('Case sensitive matching', 'explainer-plugin'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="explainer_blocked_words_whole_word" id="explainer_blocked_words_whole_word" value="1" <?php checked(get_option('explainer_blocked_words_whole_word', false), true); ?> />
                                <?php echo esc_html__('Match whole words only', 'explainer-plugin'); ?>
                            </label>
                        </div>
                        
                        <div class="blocked-words-actions" style="margin-top: 10px;">
                            <button type="button" class="button" id="clear-blocked-words"><?php echo esc_html__('Clear All', 'explainer-plugin'); ?></button>
                            <button type="button" class="button" id="load-default-blocked-words"><?php echo esc_html__('Load Common Inappropriate Words', 'explainer-plugin'); ?></button>
                        </div>
                        
                        <p class="description" style="margin-top: 10px;">
                            <strong><?php echo esc_html__('Note:', 'explainer-plugin'); ?></strong> 
                            <?php echo esc_html__('Maximum 500 words, 100 characters per word. Special characters will be removed.', 'explainer-plugin'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Performance Tab -->
        <div class="tab-content" id="performance-tab" style="display: none;">
            <h2><?php echo esc_html__('Performance & Caching', 'explainer-plugin'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Caching', 'explainer-plugin'); ?></th>
                    <td>
                        <fieldset>
                            <label for="explainer_cache_enabled">
                                <input type="checkbox" name="explainer_cache_enabled" id="explainer_cache_enabled" value="1" <?php checked(get_option('explainer_cache_enabled', true), true); ?> />
                                <?php echo esc_html__('Enable caching to reduce API calls and costs', 'explainer-plugin'); ?>
                            </label>
                            <br><br>
                            <label for="explainer_cache_duration">
                                <?php echo esc_html__('Cache Duration:', 'explainer-plugin'); ?>
                                <input type="number" name="explainer_cache_duration" id="explainer_cache_duration" value="<?php echo esc_attr(get_option('explainer_cache_duration', 24)); ?>" min="1" max="168" class="small-text" />
                                <?php echo esc_html__('hours', 'explainer-plugin'); ?>
                            </label>
                            <p class="description"><?php echo esc_html__('How long to cache explanations (1-168 hours).', 'explainer-plugin'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Rate Limiting', 'explainer-plugin'); ?></th>
                    <td>
                        <fieldset>
                            <label for="explainer_rate_limit_enabled">
                                <input type="checkbox" name="explainer_rate_limit_enabled" id="explainer_rate_limit_enabled" value="1" <?php checked(get_option('explainer_rate_limit_enabled', true), true); ?> />
                                <?php echo esc_html__('Enable rate limiting to prevent abuse', 'explainer-plugin'); ?>
                            </label>
                            <br><br>
                            <label for="explainer_rate_limit_logged">
                                <?php echo esc_html__('Logged-in users:', 'explainer-plugin'); ?>
                                <input type="number" name="explainer_rate_limit_logged" id="explainer_rate_limit_logged" value="<?php echo esc_attr(get_option('explainer_rate_limit_logged', 20)); ?>" min="1" max="100" class="small-text" />
                                <?php echo esc_html__('requests per minute', 'explainer-plugin'); ?>
                            </label>
                            <br><br>
                            <label for="explainer_rate_limit_anonymous">
                                <?php echo esc_html__('Anonymous users:', 'explainer-plugin'); ?>
                                <input type="number" name="explainer_rate_limit_anonymous" id="explainer_rate_limit_anonymous" value="<?php echo esc_attr(get_option('explainer_rate_limit_anonymous', 10)); ?>" min="1" max="50" class="small-text" />
                                <?php echo esc_html__('requests per minute', 'explainer-plugin'); ?>
                            </label>
                            <p class="description"><?php echo esc_html__('Set different rate limits for logged-in and anonymous users.', 'explainer-plugin'); ?></p>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Appearance Tab -->
        <div class="tab-content" id="appearance-tab" style="display: none;">
            <h2><?php echo esc_html__('Appearance Customization', 'explainer-plugin'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Toggle Button Position', 'explainer-plugin'); ?></th>
                    <td>
                        <select name="explainer_toggle_position" id="explainer_toggle_position">
                            <option value="bottom-right" <?php selected(get_option('explainer_toggle_position', 'bottom-right'), 'bottom-right'); ?>>
                                <?php echo esc_html__('Bottom Right', 'explainer-plugin'); ?>
                            </option>
                            <option value="bottom-left" <?php selected(get_option('explainer_toggle_position', 'bottom-right'), 'bottom-left'); ?>>
                                <?php echo esc_html__('Bottom Left', 'explainer-plugin'); ?>
                            </option>
                            <option value="top-right" <?php selected(get_option('explainer_toggle_position', 'bottom-right'), 'top-right'); ?>>
                                <?php echo esc_html__('Top Right', 'explainer-plugin'); ?>
                            </option>
                            <option value="top-left" <?php selected(get_option('explainer_toggle_position', 'bottom-right'), 'top-left'); ?>>
                                <?php echo esc_html__('Top Left', 'explainer-plugin'); ?>
                            </option>
                        </select>
                        <p class="description"><?php echo esc_html__('Choose where to position the toggle button on the page.', 'explainer-plugin'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Tooltip Colors', 'explainer-plugin'); ?></th>
                    <td>
                        <fieldset>
                            <label for="explainer_tooltip_bg_color">
                                <?php echo esc_html__('Background Color:', 'explainer-plugin'); ?>
                                <input type="color" name="explainer_tooltip_bg_color" id="explainer_tooltip_bg_color" value="<?php echo esc_attr(get_option('explainer_tooltip_bg_color', '#333333')); ?>" />
                            </label>
                            <br><br>
                            <label for="explainer_tooltip_text_color">
                                <?php echo esc_html__('Text Color:', 'explainer-plugin'); ?>
                                <input type="color" name="explainer_tooltip_text_color" id="explainer_tooltip_text_color" value="<?php echo esc_attr(get_option('explainer_tooltip_text_color', '#ffffff')); ?>" />
                            </label>
                            <p class="description"><?php echo esc_html__('Customize the tooltip background and text colors.', 'explainer-plugin'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Toggle Button Colors', 'explainer-plugin'); ?></th>
                    <td>
                        <fieldset>
                            <label for="explainer_button_enabled_color">
                                <?php echo esc_html__('Enabled Color:', 'explainer-plugin'); ?>
                                <input type="color" name="explainer_button_enabled_color" id="explainer_button_enabled_color" value="<?php echo esc_attr(get_option('explainer_button_enabled_color', '#46b450')); ?>" />
                            </label>
                            <br><br>
                            <label for="explainer_button_disabled_color">
                                <?php echo esc_html__('Disabled Color:', 'explainer-plugin'); ?>
                                <input type="color" name="explainer_button_disabled_color" id="explainer_button_disabled_color" value="<?php echo esc_attr(get_option('explainer_button_disabled_color', '#666666')); ?>" />
                            </label>
                            <br><br>
                            <label for="explainer_button_text_color">
                                <?php echo esc_html__('Button Text Color:', 'explainer-plugin'); ?>
                                <input type="color" name="explainer_button_text_color" id="explainer_button_text_color" value="<?php echo esc_attr(get_option('explainer_button_text_color', '#ffffff')); ?>" />
                            </label>
                            <p class="description"><?php echo esc_html__('Customize the toggle button colors for enabled, disabled, and text.', 'explainer-plugin'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Tooltip Footer', 'explainer-plugin'); ?></th>
                    <td>
                        <fieldset>
                            <label for="explainer_show_disclaimer">
                                <input type="checkbox" name="explainer_show_disclaimer" id="explainer_show_disclaimer" value="1" <?php checked(get_option('explainer_show_disclaimer', true), true); ?> />
                                <?php echo esc_html__('Show accuracy disclaimer', 'explainer-plugin'); ?>
                            </label>
                            <p class="description"><?php echo esc_html__('Displays "AI-generated content may not always be accurate" at the bottom of explanations.', 'explainer-plugin'); ?></p>
                            <br>
                            <label for="explainer_show_provider">
                                <input type="checkbox" name="explainer_show_provider" id="explainer_show_provider" value="1" <?php checked(get_option('explainer_show_provider', true), true); ?> />
                                <?php echo esc_html__('Show AI provider attribution', 'explainer-plugin'); ?>
                            </label>
                            <p class="description"><?php echo esc_html__('Displays "Powered by OpenAI" or "Powered by Claude" to credit the AI provider.', 'explainer-plugin'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explainer_tooltip_footer_color"><?php echo esc_html__('Footer Text Color', 'explainer-plugin'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="explainer_tooltip_footer_color" id="explainer_tooltip_footer_color" value="<?php echo esc_attr(get_option('explainer_tooltip_footer_color', '#ffffff')); ?>" class="color-field" data-default-color="#ffffff" />
                        <p class="description"><?php echo esc_html__('Choose the color for footer text in tooltips (disclaimer and provider attribution).', 'explainer-plugin'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php echo esc_html__('Preview', 'explainer-plugin'); ?></th>
                    <td>
                        <div id="tooltip-preview" class="tooltip-preview">
                            <div class="explainer-tooltip explainer-tooltip-preview">
                                <div class="explainer-tooltip-header">
                                    <span class="explainer-tooltip-title" id="preview-tooltip-title"><?php echo esc_html__('Explanation', 'explainer-plugin'); ?></span>
                                    <button class="explainer-tooltip-close" type="button">×</button>
                                </div>
                                <div class="explainer-tooltip-content">
                                    <span id="preview-tooltip-content"><?php echo esc_html__('This is how your tooltip will look with the selected colors. It matches the actual frontend design with proper spacing and typography.', 'explainer-plugin'); ?></span>
                                </div>
                                <div class="explainer-tooltip-footer">
                                    <div class="explainer-disclaimer" id="preview-disclaimer"><?php echo esc_html__('AI-generated content may not always be accurate', 'explainer-plugin'); ?></div>
                                    <div class="explainer-provider" id="preview-provider"><?php echo esc_html__('Powered by OpenAI', 'explainer-plugin'); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="button-preview" class="button-preview" style="margin-top: 20px;">
                            <h4><?php echo esc_html__('Toggle Button Preview:', 'explainer-plugin'); ?></h4>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <button type="button" class="preview-explainer-button enabled" id="preview-button-enabled">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                    <?php echo esc_html__('Explainer', 'explainer-plugin'); ?>
                                </button>
                                <span><?php echo esc_html__('(Enabled)', 'explainer-plugin'); ?></span>
                                
                                <button type="button" class="preview-explainer-button disabled" id="preview-button-disabled">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                    <?php echo esc_html__('Explainer', 'explainer-plugin'); ?>
                                </button>
                                <span><?php echo esc_html__('(Disabled)', 'explainer-plugin'); ?></span>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Advanced Tab -->
        <div class="tab-content" id="advanced-tab" style="display: none;">
            <h2><?php echo esc_html__('Advanced Configuration', 'explainer-plugin'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Debug Mode', 'explainer-plugin'); ?></th>
                    <td>
                        <label for="explainer_debug_mode">
                            <input type="checkbox" name="explainer_debug_mode" id="explainer_debug_mode" value="1" <?php checked(get_option('explainer_debug_mode', false), true); ?> />
                            <?php echo esc_html__('Enable debug mode for troubleshooting', 'explainer-plugin'); ?>
                        </label>
                        <p class="description"><?php echo esc_html__('Enables detailed console logging and API prompt capture for debugging purposes. Only enable when troubleshooting issues.', 'explainer-plugin'); ?></p>
                        
                        <?php if (get_option('explainer_debug_mode', false)): ?>
                        <div class="debug-actions" style="margin-top: 15px; padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;">
                            <h4><?php echo esc_html__('Debug Tools', 'explainer-plugin'); ?></h4>
                            <p>
                                <button type="button" class="button button-secondary" id="view-debug-logs">
                                    <?php echo esc_html__('View Debug Logs', 'explainer-plugin'); ?>
                                </button>
                                <button type="button" class="button button-secondary" id="delete-debug-logs">
                                    <?php echo esc_html__('Delete All Logs', 'explainer-plugin'); ?>
                                </button>
                            </p>
                            <div id="debug-logs-viewer" style="display: none; margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 4px; max-height: 400px; overflow-y: auto;">
                                <p><?php echo esc_html__('No logs available.', 'explainer-plugin'); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                
            </table>
        </div>
        
        <!-- Help Tab -->
        <div class="tab-content" id="help-tab" style="display: none;">
            <h2><?php echo esc_html__('How to Use WP AI Explainer', 'explainer-plugin'); ?></h2>
            
            <div class="help-section">
                <h3><?php echo esc_html__('Quick Start Guide', 'explainer-plugin'); ?></h3>
                <div class="help-steps">
                    <div class="help-step">
                        <h4><?php echo esc_html__('1. Choose Your AI Provider', 'explainer-plugin'); ?></h4>
                        <p><?php echo esc_html__('Go to Basic Settings and select either OpenAI or Claude as your AI provider.', 'explainer-plugin'); ?></p>
                    </div>
                    
                    <div class="help-step">
                        <h4><?php echo esc_html__('2. Add Your API Key', 'explainer-plugin'); ?></h4>
                        <p><?php echo esc_html__('Enter your API key from your chosen provider. Your key is encrypted and secure.', 'explainer-plugin'); ?></p>
                        <ul>
                            <li><strong>OpenAI:</strong> Get your key from <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a></li>
                            <li><strong>Claude:</strong> Get your key from <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a></li>
                        </ul>
                    </div>
                    
                    <div class="help-step">
                        <h4><?php echo esc_html__('3. Select AI Model', 'explainer-plugin'); ?></h4>
                        <p><?php echo esc_html__('Choose the AI model that best fits your needs and budget.', 'explainer-plugin'); ?></p>
                        <ul>
                            <li><strong>OpenAI:</strong> GPT-3.5-turbo (faster, cheaper) or GPT-4 (more accurate)</li>
                            <li><strong>Claude:</strong> Claude-3-haiku (faster) or Claude-3-sonnet (more detailed)</li>
                        </ul>
                    </div>
                    
                    <div class="help-step">
                        <h4><?php echo esc_html__('4. Test and Save', 'explainer-plugin'); ?></h4>
                        <p><?php echo esc_html__('Use the "Test API Key" button to verify your setup, then save your settings.', 'explainer-plugin'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="help-section">
                <h3><?php echo esc_html__('How Users Get Explanations', 'explainer-plugin'); ?></h3>
                <div class="help-usage">
                    <ol>
                        <li><?php echo esc_html__('Users select any text on your website', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('A floating toggle button appears to enable explanations', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('When enabled, users can select text to get instant AI explanations', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Explanations appear in responsive tooltips that users can dismiss', 'explainer-plugin'); ?></li>
                    </ol>
                </div>
            </div>
            
            <div class="help-section">
                <h3><?php echo esc_html__('Customisation Options', 'explainer-plugin'); ?></h3>
                
                <div class="help-feature">
                    <h4><?php echo esc_html__('Appearance Tab', 'explainer-plugin'); ?></h4>
                    <ul>
                        <li><?php echo esc_html__('Customise tooltip colours (background, text, and footer text) to match your theme', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Position the toggle button where it works best', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Add disclaimers and provider attribution in tooltip footers', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Control footer text color independently for optimal visibility', 'explainer-plugin'); ?></li>
                    </ul>
                </div>
                
                <div class="help-feature">
                    <h4><?php echo esc_html__('Content Rules Tab', 'explainer-plugin'); ?></h4>
                    <ul>
                        <li><?php echo esc_html__('Control minimum and maximum text selection lengths', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Include/exclude specific page elements with CSS selectors', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Create custom AI prompts with {{snippet}} placeholders', 'explainer-plugin'); ?></li>
                    </ul>
                </div>
                
                <div class="help-feature">
                    <h4><?php echo esc_html__('Performance Tab', 'explainer-plugin'); ?></h4>
                    <ul>
                        <li><?php echo esc_html__('Enable caching to reduce API costs and improve speed', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Set rate limits to prevent abuse and control usage', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Configure different limits for logged-in vs anonymous users', 'explainer-plugin'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="help-section">
                <h3><?php echo esc_html__('Troubleshooting', 'explainer-plugin'); ?></h3>
                <div class="help-troubleshooting">
                    <div class="help-issue">
                        <h4><?php echo esc_html__('Explanations not working?', 'explainer-plugin'); ?></h4>
                        <ul>
                            <li><?php echo esc_html__('Check that the plugin is enabled in Basic Settings', 'explainer-plugin'); ?></li>
                            <li><?php echo esc_html__('Verify your API key is entered correctly', 'explainer-plugin'); ?></li>
                            <li><?php echo esc_html__('Test your API key using the "Test API Key" button', 'explainer-plugin'); ?></li>
                            <li><?php echo esc_html__('Enable Debug Mode in Advanced tab for detailed logs', 'explainer-plugin'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="help-issue">
                        <h4><?php echo esc_html__('High API costs?', 'explainer-plugin'); ?></h4>
                        <ul>
                            <li><?php echo esc_html__('Enable caching in Performance settings', 'explainer-plugin'); ?></li>
                            <li><?php echo esc_html__('Set appropriate rate limits for your users', 'explainer-plugin'); ?></li>
                            <li><?php echo esc_html__('Consider using GPT-3.5-turbo or Claude-haiku for lower costs', 'explainer-plugin'); ?></li>
                            <li><?php echo esc_html__('Adjust text length limits to control prompt size', 'explainer-plugin'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="help-issue">
                        <h4><?php echo esc_html__('Tooltip positioning issues?', 'explainer-plugin'); ?></h4>
                        <ul>
                            <li><?php echo esc_html__('The plugin automatically handles positioning and viewport boundaries', 'explainer-plugin'); ?></li>
                            <li><?php echo esc_html__('Check Content Rules for conflicting CSS selectors', 'explainer-plugin'); ?></li>
                            <li><?php echo esc_html__('Ensure your theme doesn\'t override plugin styles', 'explainer-plugin'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="help-section">
                <h3><?php echo esc_html__('Cost Management Tips', 'explainer-plugin'); ?></h3>
                <div class="help-costs">
                    <ul>
                        <li><?php echo esc_html__('Start with caching enabled and conservative rate limits', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Monitor your API usage in your provider\'s dashboard', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Set usage alerts in your API provider account', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Consider shorter custom prompts to reduce token usage', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Use appropriate models: faster models are typically cheaper', 'explainer-plugin'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Support Tab -->
        <div class="tab-content" id="support-tab" style="display: none;">
            <h2><?php echo esc_html__('Support & Contact', 'explainer-plugin'); ?></h2>
            
            <div class="support-section">
                <h3><?php echo esc_html__('Developer Information', 'explainer-plugin'); ?></h3>
                <div class="developer-info">
                    <p><strong><?php echo esc_html__('Developer:', 'explainer-plugin'); ?></strong> Billy Patel</p>
                    <p><strong><?php echo esc_html__('Email:', 'explainer-plugin'); ?></strong> <a href="mailto:billy@billymedia.co.uk">billy@billymedia.co.uk</a></p>
                    <p><strong><?php echo esc_html__('Website:', 'explainer-plugin'); ?></strong> <a href="https://billymedia.co.uk" target="_blank">billymedia.co.uk</a></p>
                </div>
            </div>
            
            <div class="support-section">
                <h3><?php echo esc_html__('Project Links', 'explainer-plugin'); ?></h3>
                <div class="project-links">
                    <p>
                        <strong><?php echo esc_html__('GitHub Repository:', 'explainer-plugin'); ?></strong><br>
                        <a href="https://github.com/billymedia/wp-explainer" target="_blank" class="button button-secondary">
                            <?php echo esc_html__('View on GitHub', 'explainer-plugin'); ?>
                        </a>
                    </p>
                    <p class="description">
                        <?php echo esc_html__('Visit our GitHub repository for documentation, source code, and contributing to the project.', 'explainer-plugin'); ?>
                    </p>
                </div>
            </div>
            
            <div class="support-section">
                <h3><?php echo esc_html__('Getting Help', 'explainer-plugin'); ?></h3>
                <div class="support-options">
                    <div class="support-option">
                        <h4><?php echo esc_html__('1. Check the Help Tab', 'explainer-plugin'); ?></h4>
                        <p><?php echo esc_html__('Start with the Help tab above for common setup and troubleshooting guides.', 'explainer-plugin'); ?></p>
                    </div>
                    
                    <div class="support-option">
                        <h4><?php echo esc_html__('2. Enable Debug Mode', 'explainer-plugin'); ?></h4>
                        <p><?php echo esc_html__('Turn on Debug Mode in Advanced settings to see detailed error logs.', 'explainer-plugin'); ?></p>
                    </div>
                    
                    <div class="support-option">
                        <h4><?php echo esc_html__('3. GitHub Issues', 'explainer-plugin'); ?></h4>
                        <p><?php echo esc_html__('Report bugs or request features at', 'explainer-plugin'); ?> <a href="https://github.com/billymedia/wp-explainer/issues" target="_blank">github.com/billymedia/wp-explainer/issues</a></p>
                    </div>
                    
                    <div class="support-option">
                        <h4><?php echo esc_html__('4. Custom Modifications', 'explainer-plugin'); ?></h4>
                        <p><?php echo esc_html__('For custom modifications or professional services, contact Billy directly at billy@billymedia.co.uk', 'explainer-plugin'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="support-section">
                <h3><?php echo esc_html__('When Requesting Support', 'explainer-plugin'); ?></h3>
                <div class="support-info-needed">
                    <p><?php echo esc_html__('Please include the following information when requesting support:', 'explainer-plugin'); ?></p>
                    <ul>
                        <li><?php echo esc_html__('WordPress version', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('PHP version', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Plugin version', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('AI provider being used (OpenAI/Claude)', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Browser and device information', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Detailed description of the issue', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Steps to reproduce the problem', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Any error messages or debug logs', 'explainer-plugin'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="support-section">
                <h3><?php echo esc_html__('Contributing', 'explainer-plugin'); ?></h3>
                <div class="contributing-info">
                    <p><?php echo esc_html__('We welcome contributions to make this plugin better:', 'explainer-plugin'); ?></p>
                    <ul>
                        <li><?php echo esc_html__('Report bugs and suggest features on GitHub', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Submit pull requests for improvements', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Help with translations and internationalization', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Share feedback and usage experiences', 'explainer-plugin'); ?></li>
                        <li><?php echo esc_html__('Test new features and provide feedback', 'explainer-plugin'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="support-section">
                <h3><?php echo esc_html__('System Information', 'explainer-plugin'); ?></h3>
                <div class="system-info">
                    <table class="form-table">
                        <tr>
                            <th><?php echo esc_html__('Plugin Version:', 'explainer-plugin'); ?></th>
                            <td><?php echo esc_html(EXPLAINER_PLUGIN_VERSION); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('WordPress Version:', 'explainer-plugin'); ?></th>
                            <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('PHP Version:', 'explainer-plugin'); ?></th>
                            <td><?php echo esc_html(PHP_VERSION); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('Current Theme:', 'explainer-plugin'); ?></th>
                            <td><?php echo esc_html(wp_get_theme()->get('Name')); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <?php submit_button(); ?>
    </form>
    
    <div class="explainer-admin-actions">
        <h2><?php echo esc_html__('Quick Actions', 'explainer-plugin'); ?></h2>
        <p>
            <button type="button" class="button button-primary" id="test-api-key">
                <?php echo esc_html__('Test API Key', 'explainer-plugin'); ?>
            </button>
            <button type="button" class="button button-secondary" id="clear-cache">
                <?php echo esc_html__('Clear Cache', 'explainer-plugin'); ?>
            </button>
            <button type="button" class="button button-secondary" id="reset-settings">
                <?php echo esc_html__('Reset to Defaults', 'explainer-plugin'); ?>
            </button>
        </p>
        <div id="admin-messages"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').hide();
        
        // Add active class to clicked tab
        $(this).addClass('nav-tab-active');
        
        // Show corresponding content
        const target = $(this).attr('href') + '-tab';
        $(target).show();
    });
    
    // API key visibility toggle for OpenAI
    $('#toggle-api-key-visibility').on('click', function() {
        const input = $('#explainer_api_key');
        const button = $(this);
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            button.text('<?php echo esc_js(__('Hide', 'explainer-plugin')); ?>');
        } else {
            input.attr('type', 'password');
            button.text('<?php echo esc_js(__('Show', 'explainer-plugin')); ?>');
        }
    });
    
    // API key visibility toggle for Claude
    $('#toggle-claude-key-visibility').on('click', function() {
        const input = $('#explainer_claude_api_key');
        const button = $(this);
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            button.text('<?php echo esc_js(__('Hide', 'explainer-plugin')); ?>');
        } else {
            input.attr('type', 'password');
            button.text('<?php echo esc_js(__('Show', 'explainer-plugin')); ?>');
        }
    });
    
    // Provider selection is now handled in admin.js file
    
    // Real-time tooltip preview
    function updateTooltipPreview() {
        const bgColor = $('#explainer_tooltip_bg_color').val();
        const textColor = $('#explainer_tooltip_text_color').val();
        
        // Detect site font from paragraph elements
        const siteFont = detectSiteFont();
        
        // Use CSS custom properties for dynamic updates (affects both background and arrow)
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
    
    $('#explainer_tooltip_bg_color, #explainer_tooltip_text_color').on('input', updateTooltipPreview);
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
    
    
});
</script>