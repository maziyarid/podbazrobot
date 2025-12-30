<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap pbr-wrap" dir="rtl">
    <h1 class="pbr-title">📝 ربات پادباز - تولید پست بلاگ</h1>
    <p class="pbr-subtitle">تولید مقالات HTML زیبا و رنگی برای وبلاگ</p>
    
    <div class="pbr-container">
        <div class="pbr-main-form">
            <form id="pbr-post-form">
                <?php wp_nonce_field('pbr_ajax_nonce', 'pbr_nonce'); ?>
                
                <div class="pbr-form-section">
                    <h3>📰 اطلاعات پست</h3>
                    
                    <div class="pbr-form-row">
                        <label for="post_topic">موضوع پست <span class="required">*</span></label>
                        <input type="text" id="post_topic" name="post_topic" 
                               placeholder="مثال: راهنمای انتخاب پاد مناسب برای مبتدیان" required>
                    </div>
                    
                    <div class="pbr-form-row">
                        <label for="post_keywords">کلیدواژه‌های هدف</label>
                        <textarea id="post_keywords" name="post_keywords" rows="3"
                                  placeholder="انتخاب پاد
بهترین پاد برای مبتدیان
راهنمای خرید ویپ"></textarea>
                    </div>
                    
                    <div class="pbr-form-row">
                        <label for="post_type">نوع پست</label>
                        <select id="post_type" name="post_type">
                            <option value="guide">📚 راهنما و آموزش</option>
                            <option value="review">⭐ بررسی محصول</option>
                            <option value="comparison">🔄 مقایسه محصولات</option>
                            <option value="news">📰 اخبار و تازه‌ها</option>
                            <option value="tips">💡 نکات و ترفندها</option>
                        </select>
                    </div>
                </div>
                
                <div class="pbr-form-section">
                    <h3>⚙️ تنظیمات</h3>
                    
                    <div class="pbr-form-row">
                        <label>روش تحقیق</label>
                        <div class="pbr-radio-group">
                            <label>
                                <input type="radio" name="post_research_method" value="auto" checked>
                                <span>🔍 تحقیق خودکار</span>
                            </label>
                            <label>
                                <input type="radio" name="post_research_method" value="manual">
                                <span>📋 ورود دستی</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="pbr-form-row pbr-post-manual-research" style="display:none;">
                        <label for="post_manual_research">داده‌های تحقیق</label>
                        <textarea id="post_manual_research" name="post_manual_research" rows="8"></textarea>
                    </div>
                    
                    <div class="pbr-form-row">
                        <label>وضعیت انتشار</label>
                        <div class="pbr-radio-group">
                            <label>
                                <input type="radio" name="post_publish_status" value="draft" checked>
                                <span>📝 پیش‌نویس</span>
                            </label>
                            <label>
                                <input type="radio" name="post_publish_status" value="publish">
                                <span>🚀 انتشار فوری</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="pbr-form-actions">
                    <button type="submit" id="pbr-generate-post-btn" class="button button-primary button-hero">
                        <span class="dashicons dashicons-edit"></span>
                        تولید محتوای HTML پست
                    </button>
                </div>
            </form>
        </div>
        
        <div class="pbr-sidebar">
            <div class="pbr-info-box">
                <h4>📋 خروجی پست شامل:</h4>
                <ul>
                    <li>✅ عنوان جذاب H1</li>
                    <li>✅ متادیتای سئو</li>
                    <li>✅ مقدمه رنگی</li>
                    <li>✅ بدنه HTML ۱۰۰۰+ کلمه</li>
                    <li>✅ باکس‌های اطلاعاتی</li>
                    <li>✅ جمع‌بندی</li>
                    <li>✅ دسته‌بندی و تگ</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Progress Modal -->
    <div id="pbr-post-progress-modal" class="pbr-modal" style="display:none;">
        <div class="pbr-modal-content">
            <h3>⏳ در حال تولید پست...</h3>
            <div class="pbr-progress-bar">
                <div class="pbr-progress-fill" style="width: 50%;"></div>
            </div>
            <p class="pbr-progress-message">لطفاً صبر کنید...</p>
        </div>
    </div>
    
    <!-- Result Modal -->
    <div id="pbr-post-result-modal" class="pbr-modal" style="display:none;">
        <div class="pbr-modal-content">
            <button class="pbr-modal-close">&times;</button>
            <div id="pbr-post-result-content"></div>
        </div>
    </div>
</div>
