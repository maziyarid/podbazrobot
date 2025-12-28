<?php
/**
 * Plugin Name: PodBaz Robot
 * Plugin URI: https://github.com/maziyarid/podbazrobot
 * Description: A WordPress plugin for automated content management and bot functionality
 * Version: 1.0.0
 * Author: Maziyar Moradi
 * Author URI: https://github.com/maziyarid
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: podbazrobot
 * Domain Path: /languages
 *
 * @package PodBazRobot
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'PODBAZROBOT_VERSION', '1.0.0' );
define( 'PODBAZROBOT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PODBAZROBOT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PODBAZROBOT_PLUGIN_FILE', __FILE__ );
define( 'PODBAZROBOT_TABLE_SUFFIX', 'podbazrobot_logs' );

/**
 * Main PodBazRobot Class
 */
class PodBazRobot {
    
    /**
     * Instance of this class
     *
     * @var object
     */
    private static $instance = null;
    
    /**
     * Get instance of the class
     *
     * @return PodBazRobot
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook( PODBAZROBOT_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( PODBAZROBOT_PLUGIN_FILE, array( $this, 'deactivate' ) );
        
        // Admin hooks
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        
        // Load text domain for translations
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
    }
    
    /**
     * Get table name with validation
     *
     * @return string|false Table name or false if invalid
     */
    private static function get_table_name() {
        global $wpdb;
        
        // Build table name from known constant
        $table_name = $wpdb->prefix . PODBAZROBOT_TABLE_SUFFIX;
        
        // Validate the complete table name matches expected pattern
        if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $table_name ) ) {
            return false;
        }
        
        return $table_name;
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $default_options = array(
            'enabled' => true,
            'api_key' => '',
            'interval' => 60,
        );
        
        if ( ! get_option( 'podbazrobot_settings' ) ) {
            add_option( 'podbazrobot_settings', $default_options );
        }
        
        // Create database table if needed
        $this->create_database_table();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events if any
        wp_clear_scheduled_hook( 'podbazrobot_cron_event' );
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database table
     */
    private function create_database_table() {
        global $wpdb;
        
        $table_name = self::get_table_name();
        if ( false === $table_name ) {
            return;
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            action varchar(255) NOT NULL,
            message text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'PodBaz Robot', 'podbazrobot' ),
            __( 'PodBaz Robot', 'podbazrobot' ),
            'manage_options',
            'podbazrobot',
            array( $this, 'render_admin_page' ),
            'dashicons-admin-generic',
            30
        );
        
        add_submenu_page(
            'podbazrobot',
            __( 'Settings', 'podbazrobot' ),
            __( 'Settings', 'podbazrobot' ),
            'manage_options',
            'podbazrobot',
            array( $this, 'render_admin_page' )
        );
        
        add_submenu_page(
            'podbazrobot',
            __( 'Logs', 'podbazrobot' ),
            __( 'Logs', 'podbazrobot' ),
            'manage_options',
            'podbazrobot-logs',
            array( $this, 'render_logs_page' )
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'podbazrobot_settings_group',
            'podbazrobot_settings',
            array( $this, 'sanitize_settings' )
        );
        
        add_settings_section(
            'podbazrobot_main_section',
            __( 'Main Settings', 'podbazrobot' ),
            array( $this, 'render_settings_section' ),
            'podbazrobot'
        );
        
        add_settings_field(
            'podbazrobot_enabled',
            __( 'Enable Plugin', 'podbazrobot' ),
            array( $this, 'render_enabled_field' ),
            'podbazrobot',
            'podbazrobot_main_section'
        );
        
        add_settings_field(
            'podbazrobot_api_key',
            __( 'API Key', 'podbazrobot' ),
            array( $this, 'render_api_key_field' ),
            'podbazrobot',
            'podbazrobot_main_section'
        );
        
        add_settings_field(
            'podbazrobot_interval',
            __( 'Update Interval (minutes)', 'podbazrobot' ),
            array( $this, 'render_interval_field' ),
            'podbazrobot',
            'podbazrobot_main_section'
        );
    }
    
    /**
     * Sanitize settings
     *
     * @param array $input Input values
     * @return array Sanitized values
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();
        
        $sanitized['enabled'] = isset( $input['enabled'] ) ? (bool) $input['enabled'] : false;
        $sanitized['api_key'] = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '';
        
        // Validate interval is between 1 and 1440 minutes
        $interval = isset( $input['interval'] ) ? absint( $input['interval'] ) : 60;
        $sanitized['interval'] = max( 1, min( 1440, $interval ) );
        
        return $sanitized;
    }
    
    /**
     * Render settings section
     */
    public function render_settings_section() {
        echo '<p>' . esc_html__( 'Configure the PodBaz Robot settings below.', 'podbazrobot' ) . '</p>';
    }
    
    /**
     * Render enabled field
     */
    public function render_enabled_field() {
        $options = get_option( 'podbazrobot_settings', array( 'enabled' => false ) );
        $enabled = isset( $options['enabled'] ) ? $options['enabled'] : false;
        ?>
        <input type="checkbox" name="podbazrobot_settings[enabled]" value="1" <?php checked( $enabled, true ); ?> />
        <label><?php esc_html_e( 'Enable the plugin functionality', 'podbazrobot' ); ?></label>
        <?php
    }
    
    /**
     * Render API key field
     */
    public function render_api_key_field() {
        $options = get_option( 'podbazrobot_settings', array( 'api_key' => '' ) );
        $api_key = isset( $options['api_key'] ) ? $options['api_key'] : '';
        ?>
        <input type="text" name="podbazrobot_settings[api_key]" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Enter your API key for external services (if required)', 'podbazrobot' ); ?></p>
        <?php
    }
    
    /**
     * Render interval field
     */
    public function render_interval_field() {
        $options = get_option( 'podbazrobot_settings', array( 'interval' => 60 ) );
        $interval = isset( $options['interval'] ) ? $options['interval'] : 60;
        ?>
        <input type="number" name="podbazrobot_settings[interval]" value="<?php echo esc_attr( $interval ); ?>" min="1" max="1440" />
        <p class="description"><?php esc_html_e( 'How often to run automated tasks (in minutes)', 'podbazrobot' ); ?></p>
        <?php
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'podbazrobot' ) );
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields( 'podbazrobot_settings_group' );
                do_settings_sections( 'podbazrobot' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render logs page
     */
    public function render_logs_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'podbazrobot' ) );
        }
        
        global $wpdb;
        $table_name = self::get_table_name();
        
        if ( false === $table_name ) {
            $logs = array();
        } else {
            // Get logs
            $logs = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM `{$table_name}` ORDER BY created_at DESC LIMIT %d",
                    100
                )
            );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'PodBaz Robot Logs', 'podbazrobot' ); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'podbazrobot' ); ?></th>
                        <th><?php esc_html_e( 'Action', 'podbazrobot' ); ?></th>
                        <th><?php esc_html_e( 'Message', 'podbazrobot' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'podbazrobot' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $logs ) ) : ?>
                        <?php foreach ( $logs as $log ) : ?>
                            <tr>
                                <td><?php echo esc_html( $log->id ); ?></td>
                                <td><?php echo esc_html( $log->action ); ?></td>
                                <td><?php echo esc_html( $log->message ); ?></td>
                                <td><?php echo esc_html( $log->created_at ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4"><?php esc_html_e( 'No logs found.', 'podbazrobot' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on plugin pages
        if ( strpos( $hook, 'podbazrobot' ) === false ) {
            return;
        }
        
        wp_enqueue_style(
            'podbazrobot-admin',
            PODBAZROBOT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PODBAZROBOT_VERSION
        );
        
        wp_enqueue_script(
            'podbazrobot-admin',
            PODBAZROBOT_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            PODBAZROBOT_VERSION,
            true
        );
    }
    
    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'podbazrobot',
            false,
            dirname( plugin_basename( PODBAZROBOT_PLUGIN_FILE ) ) . '/languages'
        );
    }
    
    /**
     * Log message
     *
     * @param string $action Action type
     * @param string $message Log message
     */
    public static function log( $action, $message ) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        if ( false === $table_name ) {
            return;
        }
        
        $wpdb->insert(
            $table_name,
            array(
                'action' => sanitize_text_field( $action ),
                'message' => sanitize_text_field( $message ),
            ),
            array( '%s', '%s' )
        );
    }
}

// Initialize the plugin
function podbazrobot_init() {
    return PodBazRobot::get_instance();
}

// Start the plugin
add_action( 'plugins_loaded', 'podbazrobot_init' );
