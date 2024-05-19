<?php
if (!defined('ABSPATH')) {
    exit;
}


/**
 * Put an user into the waiting list 
 * @version 1.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @param int $user_id
 * @return int
 */
function ba_plus_insert_waiting_list($user_id, $event_id, $start_date, $end_date)
{
    global $wpdb;

    $query = 'INSERT INTO ' . BOOKACTI_TABLE_WAITING_LIST . ' (user_id, event_id, start_date, end_date) VALUES (%d, %d, %s, %s)';
    $query = $wpdb->prepare($query, $user_id, $event_id, $start_date, $end_date);
    $wpdb->query($query);

    return !empty($wpdb->insert_id) ? $wpdb->insert_id : 0;
}

/**
 * Get the waiting list for an event 
 * @version 1.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @return array
 */
function ba_plus_get_event_waiting_list($event_id, $start_date, $end_date)
{
    global $wpdb;

    $query = 'SELECT * FROM ' . BOOKACTI_TABLE_WAITING_LIST . ' WHERE event_id = %d AND start_date = %s AND end_date = %s';
    $query = $wpdb->prepare($query, $event_id);
    $waiting_list = $wpdb->get_results($query, OBJECT);

    return $waiting_list;
}


/**
 * Check if an user is in the waiting list for an event 
 * @version 1.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @param int $user_id
 * @return array
 */
function ba_plus_check_if_user_is_in_waiting_list($user_id, $event_id, $start_date, $end_date)
{
    global $wpdb;

    $query = 'SELECT * FROM ' . BOOKACTI_TABLE_WAITING_LIST . ' WHERE user_id = %d AND event_id = %d AND start_date = %s AND end_date = %s';
    $query = $wpdb->prepare($query, $user_id, $event_id, $start_date, $end_date);
    $waiting_list = $wpdb->get_results($query, OBJECT);

    return $waiting_list;
}

/**
 * Get the waiting list for an user 
 * @version 1.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @return array
 */
function ba_plus_get_user_waiting_list($user_id)
{
    global $wpdb;

    $query = 'SELECT * FROM ' . BOOKACTI_TABLE_WAITING_LIST . ' WHERE user_id = %d';
    $query = $wpdb->prepare($query, $user_id);
    $waiting_list = $wpdb->get_results($query, OBJECT);

    return $waiting_list;
}

/**
 * Remove an user from the waiting list 
 * @version 1.0
 * @global wpdb $wpdb
 * @param int $waiting_id
 * @param int $user_id
 * @return int
 */
function ba_plus_remove_waiting_list($waiting_id, $user_id)
{
    global $wpdb;

    $query = 'DELETE FROM ' . BOOKACTI_TABLE_WAITING_LIST . ' WHERE id = %d AND user_id = %d';
    $query = $wpdb->prepare($query, $waiting_id, $user_id);
    $wpdb->query($query);

    return $wpdb->rows_affected;
}

/**
 * Remove an user from the waiting list 
 * @version 1.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @param int $user_id
 * @return int
 */
function ba_plus_remove_waiting_list_by_event_id($event_id, $user_id, $start_date, $end_date)
{
    global $wpdb;

    $query = 'DELETE FROM ' . BOOKACTI_TABLE_WAITING_LIST . ' WHERE event_id = %d AND user_id = %d AND start_date = %s AND end_date = %s';
    $query = $wpdb->prepare($query, $event_id, $user_id);
    $wpdb->query($query);

    return $wpdb->rows_affected;
}

/**
 * Get all the waiting list plus join the event data 
 */
function ba_plus_get_all_waiting_list()
{
    global $wpdb;

    $query = 'SELECT * FROM ' . BOOKACTI_TABLE_WAITING_LIST . ' as WL LEFT JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON WL.event_id = E.id ';
    $waiting_list = $wpdb->get_results($query);

    return $waiting_list;
}

/**
 * Remove all users from the waiting list for an event 
 * @version 1.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @return int
 */
function ba_plus_remove_all_waiting_list($event_id, $start_date, $end_date)
{
    global $wpdb;

    $query = 'DELETE FROM ' . BOOKACTI_TABLE_WAITING_LIST . ' WHERE event_id = %d AND start_date = %s AND end_date = %s';
    $query = $wpdb->prepare($query, $event_id);
    $wpdb->query($query);

    return $wpdb->rows_affected;
}

/**
 * Get the number of users in the waiting list for an event 
 * @version 1.0
 * @global wpdb $wpdb
 * @param int $event_id
 * @return int
 */
function ba_plus_get_waiting_list_count($event_id, $start_date, $end_date)
{
    global $wpdb;

    $query = 'SELECT COUNT(*) FROM ' . BOOKACTI_TABLE_WAITING_LIST . ' WHERE event_id = %d AND start_date = %s AND end_date = %s';
    $query = $wpdb->prepare($query, $event_id);
    $waiting_list_count = $wpdb->get_var($query);

    return $waiting_list_count;
}