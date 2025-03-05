<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create the necessary tables for the plugin.
 */
function ba_plus_create_tables()
{
    global $wpdb;
    $wpdb->hide_errors();
    $collate = '';
    if ($wpdb->has_cap('collation')) {
        $collate = $wpdb->get_charset_collate();
    }

    $table_waiting_list = 'CREATE TABLE ' . BOOKACTI_TABLE_WAITING_LIST . '(
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id VARCHAR(64),
		event_id BIGINT UNSIGNED,
        start_date DATETIME,
        end_date DATETIME,
		PRIMARY KEY(id),
		KEY event_id(event_id),
		KEY user_id(user_id)
	)' . $collate . ';';

    // Execute the queries
    if (!function_exists('dbDelta')) {
        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
    }
    dbDelta($table_waiting_list);
}

/**
 * Drop the tables created by the plugin.
 */
function ba_plus_drop_table()
{
    global $wpdb;
    $wpdb->hide_errors();
    $wpdb->query('DROP TABLE IF EXISTS ' . BOOKACTI_TABLE_WAITING_LIST);
}
