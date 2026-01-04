<?php
if (!defined('ABSPATH')) exit;

$queue_handler = new PBR_Queue_Handler();
$stats = $queue_handler->get_stats();
?>
<div class="wrap pbr-wrap" dir="rtl">
    <h1 class="pbr-title">📦 تولید دسته‌جمعی محتوا</h1>
    
    <div class="pbr-bulk-container">
        
        <!-- Add to Queue Form -->
        <div class="pbr-card">
            <h2>➕ افزودن به صف</h2>
            
            <form id="pbr-bulk-add-form">
                <?php wp_nonce_field('pbr_ajax_nonce', 'pbr_nonce'); ?>
                
                <div class="pbr-form-row">
                    <label for="bulk_type">نوع محتوا</label>
                    <select id="bulk_type" name="bulk_type">
                        <option value="product">محصول</option>
                        <option value="post">پست بلاگ</option>
                    </select>
                </div>
                
                <div class="pbr-form-row">
                    <label for="bulk_items">لیست آیتم‌ها (هر خط یک آیتم)</label>
                    <textarea id="bulk_items" 
                              name="bulk_items" 
                              rows="10" 
                              placeholder="نام محصول 1&#10;نام محصول 2&#10;نام محصول 3"
                              required></textarea>
                    <span class="pbr-help">
                        هر خط یک آیتم - می‌توانید نام محصولات یا موضوعات پست را وارد کنید
                    </span>
                </div>
                
                <div class="pbr-form-row">
                    <label for="bulk_keywords">کلمات کلیدی مشترک (اختیاری)</label>
                    <input type="text" 
                           id="bulk_keywords" 
                           name="bulk_keywords" 
                           placeholder="کلمات کلیدی مشترک برای همه آیتم‌ها">
                </div>
                
                <div class="pbr-form-actions">
                    <button type="submit" id="pbr-bulk-add-btn" class="button button-primary button-hero">
                        ➕ افزودن به صف
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Queue Stats -->
        <div class="pbr-card pbr-stats-card">
            <h2>📊 وضعیت صف</h2>
            <div class="pbr-stats-grid">
                <div class="pbr-stat-item">
                    <span class="pbr-stat-label">کل آیتم‌ها</span>
                    <span class="pbr-stat-value" id="stat-total"><?php echo $stats['total']; ?></span>
                </div>
                <div class="pbr-stat-item">
                    <span class="pbr-stat-label">در انتظار</span>
                    <span class="pbr-stat-value pbr-stat-pending" id="stat-pending"><?php echo $stats['pending']; ?></span>
                </div>
                <div class="pbr-stat-item">
                    <span class="pbr-stat-label">در حال پردازش</span>
                    <span class="pbr-stat-value pbr-stat-processing" id="stat-processing"><?php echo $stats['processing']; ?></span>
                </div>
                <div class="pbr-stat-item">
                    <span class="pbr-stat-label">موفق</span>
                    <span class="pbr-stat-value pbr-stat-completed" id="stat-completed"><?php echo $stats['completed']; ?></span>
                </div>
                <div class="pbr-stat-item">
                    <span class="pbr-stat-label">ناموفق</span>
                    <span class="pbr-stat-value pbr-stat-failed" id="stat-failed"><?php echo $stats['failed']; ?></span>
                </div>
            </div>
            
            <div class="pbr-queue-actions">
                <button type="button" id="pbr-process-queue-btn" class="button button-primary">
                    ▶️ شروع پردازش صف
                </button>
                <button type="button" id="pbr-stop-queue-btn" class="button" style="display: none;">
                    ⏸️ توقف پردازش
                </button>
                <button type="button" id="pbr-refresh-queue-btn" class="button">
                    🔄 بروزرسانی
                </button>
                <button type="button" id="pbr-clear-completed-btn" class="button">
                    🗑️ پاک کردن موفق‌ها
                </button>
            </div>
        </div>
        
        <!-- Queue Items -->
        <div class="pbr-card">
            <h2>📋 آیتم‌های صف</h2>
            
            <div class="pbr-queue-filters">
                <select id="pbr-queue-filter">
                    <option value="all">همه</option>
                    <option value="pending">در انتظار</option>
                    <option value="processing">در حال پردازش</option>
                    <option value="completed">موفق</option>
                    <option value="failed">ناموفق</option>
                </select>
            </div>
            
            <div id="pbr-queue-table-container">
                <table class="wp-list-table widefat fixed striped pbr-queue-table">
                    <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>نام آیتم</th>
                            <th>نوع</th>
                            <th>وضعیت</th>
                            <th>تاریخ ایجاد</th>
                            <th>تاریخ پردازش</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody id="pbr-queue-items">
                        <!-- Items loaded via AJAX -->
                        <tr>
                            <td colspan="7" style="text-align: center;">
                                در حال بارگذاری...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Processing Progress -->
        <div id="pbr-processing-progress" class="pbr-card" style="display: none;">
            <h2>⚙️ در حال پردازش...</h2>
            <div class="pbr-progress-info">
                <p><strong>آیتم در حال پردازش:</strong> <span id="processing-item-name">-</span></p>
                <p><strong>وضعیت:</strong> <span id="processing-status">-</span></p>
            </div>
            <div class="pbr-progress-bar">
                <div id="pbr-progress-fill" class="pbr-progress-fill" style="width: 0%;"></div>
            </div>
            <p class="pbr-progress-text">
                <span id="processed-count">0</span> از <span id="total-count">0</span> آیتم پردازش شده
            </p>
        </div>
        
    </div>
</div>
