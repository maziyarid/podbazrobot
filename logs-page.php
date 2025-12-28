<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'pbr_logs';

// Pagination
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

$total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$total_pages = ceil($total_items / $per_page);

$logs = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
    $per_page,
    $offset
));

$stats = [
    'total' => $total_items,
    'products' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE action_type LIKE '%product%'"),
    'posts' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE action_type LIKE '%post%'"),
    'success' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'success'"),
    'failed' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'failed'"),
];
?>
<div class="wrap pbr-wrap" dir="rtl">
    <h1 class="pbr-title">📊 گزارش‌های ربات پادباز</h1>
    
    <!-- Statistics -->
    <div class="pbr-stats-grid">
        <div class="pbr-stat-card">
            <span class="pbr-stat-icon">📊</span>
            <div class="pbr-stat-content">
                <span class="pbr-stat-number"><?php echo number_format_i18n($stats['total']); ?></span>
                <span class="pbr-stat-label">کل عملیات</span>
            </div>
        </div>
        <div class="pbr-stat-card">
            <span class="pbr-stat-icon">📦</span>
            <div class="pbr-stat-content">
                <span class="pbr-stat-number"><?php echo number_format_i18n($stats['products']); ?></span>
                <span class="pbr-stat-label">محصولات</span>
            </div>
        </div>
        <div class="pbr-stat-card">
            <span class="pbr-stat-icon">📝</span>
            <div class="pbr-stat-content">
                <span class="pbr-stat-number"><?php echo number_format_i18n($stats['posts']); ?></span>
                <span class="pbr-stat-label">پست‌ها</span>
            </div>
        </div>
        <div class="pbr-stat-card pbr-stat-success">
            <span class="pbr-stat-icon">✅</span>
            <div class="pbr-stat-content">
                <span class="pbr-stat-number"><?php echo number_format_i18n($stats['success']); ?></span>
                <span class="pbr-stat-label">موفق</span>
            </div>
        </div>
        <div class="pbr-stat-card pbr-stat-failed">
            <span class="pbr-stat-icon">❌</span>
            <div class="pbr-stat-content">
                <span class="pbr-stat-number"><?php echo number_format_i18n($stats['failed']); ?></span>
                <span class="pbr-stat-label">ناموفق</span>
            </div>
        </div>
    </div>
    
    <!-- Logs Table -->
    <div class="pbr-logs-container">
        <div class="pbr-logs-header">
            <h2>📋 لیست عملیات</h2>
            <div class="pbr-logs-actions">
                <button type="button" id="pbr-clear-logs" class="button">🗑️ پاک کردن همه</button>
                <button type="button" id="pbr-export-logs" class="button">📥 خروجی CSV</button>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>نوع عملیات</th>
                    <th>نام</th>
                    <th>وضعیت</th>
                    <th>پیام</th>
                    <th>تاریخ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:#666;">
                        هنوز هیچ گزارشی ثبت نشده است.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo esc_html($log->id); ?></td>
                    <td>
                        <?php 
                        $action_labels = [
                            'create_product' => '📦 ایجاد محصول',
                            'update_product' => '🔄 به‌روزرسانی محصول',
                            'create_post' => '📝 ایجاد پست',
                            'update_post' => '🔄 به‌روزرسانی پست',
                        ];
                        echo isset($action_labels[$log->action_type]) 
                            ? $action_labels[$log->action_type] 
                            : esc_html($log->action_type);
                        ?>
                    </td>
                    <td><?php echo esc_html($log->product_name); ?></td>
                    <td>
                        <span class="pbr-status-badge pbr-status-<?php echo esc_attr($log->status); ?>">
                            <?php echo $log->status === 'success' ? '✅ موفق' : '❌ ناموفق'; ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($log->message); ?></td>
                    <td><?php echo date('Y/m/d H:i', strtotime($log->created_at)); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <div class="pbr-pagination">
            <?php
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo; قبلی',
                'next_text' => 'بعدی &raquo;',
                'total' => $total_pages,
                'current' => $current_page
            ]);
            ?>
        </div>
        <?php endif; ?>
    </div>
</div>
