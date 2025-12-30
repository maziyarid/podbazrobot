<?php
/**
 * Uninstall script for Podbaz Robot
 * 
 * This file is called when the plugin is uninstalled via the WordPress admin.
 * It handles cleanup of all plugin data.
 */

// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove plugin options
$options = [
    'pbr_blackbox_api_key',
    'pbr_tavily_api_key',
    'pbr_claude_model',
    'pbr_auto_publish',
    'pbr_enable_logging',
    'pbr_research_prompt',
    'pbr_content_prompt',
    'pbr_post_prompt',
    'pbr_update_prompt',
    'pbr_field_mappings',
];

foreach ($options as $option) {
    delete_option($option);
}

// Drop logs table
global $wpdb;
$table_name = $wpdb->prefix . 'pbr_logs';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Remove all post meta added by the plugin
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_pbr_%'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_product_%'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_battery_%'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_output_%'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_tank_%'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_coil_%'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_charging_%'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_display_%'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_chipset%'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_available_%'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_airflow_%'");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_vape_%'");

// Clear any cached data
wp_cache_flush();
