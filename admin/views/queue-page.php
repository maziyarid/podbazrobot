<?php
if (!defined('ABSPATH')) exit;

$queue_manager = new PBR_Queue_Manager();
$stats = $queue_manager->get_stats();
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : null;
$items = $queue_manager->get_items($status_filter, 100);
?>
<div class="wrap pbr-wrap" dir="rtl">
    <h1 class="pbr-title">📋 مدیریت صف تولید محتوا</h1>
    
    <!-- Statistics Dashboard -->
    <div class="pbr-queue-stats">
        <div class="pbr-stat-card pbr-stat-total">
            <span class="pbr-stat-icon">📊</span>
            <div class="pbr-stat-content">
                <div class="pbr-stat-value"><?php echo $stats['total']; ?></div>
                <div class="pbr-stat-label">کل موارد</div>
            </div>
        </div>
        <div class="pbr-stat-card pbr-stat-pending">
            <span class="pbr-stat-icon">⏳</span>
            <div class="pbr-stat-content">
                <div class="pbr-stat-value"><?php echo $stats['pending']; ?></div>
                <div class="pbr-stat-label">در انتظار</div>
            </div>
        </div>
        <div class="pbr-stat-card pbr-stat-processing">
            <span class="pbr-stat-icon">🔄</span>
            <div class="pbr-stat-content">
                <div class="pbr-stat-value"><?php echo $stats['processing']; ?></div>
                <div class="pbr-stat-label">در حال پردازش</div>
            </div>
        </div>
        <div class="pbr-stat-card pbr-stat-completed">
            <span class="pbr-stat-icon">✅</span>
            <div class="pbr-stat-content">
                <div class="pbr-stat-value"><?php echo $stats['completed']; ?></div>
                <div class="pbr-stat-label">تکمیل شده</div>
            </div>
        </div>
        <div class="pbr-stat-card pbr-stat-failed">
            <span class="pbr-stat-icon">❌</span>
            <div class="pbr-stat-content">
                <div class="pbr-stat-value"><?php echo $stats['failed']; ?></div>
                <div class="pbr-stat-label">ناموفق</div>
            </div>
        </div>
    </div>
    
    <!-- Add Items Section -->
    <div class="pbr-queue-add-section">
        <h2>➕ افزودن به صف</h2>
        
        <div class="pbr-queue-tabs">
            <button class="pbr-queue-tab active" data-tab="single">📝 تک مورد</button>
            <button class="pbr-queue-tab" data-tab="bulk">📋 چند مورد</button>
            <button class="pbr-queue-tab" data-tab="csv">📄 بارگذاری CSV</button>
        </div>
        
        <!-- Single Item Form -->
        <div class="pbr-queue-tab-content active" id="single-tab">
            <form id="pbr-single-queue-form">
                <?php wp_nonce_field('pbr_ajax_nonce', 'pbr_nonce'); ?>
                <div class="pbr-form-row">
                    <label for="queue_title">عنوان/نام محصول *</label>
                    <input type="text" id="queue_title" name="title" required>
                </div>
                <div class="pbr-form-row">
                    <label for="queue_keywords">کلیدواژه‌ها</label>
                    <input type="text" id="queue_keywords" name="keywords" placeholder="جدا شده با ویرگول">
                </div>
                <div class="pbr-form-row">
                    <label for="queue_type">نوع محتوا</label>
                    <select id="queue_type" name="item_type">
                        <option value="product">محصول</option>
                        <option value="post">پست</option>
                    </select>
                </div>
                <div class="pbr-form-row">
                    <label for="queue_priority">اولویت</label>
                    <input type="number" id="queue_priority" name="priority" value="0" min="0" max="10">
                </div>
                <button type="submit" class="button button-primary">➕ افزودن به صف</button>
            </form>
        </div>
        
        <!-- Bulk Text Form -->
        <div class="pbr-queue-tab-content" id="bulk-tab">
            <form id="pbr-bulk-queue-form">
                <?php wp_nonce_field('pbr_ajax_nonce', 'pbr_nonce'); ?>
                <div class="pbr-form-row">
                    <label for="bulk_items">موارد (هر خط یک مورد)</label>
                    <textarea id="bulk_items" name="bulk_items" rows="10" 
                              placeholder="فرمت: عنوان | کلیدواژه1, کلیدواژه2&#10;مثال:&#10;پاد آواتار | پاد سیستم, آواتار&#10;ویپ وپو | دراگ ایکس, وپو"></textarea>
                    <span class="pbr-help">هر خط یک مورد - فرمت: عنوان | کلیدواژه‌ها</span>
                </div>
                <button type="submit" class="button button-primary">➕ افزودن دسته‌جمعی</button>
            </form>
        </div>
        
        <!-- CSV Upload Form -->
        <div class="pbr-queue-tab-content" id="csv-tab">
            <form id="pbr-csv-queue-form" enctype="multipart/form-data">
                <?php wp_nonce_field('pbr_ajax_nonce', 'pbr_nonce'); ?>
                <div class="pbr-form-row">
                    <label for="csv_file">فایل CSV</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv">
                    <span class="pbr-help">ستون‌ها: عنوان, کلیدواژه‌ها, نوع (product/post)</span>
                </div>
                <button type="submit" class="button button-primary">📤 بارگذاری CSV</button>
            </form>
        </div>
    </div>
    
    <!-- Queue Items Table -->
    <div class="pbr-queue-items-section">
        <div class="pbr-queue-header">
            <h2>📋 موارد صف</h2>
            <div class="pbr-queue-actions">
                <button id="pbr-process-queue" class="button button-primary">▶️ شروع پردازش</button>
                <button id="pbr-clear-completed" class="button">🗑️ پاک کردن تکمیل شده‌ها</button>
                <select id="pbr-status-filter">
                    <option value="">همه موارد</option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>>در انتظار</option>
                    <option value="processing" <?php selected($status_filter, 'processing'); ?>>در حال پردازش</option>
                    <option value="completed" <?php selected($status_filter, 'completed'); ?>>تکمیل شده</option>
                    <option value="failed" <?php selected($status_filter, 'failed'); ?>>ناموفق</option>
                </select>
            </div>
        </div>
        
        <table class="pbr-queue-table">
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>عنوان</th>
                    <th>نوع</th>
                    <th>وضعیت</th>
                    <th>اولویت</th>
                    <th>تاریخ ایجاد</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">موردی در صف وجود ندارد</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr data-item-id="<?php echo $item['id']; ?>">
                            <td><?php echo $item['id']; ?></td>
                            <td><?php echo esc_html($item['title']); ?></td>
                            <td>
                                <span class="pbr-type-badge pbr-type-<?php echo $item['item_type']; ?>">
                                    <?php echo $item['item_type'] === 'product' ? '📦 محصول' : '📝 پست'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="pbr-status-badge pbr-status-<?php echo $item['status']; ?>">
                                    <?php 
                                    $status_labels = [
                                        'pending' => '⏳ در انتظار',
                                        'processing' => '🔄 در حال پردازش',
                                        'completed' => '✅ تکمیل شده',
                                        'failed' => '❌ ناموفق'
                                    ];
                                    echo $status_labels[$item['status']] ?? $item['status'];
                                    ?>
                                </span>
                            </td>
                            <td><?php echo $item['priority']; ?></td>
                            <td><?php echo date_i18n('Y/m/d H:i', strtotime($item['created_at'])); ?></td>
                            <td class="pbr-queue-actions-cell">
                                <?php if ($item['status'] === 'failed'): ?>
                                    <button class="button button-small pbr-retry-item" data-id="<?php echo $item['id']; ?>">
                                        🔄 تلاش مجدد
                                    </button>
                                    <button class="button button-small pbr-show-error" data-id="<?php echo $item['id']; ?>" 
                                            data-error="<?php echo esc_attr($item['error_message']); ?>">
                                        ⚠️ خطا
                                    </button>
                                <?php endif; ?>
                                <?php if ($item['status'] === 'completed' && $item['result_id']): ?>
                                    <a href="<?php echo get_edit_post_link($item['result_id']); ?>" 
                                       class="button button-small" target="_blank">
                                        ✏️ مشاهده
                                    </a>
                                <?php endif; ?>
                                <button class="button button-small pbr-delete-item" data-id="<?php echo $item['id']; ?>">
                                    🗑️ حذف
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Processing Progress Modal -->
<div id="pbr-queue-progress-modal" class="pbr-modal" style="display:none;">
    <div class="pbr-modal-content">
        <h2>🔄 در حال پردازش صف</h2>
        <div class="pbr-progress-info">
            <p id="pbr-current-item">در حال آماده‌سازی...</p>
            <div class="pbr-progress-bar">
                <div class="pbr-progress-fill" style="width: 0%;"></div>
            </div>
            <p id="pbr-progress-stats">0 از 0 مورد پردازش شد</p>
        </div>
        <div id="pbr-process-results"></div>
        <button class="button pbr-modal-close">بستن</button>
    </div>
</div>

<!-- Error Display Modal -->
<div id="pbr-error-modal" class="pbr-modal" style="display:none;">
    <div class="pbr-modal-content">
        <h2>⚠️ پیام خطا</h2>
        <div id="pbr-error-content"></div>
        <button class="button pbr-modal-close">بستن</button>
    </div>
</div>
