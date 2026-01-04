<?php
/**
 * Plugin Name: Podbaz Robot 🎨
 * Plugin URI: https://podbaz.com
 * Description: ربات هوشمند تولید محتوای HTML برای ویرایشگر کلاسیک وردپرس
 * Version: 1.0.0
 * Author: Podbaz Team
 * Author URI: https://podbaz.com
 * Text Domain: podbaz-robot
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin Constants
define('PBR_VERSION', '1.0.0');
define('PBR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PBR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PBR_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
final class Podbaz_Robot {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        require_once PBR_PLUGIN_DIR . 'class-prompts.php';
        require_once PBR_PLUGIN_DIR . 'class-blackbox-api.php';
        require_once PBR_PLUGIN_DIR . 'class-tavily-api.php';
        require_once PBR_PLUGIN_DIR . 'class-html-parser.php';
        require_once PBR_PLUGIN_DIR . 'class-custom-fields.php';
        require_once PBR_PLUGIN_DIR . 'class-product-handler.php';
        require_once PBR_PLUGIN_DIR . 'class-post-handler.php';
        require_once PBR_PLUGIN_DIR . 'class-queue-manager.php';
        
        if (is_admin()) {
            require_once PBR_PLUGIN_DIR . 'class-admin-pages.php';
            require_once PBR_PLUGIN_DIR . 'class-ajax-handlers.php';
        }
    }
    
    private function init_hooks() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        if (is_admin()) {
            new PBR_Admin_Pages();
            new PBR_Ajax_Handlers();
        }
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('podbaz-robot', false, dirname(PBR_PLUGIN_BASENAME) . '/languages');
    }
}

// Activation Hook
register_activation_hook(__FILE__, function() {
    $defaults = [
        'pbr_blackbox_api_key' => '',
        'pbr_tavily_api_key' => '',
        'pbr_claude_model' => 'blackboxai/x-ai/grok-code-fast-1:free',
        'pbr_auto_publish' => 'draft',
        'pbr_enable_logging' => 'yes',
        'pbr_enable_multi_agent' => 'no',
        'pbr_primary_color' => '#29853a',
        'pbr_use_theme_color' => 'no',
    ];
    
    foreach ($defaults as $key => $value) {
        if (get_option($key) === false) {
            add_option($key, $value);
        }
    }
    
    PBR_Prompts::init_default_prompts();
    
    // Create logs table
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $logs_table = $wpdb->prefix . 'pbr_logs';
    $logs_sql = "CREATE TABLE IF NOT EXISTS $logs_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        action_type varchar(50) NOT NULL,
        product_name varchar(255) NOT NULL,
        status varchar(20) NOT NULL,
        message text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY action_type (action_type),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Create queue table
    $queue_table = $wpdb->prefix . 'pbr_queue';
    $queue_sql = "CREATE TABLE IF NOT EXISTS $queue_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        keywords text,
        item_type varchar(50) NOT NULL DEFAULT 'product',
        priority int(11) NOT NULL DEFAULT 0,
        status varchar(20) NOT NULL DEFAULT 'pending',
        result_id bigint(20) DEFAULT NULL,
        error_message text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        processed_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY status (status),
        KEY priority (priority),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($logs_sql);
    dbDelta($queue_sql);
});

// Initialize Plugin
add_action('plugins_loaded', function() {
    Podbaz_Robot::get_instance();
}, 10);
