<?php
if (!defined('ABSPATH')) exit;

$settings = [
    'blackbox_api_key' => get_option('pbr_blackbox_api_key', ''),
    'tavily_api_key' => get_option('pbr_tavily_api_key', ''),
    'claude_model' => get_option('pbr_claude_model', 'blackboxai/x-ai/grok-code-fast-1:free'),
    'auto_publish' => get_option('pbr_auto_publish', 'draft'),
    'enable_logging' => get_option('pbr_enable_logging', 'yes'),
    'enable_multi_agent' => get_option('pbr_enable_multi_agent', 'no'),
    'primary_color' => get_option('pbr_primary_color', '#29853a'),
    'use_theme_color' => get_option('pbr_use_theme_color', 'no'),
];
?>
<div class="wrap pbr-wrap" dir="rtl">
    <h1 class="pbr-title">⚙️ تنظیمات ربات پادباز</h1>
    
    <div class="pbr-settings-container">
        <form id="pbr-settings-form">
            <?php wp_nonce_field('pbr_ajax_nonce', 'pbr_nonce'); ?>
            
            <!-- API Settings -->
            <div class="pbr-settings-section">
                <h2>🔌 تنظیمات API</h2>
                
                <div class="pbr-form-row">
                    <label for="blackbox_api_key">کلید API بلک‌باکس</label>
                    <div class="pbr-input-group">
                        <input type="password" 
                               id="blackbox_api_key" 
                               name="blackbox_api_key" 
                               value="<?php echo esc_attr($settings['blackbox_api_key']); ?>"
                               placeholder="کلید API">
                        <button type="button" class="button pbr-toggle-password">👁️</button>
                        <button type="button" class="button pbr-test-api" data-api="blackbox">تست</button>
                    </div>
                    <span class="pbr-help">
                        از <a href="https://www.blackbox.ai/api" target="_blank">blackbox.ai</a> دریافت کنید
                    </span>
                    <div class="pbr-api-status" id="blackbox-status"></div>
                </div>
                
                <div class="pbr-form-row">
                    <label for="tavily_api_key">کلید API تاویلی</label>
                    <div class="pbr-input-group">
                        <input type="password" 
                               id="tavily_api_key" 
                               name="tavily_api_key" 
                               value="<?php echo esc_attr($settings['tavily_api_key']); ?>"
                               placeholder="کلید API">
                        <button type="button" class="button pbr-toggle-password">👁️</button>
                        <button type="button" class="button pbr-test-api" data-api="tavily">تست</button>
                    </div>
                    <span class="pbr-help">
                        از <a href="https://tavily.com" target="_blank">tavily.com</a> دریافت کنید
                    </span>
                    <div class="pbr-api-status" id="tavily-status"></div>
                </div>
            </div>
            
            <!-- Model Settings -->
            <div class="pbr-settings-section">
                <h2>🤖 تنظیمات مدل</h2>
                
                <div class="pbr-form-row">
                    <label for="claude_model">مدل هوش مصنوعی</label>
                    <select id="claude_model" name="claude_model">
                        <optgroup label="🆓 مدل‌های رایگان (پیشنهادی)">
                            <option value="blackboxai/x-ai/grok-code-fast-1:free" <?php selected($settings['claude_model'], 'blackboxai/x-ai/grok-code-fast-1:free'); ?>>
                                🆓 xAI Grok Code Fast 1 (پیشنهادی)
                            </option>
                            <option value="blackboxai/agentica-org/deepcoder-14b-preview:free" <?php selected($settings['claude_model'], 'blackboxai/agentica-org/deepcoder-14b-preview:free'); ?>>
                                🆓 Agentica Deepcoder 14B
                            </option>
                        </optgroup>
                        
                        <optgroup label="🔝 مدل‌های حرفه‌ای">
                            <option value="blackboxai/anthropic/claude-opus-4" <?php selected($settings['claude_model'], 'blackboxai/anthropic/claude-opus-4'); ?>>
                                Claude Opus 4 (بهترین کیفیت)
                            </option>
                            <option value="blackboxai/anthropic/claude-sonnet-4" <?php selected($settings['claude_model'], 'blackboxai/anthropic/claude-sonnet-4'); ?>>
                                Claude Sonnet 4 (تعادل عالی)
                            </option>
                            <option value="blackboxai/anthropic/claude-3-5-sonnet" <?php selected($settings['claude_model'], 'blackboxai/anthropic/claude-3-5-sonnet'); ?>>
                                Claude 3.5 Sonnet
                            </option>
                            <option value="blackboxai/openai/gpt-4o" <?php selected($settings['claude_model'], 'blackboxai/openai/gpt-4o'); ?>>
                                ChatGPT-4o (قدرتمند)
                            </option>
                            <option value="blackboxai/openai/gpt-4-turbo" <?php selected($settings['claude_model'], 'blackboxai/openai/gpt-4-turbo'); ?>>
                                ChatGPT-4 Turbo
                            </option>
                            <option value="blackboxai/google/gemini-pro-1.5" <?php selected($settings['claude_model'], 'blackboxai/google/gemini-pro-1.5'); ?>>
                                Gemini Pro 1.5
                            </option>
                            <option value="blackboxai/google/gemini-ultra" <?php selected($settings['claude_model'], 'blackboxai/google/gemini-ultra'); ?>>
                                Gemini Ultra
                            </option>
                        </optgroup>
                        
                        <optgroup label="💰 مدل‌های اقتصادی">
                            <option value="blackboxai/amazon/nova-micro-v1" <?php selected($settings['claude_model'], 'blackboxai/amazon/nova-micro-v1'); ?>>
                                Amazon Nova Micro
                            </option>
                            <option value="blackboxai/amazon/nova-lite-v1" <?php selected($settings['claude_model'], 'blackboxai/amazon/nova-lite-v1'); ?>>
                                Amazon Nova Lite
                            </option>
                            <option value="blackboxai/openai/gpt-4o-mini" <?php selected($settings['claude_model'], 'blackboxai/openai/gpt-4o-mini'); ?>>
                                ChatGPT-4o Mini
                            </option>
                            <option value="blackboxai/ai21/jamba-1.6-mini" <?php selected($settings['claude_model'], 'blackboxai/ai21/jamba-1.6-mini'); ?>>
                                AI21 Jamba Mini
                            </option>
                            <option value="blackboxai/anthropic/claude-3-haiku" <?php selected($settings['claude_model'], 'blackboxai/anthropic/claude-3-haiku'); ?>>
                                Claude 3 Haiku (سریع)
                            </option>
                            <option value="blackboxai/google/gemini-flash-1.5" <?php selected($settings['claude_model'], 'blackboxai/google/gemini-flash-1.5'); ?>>
                                Gemini Flash 1.5
                            </option>
                        </optgroup>
                        
                        <optgroup label="⚙️ مدل‌های تخصصی">
                            <option value="blackboxai/aion-labs/aion-1.0-mini" <?php selected($settings['claude_model'], 'blackboxai/aion-labs/aion-1.0-mini'); ?>>
                                AionLabs Aion Mini
                            </option>
                            <option value="blackboxai/amazon/nova-pro-v1" <?php selected($settings['claude_model'], 'blackboxai/amazon/nova-pro-v1'); ?>>
                                Amazon Nova Pro
                            </option>
                            <option value="blackboxai/ai21/jamba-1.6-large" <?php selected($settings['claude_model'], 'blackboxai/ai21/jamba-1.6-large'); ?>>
                                AI21 Jamba Large
                            </option>
                            <option value="blackboxai/01-ai/yi-large" <?php selected($settings['claude_model'], 'blackboxai/01-ai/yi-large'); ?>>
                                01.AI Yi Large
                            </option>
                            <option value="blackboxai/aion-labs/aion-1.0" <?php selected($settings['claude_model'], 'blackboxai/aion-labs/aion-1.0'); ?>>
                                AionLabs Aion 1.0
                            </option>
                        </optgroup>
                        
                        <optgroup label="🤝 عوامل پس‌زمینه (رایگان)">
                            <option value="BLACKBOX" <?php selected($settings['claude_model'], 'BLACKBOX'); ?>>
                                BLACKBOX Agent
                            </option>
                            <option value="Claude Code" <?php selected($settings['claude_model'], 'Claude Code'); ?>>
                                Claude Code Agent
                            </option>
                            <option value="Codex" <?php selected($settings['claude_model'], 'Codex'); ?>>
                                Codex Agent
                            </option>
                            <option value="Gemini" <?php selected($settings['claude_model'], 'Gemini'); ?>>
                                Gemini Agent
                            </option>
                        </optgroup>
                    </select>
                    <span class="pbr-help">
                        🆓 = رایگان | 🔝 = حرفه‌ای | 💰 = اقتصادی | ⚙️ = تخصصی
                    </span>
                </div>
                
                <div class="pbr-form-row">
                    <label>
                        <input type="checkbox" 
                               name="enable_multi_agent" 
                               value="yes" 
                               <?php checked($settings['enable_multi_agent'], 'yes'); ?>>
                        فعالسازی سیستم چند-عامله (Multi-Agent Orchestration)
                    </label>
                    <span class="pbr-help">
                        با فعالسازی این گزینه، سیستم از چندین عامل هوش مصنوعی استفاده می‌کند
                    </span>
                </div>
            </div>
            
            <!-- Design Settings -->
            <div class="pbr-settings-section">
                <h2>🎨 تنظیمات طراحی</h2>
                
                <div class="pbr-form-row">
                    <label for="primary_color">رنگ اصلی سایت</label>
                    <div class="pbr-input-group">
                        <input type="color" 
                               id="primary_color" 
                               name="primary_color" 
                               value="<?php echo esc_attr($settings['primary_color']); ?>">
                        <input type="text" 
                               id="primary_color_hex" 
                               value="<?php echo esc_attr($settings['primary_color']); ?>"
                               pattern="^#[0-9A-Fa-f]{6}$"
                               placeholder="#29853a">
                    </div>
                    <span class="pbr-help">
                        این رنگ در محتوای تولید شده استفاده می‌شود
                    </span>
                </div>
                
                <div class="pbr-form-row">
                    <label>
                        <input type="checkbox" 
                               name="use_theme_color" 
                               value="yes" 
                               <?php checked($settings['use_theme_color'], 'yes'); ?>>
                        استفاده خودکار از رنگ اصلی قالب
                    </label>
                    <span class="pbr-help">
                        رنگ اصلی به صورت خودکار از تنظیمات قالب فعلی دریافت می‌شود
                    </span>
                </div>
            </div>
            
            <!-- Content Settings -->
            <div class="pbr-settings-section">
                <h2>📝 تنظیمات محتوا</h2>
                
                <div class="pbr-form-row">
                    <label for="auto_publish">وضعیت پیش‌فرض انتشار</label>
                    <select id="auto_publish" name="auto_publish">
                        <option value="draft" <?php selected($settings['auto_publish'], 'draft'); ?>>پیش‌نویس</option>
                        <option value="publish" <?php selected($settings['auto_publish'], 'publish'); ?>>انتشار فوری</option>
                        <option value="pending" <?php selected($settings['auto_publish'], 'pending'); ?>>در انتظار بررسی</option>
                    </select>
                </div>
                
                <div class="pbr-form-row">
                    <label>
                        <input type="checkbox" 
                               name="enable_logging" 
                               value="yes" 
                               <?php checked($settings['enable_logging'], 'yes'); ?>>
                        فعال‌سازی ثبت گزارش عملیات
                    </label>
                </div>
            </div>
            
            <div class="pbr-form-actions">
                <button type="submit" id="pbr-save-settings-btn" class="button button-primary button-hero">
                    💾 ذخیره تنظیمات
                </button>
            </div>
        </form>
    </div>
</div>
