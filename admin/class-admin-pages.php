<?php
/**
 * Admin Pages Handler for Podbaz
 */
if (!defined('ABSPATH')) exit;

class PBR_Admin_Pages {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Add menu pages
     */
    public function add_menu_pages() {
        // Main menu
        add_menu_page(
            'ربات پادباز',
            '🎨 پادباز',
            'manage_options',
            'podbaz-robot',
            [$this, 'render_main_page'],
            'dashicons-art',
            31
        );
        
        // Submenus
        add_submenu_page(
            'podbaz-robot',
            'تولید محصول جدید',
            '📦 محصول جدید',
            'manage_options',
            'podbaz-robot',
            [$this, 'render_main_page']
        );
        
        add_submenu_page(
            'podbaz-robot',
            'تولید پست بلاگ',
            '📝 پست جدید',
            'manage_options',
            'podbaz-post',
            [$this, 'render_post_page']
        );
        
        add_submenu_page(
            'podbaz-robot',
            'تولید دسته‌جمعی',
            '📦 تولید دسته‌جمعی',
            'manage_options',
            'podbaz-bulk',
            [$this, 'render_bulk_page']
        );
        
        add_submenu_page(
            'podbaz-robot',
            'به‌روزرسانی محتوا',
            '🔄 به‌روزرسانی',
            'manage_options',
            'podbaz-update',
            [$this, 'render_update_page']
        );
        
        add_submenu_page(
            'podbaz-robot',
            'مدیریت پرامپت‌ها',
            '📋 پرامپت‌ها',
            'manage_options',
            'podbaz-prompts',
            [$this, 'render_prompts_page']
        );
        
        add_submenu_page(
            'podbaz-robot',
            'تنظیمات',
            '⚙️ تنظیمات',
            'manage_options',
            'podbaz-settings',
            [$this, 'render_settings_page']
        );
        
        add_submenu_page(
            'podbaz-robot',
            'گزارش‌ها',
            '📊 گزارش‌ها',
            'manage_options',
            'podbaz-logs',
            [$this, 'render_logs_page']
        );
        }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'podbaz') === false) {
            return;
        }
        
        wp_enqueue_style(
            'pbr-admin-css',
            PBR_PLUGIN_URL . 'admin/css/admin.css',
            [],
            PBR_VERSION
        );
        
        wp_enqueue_script(
            'pbr-admin-js',
            PBR_PLUGIN_URL . 'admin/js/admin.js',
            ['jquery'],
            PBR_VERSION,
            true
        );
        
        wp_localize_script('pbr-admin-js', 'pbr_ajax', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pbr_ajax_nonce')
        ]);
    }

    /**
     * Render main page
     */
    public function render_main_page() {
        include PBR_PLUGIN_DIR . 'admin/views/main-page.php';
    }

    /**
     * Render post page
     */
    public function render_post_page() {
        include PBR_PLUGIN_DIR . 'admin/views/post-page.php';
    }

    /**
     * Render update page
     */
    public function render_update_page() {
        include PBR_PLUGIN_DIR . 'admin/views/update-page.php';
    }

    /**
     * Render prompts page
     */
    public function render_prompts_page() {
        include PBR_PLUGIN_DIR . 'admin/views/prompts-page.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        include PBR_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Render logs page
     */
    public function render_logs_page() {
        include PBR_PLUGIN_DIR . 'admin/views/logs-page.php';
    }
    
    /**
     * Render bulk page
     */
    public function render_bulk_page() {
        include PBR_PLUGIN_DIR . 'admin/views/bulk-page.php';
    }
}