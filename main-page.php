<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap pbr-wrap" dir="rtl">
    <h1 class="pbr-title">🎨 ربات پادباز - تولید محصول جدید</h1>
    <p class="pbr-subtitle">تولید محتوای HTML رنگی و زیبا برای ویرایشگر کلاسیک وردپرس</p>
    
    <div class="pbr-container">
        <div class="pbr-main-form">
            <form id="pbr-product-form">
                <?php wp_nonce_field('pbr_ajax_nonce', 'pbr_nonce'); ?>
                
                <div class="pbr-form-section">
                    <h3>📦 اطلاعات محصول</h3>
                    
                    <div class="pbr-form-row">
                        <label for="product_name">نام محصول <span class="required">*</span></label>
                        <input type="text" id="product_name" name="product_name" 
                               placeholder="مثال: VAPORESSO XROS 4" required>
                        <span class="pbr-help">نام کامل محصول به انگلیسی یا فارسی</span>
                    </div>
                    
                    <div class="pbr-form-row">
                        <label for="keywords">کلیدواژه‌های هدف</label>
                        <textarea id="keywords" name="keywords" rows="4"
                                  placeholder="پاد واپرسو ایکسروس
vaporesso xros 4
پادسیستم کم‌حجم"></textarea>
                        <span class="pbr-help">هر کلیدواژه در یک خط</span>
                    </div>
                </div>
                
                <div class="pbr-form-section">
                    <h3>⚙️ تنظیمات تولید</h3>
                    
                    <div class="pbr-form-row">
                        <label>روش تحقیق</label>
                        <div class="pbr-radio-group">
                            <label>
                                <input type="radio" name="research_method" value="auto" checked>
                                <span>🔍 تحقیق خودکار (Tavily)</span>
                            </label>
                            <label>
                                <input type="radio" name="research_method" value="manual">
                                <span>📋 ورود دستی داده‌ها</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="pbr-form-row pbr-manual-research" style="display:none;">
                        <label for="manual_research">داده‌های تحقیق</label>
                        <textarea id="manual_research" name="manual_research" rows="10"
                                  placeholder="اطلاعات محصول را اینجا وارد کنید..."></textarea>
                    </div>
                    
                    <div class="pbr-form-row">
                        <label>وضعیت انتشار</label>
                        <div class="pbr-radio-group">
                            <label>
                                <input type="radio" name="publish_status" value="draft" checked>
                                <span>📝 پیش‌نویس</span>
                            </label>
                            <label>
                                <input type="radio" name="publish_status" value="publish">
                                <span>🚀 انتشار فوری</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="pbr-form-actions">
                    <button type="submit" id="pbr-generate-btn" class="button button-primary button-hero">
                        <span class="dashicons dashicons-art"></span>
                        تولید محتوای HTML محصول
                    </button>
                </div>
            </form>
        </div>
        
        <div class="pbr-sidebar">
            <div class="pbr-info-box">
                <h4>📋 خروجی شامل:</h4>
                <ul>
                    <li>✅ جدول متادیتای سئو</li>
                    <li>✅ توضیح کوتاه محصول</li>
                    <li>✅ کد HTML رنگی و زیبا</li>
                    <li>✅ جدول اطلاعات کلی</li>
                    <li>✅ جدول مشخصات فنی</li>
                    <li>✅ جدول کویل‌های سازگار</li>
                    <li>✅ باکس‌های اطلاعاتی رنگی</li>
                    <li>✅ داستان برند</li>
                    <li>✅ Alt Text تصاویر</li>
                    <li>✅ فیلدهای سفارشی JSON</li>
                </ul>
            </div>
            
            <div class="pbr-color-palette">
                <h4>🎨 پالت رنگی خروجی:</h4>
                <div class="pbr-colors">
                    <span style="background:#3F51B5;" title="اطلاعات کلی"></span>
                    <span style="background:#009688;" title="مشخصات فنی"></span>
                    <span style="background:#FF7043;" title="کویل"></span>
                    <span style="background:#2196F3;" title="ابعاد"></span>
                    <span style="background:#FBC02D;" title="مواد"></span>
                    <span style="background:#9C27B0;" title="جریان هوا"></span>
                    <span style="background:#673AB7;" title="مودها"></span>
                    <span style="background:#00BCD4;" title="فناوری"></span>
                    <span style="background:#E91E63;" title="استفاده"></span>
                    <span style="background:#FF9800;" title="ایمنی"></span>
                    <span style="background:#8BC34A;" title="حفاظت"></span>
                </div>
            </div>
            
            <div class="pbr-api-status">
                <h4>🔌 وضعیت API</h4>
                <div id="pbr-api-status-content">
                    <p>در حال بررسی...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Progress Modal -->
    <div id="pbr-progress-modal" class="pbr-modal" style="display:none;">
        <div class="pbr-modal-content">
            <h3>⏳ در حال تولید محتوای HTML...</h3>
            <div class="pbr-progress-steps">
                <div class="pbr-step" data-step="research">
                    <span class="pbr-step-icon">⏳</span>
                    <span class="pbr-step-text">مرحله ۱: تحقیق محصول</span>
                </div>
                <div class="pbr-step" data-step="content">
                    <span class="pbr-step-icon">⏳</span>
                    <span class="pbr-step-text">مرحله ۲: تولید کد HTML رنگی</span>
                </div>
                <div class="pbr-step" data-step="publish">
                    <span class="pbr-step-icon">⏳</span>
                    <span class="pbr-step-text">مرحله ۳: ایجاد محصول در ووکامرس</span>
                </div>
            </div>
            <div class="pbr-progress-bar">
                <div class="pbr-progress-fill"></div>
            </div>
            <p class="pbr-progress-message">لطفاً صبر کنید. این فرآیند ممکن است ۲-۳ دقیقه طول بکشد.</p>
        </div>
    </div>
    
    <!-- Result Modal -->
    <div id="pbr-result-modal" class="pbr-modal" style="display:none;">
        <div class="pbr-modal-content pbr-result-modal-content">
            <button class="pbr-modal-close">&times;</button>
            <div id="pbr-result-content"></div>
        </div>
    </div>
    
    <!-- HTML Preview Modal -->
    <div id="pbr-preview-modal" class="pbr-modal" style="display:none;">
        <div class="pbr-modal-content pbr-preview-modal-content">
            <button class="pbr-modal-close">&times;</button>
            <h3>📄 پیش‌نمایش HTML</h3>
            <div id="pbr-preview-content" class="pbr-preview-frame"></div>
        </div>
    </div>
</div>