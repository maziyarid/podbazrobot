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

// Delete plugin options
delete_option( 'podbazrobot_settings' );

// Delete transients
delete_transient( 'podbazrobot_cache' );

// Drop custom table
global $wpdb;
$table_name = $wpdb->prefix . 'podbazrobot_logs';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

// Clear any scheduled hooks
wp_clear_scheduled_hook( 'podbazrobot_cron_event' );
