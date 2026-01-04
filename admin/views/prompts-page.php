<?php
if (!defined('ABSPATH')) exit;

$prompts = [
    'research' => [
        'label' => '🔍 پرامپت تحقیق (Tavily)',
        'description' => 'برای جمع‌آوری اطلاعات محصول',
        'value' => get_option('pbr_research_prompt', '')
    ],
    'content' => [
        'label' => '🎨 پرامپت HTML محصول',
        'description' => 'برای تولید کد HTML رنگی محصول',
        'value' => get_option('pbr_content_prompt', '')
    ],
    'post' => [
        'label' => '📝 پرامپت HTML پست',
        'description' => 'برای تولید پست‌های بلاگ',
        'value' => get_option('pbr_post_prompt', '')
    ],
    'update' => [
        'label' => '🔄 پرامپت به‌روزرسانی',
        'description' => 'برای به‌روزرسانی محتوای موجود',
        'value' => get_option('pbr_update_prompt', '')
    ]
];
?>
<div class="wrap pbr-wrap" dir="rtl">
    <h1 class="pbr-title">📋 مدیریت پرامپت‌ها</h1>
    <p class="pbr-subtitle">ویرایش پرامپت‌های تولید محتوای HTML</p>
    
    <div class="pbr-prompts-container">
        <form id="pbr-prompts-form">
            <?php wp_nonce_field('pbr_ajax_nonce', 'pbr_nonce'); ?>
            
            <div class="pbr-prompts-tabs">
                <button type="button" class="pbr-tab-btn active" data-tab="research">🔍 تحقیق</button>
                <button type="button" class="pbr-tab-btn" data-tab="content">🎨 محصول</button>
                <button type="button" class="pbr-tab-btn" data-tab="post">📝 پست</button>
                <button type="button" class="pbr-tab-btn" data-tab="update">🔄 به‌روزرسانی</button>
            </div>
            
            <?php foreach ($prompts as $key => $prompt): ?>
            <div class="pbr-prompt-tab <?php echo $key === 'research' ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($key); ?>">
                <div class="pbr-prompt-header">
                    <h3><?php echo esc_html($prompt['label']); ?></h3>
                    <p><?php echo esc_html($prompt['description']); ?></p>
                </div>
                
                <div class="pbr-form-row">
                    <textarea id="prompt_<?php echo esc_attr($key); ?>" 
                              name="prompt_<?php echo esc_attr($key); ?>" 
                              rows="25" 
                              class="pbr-prompt-editor"
                              dir="auto"><?php echo esc_textarea($prompt['value']); ?></textarea>
                </div>
                
                <div class="pbr-prompt-actions">
                    <button type="button" class="button pbr-reset-prompt" data-prompt="<?php echo esc_attr($key); ?>">
                        🔄 بازگردانی به پیش‌فرض
                    </button>
                    <span class="pbr-char-count">
                        تعداد کاراکتر: <span id="count_<?php echo esc_attr($key); ?>">0</span>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="pbr-form-actions">
                <button type="submit" id="pbr-save-prompts-btn" class="button button-primary button-hero">
                    💾 ذخیره همه پرامپت‌ها
                </button>
            </div>
        </form>
    </div>
</div>
