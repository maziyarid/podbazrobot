<?php
if (!defined('ABSPATH')) exit;

// Get existing products and posts
$products = [];
$posts_list = [];

if (class_exists('WooCommerce')) {
    $products = get_posts([
        'post_type' => 'product',
        'posts_per_page' => 100,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
}

$posts_list = get_posts([
    'post_type' => 'post',
    'posts_per_page' => 100,
    'orderby' => 'date',
    'order' => 'DESC'
]);
?>
<div class="wrap pbr-wrap" dir="rtl">
    <h1 class="pbr-title">🔄 به‌روزرسانی محتوای موجود</h1>
    <p class="pbr-subtitle">به‌روزرسانی و بهبود محصولات و پست‌های موجود</p>
    
    <div class="pbr-container">
        <div class="pbr-main-form">
            <form id="pbr-update-form">
                <?php wp_nonce_field('pbr_ajax_nonce', 'pbr_nonce'); ?>
                
                <div class="pbr-form-section">
                    <h3>📝 انتخاب محتوا</h3>
                    
                    <div class="pbr-form-row">
                        <label>نوع محتوا</label>
                        <div class="pbr-radio-group">
                            <label>
                                <input type="radio" name="update_type" value="product" checked>
                                <span>📦 محصول</span>
                            </label>
                            <label>
                                <input type="radio" name="update_type" value="post">
                                <span>📝 پست</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="pbr-form-row" id="product-select-row">
                        <label for="product_id">انتخاب محصول</label>
                        <select id="product_id" name="product_id">
                            <option value="">-- انتخاب کنید --</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo esc_attr($product->ID); ?>">
                                    <?php echo esc_html($product->post_title); ?> (ID: <?php echo $product->ID; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="pbr-form-row" id="post-select-row" style="display:none;">
                        <label for="post_id">انتخاب پست</label>
                        <select id="post_id" name="post_id">
                            <option value="">-- انتخاب کنید --</option>
                            <?php foreach ($posts_list as $post): ?>
                                <option value="<?php echo esc_attr($post->ID); ?>">
                                    <?php echo esc_html($post->post_title); ?> (ID: <?php echo $post->ID; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="pbr-form-section">
                    <h3>🔧 دستورالعمل به‌روزرسانی</h3>
                    
                    <div class="pbr-form-row">
                        <label for="update_instructions">چه بخش‌هایی به‌روزرسانی شوند؟</label>
                        <textarea id="update_instructions" name="update_instructions" rows="4"
                                  placeholder="مثال:
- به‌روزرسانی مشخصات فنی
- بهبود رنگ‌بندی HTML
- اضافه کردن بخش جدید
- اصلاح اطلاعات برند"></textarea>
                    </div>
                    
                    <div class="pbr-form-row">
                        <label>
                            <input type="checkbox" name="refresh_research" value="yes">
                            تحقیق مجدد از منابع جدید
                        </label>
                    </div>
                </div>
                
                <div class="pbr-form-actions">
                    <button type="button" id="pbr-load-content-btn" class="button button-secondary">
                        📄 نمایش محتوای فعلی
                    </button>
                    <button type="submit" id="pbr-update-btn" class="button button-primary button-hero">
                        🔄 به‌روزرسانی محتوا
                    </button>
                </div>
            </form>
            
            <div id="pbr-current-content" class="pbr-current-content" style="display:none;">
                <h4>📄 پیش‌نمایش محتوای فعلی:</h4>
                <div id="pbr-current-content-display" class="pbr-preview-frame"></div>
            </div>
        </div>
    </div>
    
    <!-- Progress Modal -->
    <div id="pbr-update-progress-modal" class="pbr-modal" style="display:none;">
        <div class="pbr-modal-content">
            <h3>⏳ در حال به‌روزرسانی...</h3>
            <div class="pbr-progress-bar">
                <div class="pbr-progress-fill" style="width: 50%;"></div>
            </div>
        </div>
    </div>
    
    <!-- Result Modal -->
    <div id="pbr-update-result-modal" class="pbr-modal" style="display:none;">
        <div class="pbr-modal-content">
            <button class="pbr-modal-close">&times;</button>
            <div id="pbr-update-result-content"></div>
        </div>
    </div>
</div>
