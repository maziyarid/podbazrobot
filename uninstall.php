<?php
/**
 * Uninstall PodBaz Robot
 *
 * This file is executed when the plugin is deleted from WordPress.
 * It removes all plugin data from the database.
 *
 * @package PodBazRobot
 */

// Exit if accessed directly or not uninstalling
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Define table suffix constant (same as in main plugin file)
define( 'PODBAZROBOT_TABLE_SUFFIX', 'podbazrobot_logs' );

// Delete plugin options
delete_option( 'podbazrobot_settings' );

// Delete transients
delete_transient( 'podbazrobot_cache' );

// Drop custom table
global $wpdb;
// Use constant for table suffix to ensure consistency
$table_name = $wpdb->prefix . PODBAZROBOT_TABLE_SUFFIX;
// Validate table name - allow alphanumeric, underscore, and hyphen
// WordPress table prefixes can contain these characters
if ( preg_match( '/^[a-zA-Z0-9_-]+$/', $table_name ) && strlen( $table_name ) <= 64 ) {
    $wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" );
}

// Clear any scheduled hooks
wp_clear_scheduled_hook( 'podbazrobot_cron_event' );
