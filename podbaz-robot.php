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
        require_once PBR_PLUGIN_DIR . 'includes/class-prompts.php';
        require_once PBR_PLUGIN_DIR . 'includes/class-blackbox-api.php';
        require_once PBR_PLUGIN_DIR . 'includes/class-tavily-api.php';
        require_once PBR_PLUGIN_DIR . 'includes/class-html-parser.php';
        require_once PBR_PLUGIN_DIR . 'includes/class-custom-fields.php';
        require_once PBR_PLUGIN_DIR . 'includes/class-product-handler.php';
        require_once PBR_PLUGIN_DIR . 'includes/class-post-handler.php';
        
        if (is_admin()) {
            require_once PBR_PLUGIN_DIR . 'admin/class-admin-pages.php';
            require_once PBR_PLUGIN_DIR . 'admin/class-ajax-handlers.php';
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
    // Load prompts class for initialization
    require_once plugin_dir_path(__FILE__) . 'includes/class-prompts.php';
    
    $defaults = [
        'pbr_blackbox_api_key' => '',
        'pbr_tavily_api_key' => '',
        'pbr_claude_model' => 'blackboxai/anthropic/claude-3.5-sonnet',
        'pbr_auto_publish' => 'draft',
        'pbr_enable_logging' => 'yes',
    ];
    
    foreach ($defaults as $key => $value) {
        if (get_option($key) === false) {
            add_option($key, $value);
        }
    }
    
    // Migrate invalid model names
    $current_model = get_option('pbr_claude_model');
    $invalid_models = [
        'blackboxai', 'blackboxai-pro', 
        'claude-sonnet-4-20250514', 'claude-3-5-sonnet-20241022',
        'gpt-4o', 'gpt-4-turbo', 'gpt-4', 
        'claude-3-opus', 'claude-3-sonnet', 'claude-3-haiku',
        'gemini-1.5-pro', 'gpt-3.5-turbo'
    ];
    if (in_array($current_model, $invalid_models)) {
        update_option('pbr_claude_model', 'blackboxai/anthropic/claude-3.5-sonnet');
    }
    
    PBR_Prompts::init_default_prompts();
    
    // Create logs table
    global $wpdb;
    $table_name = $wpdb->prefix . 'pbr_logs';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        action_type varchar(50) NOT NULL,
        product_name varchar(255) NOT NULL,
        status varchar(20) NOT NULL,
        message text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

// Initialize Plugin
add_action('plugins_loaded', function() {
    // Auto-migrate invalid model names on plugin load
    $current_model = get_option('pbr_claude_model');
    $invalid_models = [
        'blackboxai', 'blackboxai-pro', 
        'claude-sonnet-4-20250514', 'claude-3-5-sonnet-20241022',
        'gpt-4o', 'gpt-4-turbo', 'gpt-4', 
        'claude-3-opus', 'claude-3-sonnet', 'claude-3-haiku',
        'gemini-1.5-pro', 'gpt-3.5-turbo'
    ];
    if ($current_model && in_array($current_model, $invalid_models)) {
        update_option('pbr_claude_model', 'blackboxai/anthropic/claude-3.5-sonnet');
    }
    
    Podbaz_Robot::get_instance();
}, 10);
