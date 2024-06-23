<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Table name
global $wpdb;
$db_prefix = $wpdb->prefix;

define('BOOKACTI_TABLE_WAITING_LIST', $db_prefix . 'bookacti_waiting_list');

// Booking activities table | This is a constant from the Booking Activities plugin
if (!defined('BOOKACTI_TABLE_EVENTS')) {
	define('BOOKACTI_TABLE_EVENTS', $db_prefix . 'bookacti_events');
}
if (!defined('BOOKACTI_TABLE_ACTIVITIES')) {
	define('BOOKACTI_TABLE_ACTIVITIES', $db_prefix . 'bookacti_activities');
}
if (!defined('BOOKACTI_TABLE_BOOKINGS')) {
	define('BOOKACTI_TABLE_BOOKINGS', $db_prefix . 'bookacti_bookings');
}
if (!defined('BOOKACTI_TABLE_META')) {
	define('BOOKACTI_TABLE_META', $db_prefix . 'bookacti_meta');
}
if (!defined('BOOKACTI_TABLE_PASSES')) {
	define('BOOKACTI_TABLE_PASSES', $db_prefix . 'bookacti_passes');
}
if (!defined('BOOKACTI_TABLE_PASSES_TEMPLATES')) {
	define('BOOKACTI_TABLE_PASSES_TEMPLATES', $db_prefix . 'bookacti_passes_templates');
}

function ba_plus_check_if_already_booked($user_id, $event_id, $start_date, $end_date)
{
	global $wpdb;
	$query = 'SELECT * FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE user_id = %d AND event_id = %d AND active = 1 AND event_start = %s AND event_end = %s';
	$query = $wpdb->prepare($query, $user_id, $event_id, $start_date, $end_date);
	$booking = $wpdb->get_row($query, OBJECT);
	if (!empty($booking)) {
		return true;
	}
	return false;
}

function ba_plus_check_if_event_is_full($event_id, $start_date, $end_date)
{
	global $wpdb;
	$query = 'SELECT COUNT(*) as booked FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE event_id = %d AND active = 1 AND event_start = %s AND event_end = %s'; 
	$query = $wpdb->prepare($query, $event_id, $start_date, $end_date);
	$booked = $wpdb->get_var($query);
	$query = 'SELECT availability FROM ' . BOOKACTI_TABLE_EVENTS . ' WHERE id = %d';
	$query = $wpdb->prepare($query, $event_id);
	$availability = $wpdb->get_var($query);
	if ($booked >= $availability) {
		return true;
	}
	return false;
}

function ba_plus_get_booking($booking_id)
{
	global $wpdb;
	$query = 'SELECT * FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE id = %d';
	$query = $wpdb->prepare($query, $booking_id);
	$booking = $wpdb->get_row($query, OBJECT);
	return $booking;
}

function ba_plus_set_refunded_booking( $booking_id ) {
	global $wpdb;

	$query = 'UPDATE ' . BOOKACTI_TABLE_BOOKINGS . ' SET state = "refunded", active = 0 WHERE id = %d AND active = 1';
	$prep  = $wpdb->prepare( $query, $booking_id );
	$cancelled = $wpdb->query( $prep );

	return $cancelled;
}

function ba_plus_change_event_title($event_id, $event_start, $event_end, $event_title){
	global $wpdb;
	$query = 'UPDATE ' . BOOKACTI_TABLE_EVENTS . ' SET title = %s WHERE id = %d and start = %s and end = %s';
	$query = $wpdb->prepare($query, $event_title, $event_id, $event_start, $event_end);
	$updated = $wpdb->query($query);
	return $updated;
}

function ba_plus_change_event_availability($event_id, $event_start, $event_end, $availability){
	global $wpdb;
	$query = 'UPDATE ' . BOOKACTI_TABLE_EVENTS . ' SET availability = %d WHERE id = %d and start = %s and end = %s';
	$query = $wpdb->prepare($query, $availability, $event_id, $event_start, $event_end);
	$updated = $wpdb->query($query);
	return $query;
	return $updated;
}

function ba_plus_restore_event_availability($event_id, $event_start, $event_end){
	global $wpdb;
	$query = 'UPDATE ' . BOOKACTI_TABLE_EVENTS . 'as E SET availability = (SELECT availability FROM ' . BOOKACTI_TABLE_ACTIVITIES . ' WHERE id = E.activity_id) WHERE id = %d and start = %s and end = %s';
	$query = $wpdb->prepare($query, $event_id, $event_start, $event_end);
	$updated = $wpdb->query($query);
	return $updated;
}

function ba_plus_get_event_availability($event_id){
	global $wpdb;
	$query = 'SELECT A.availability FROM ' . BOOKACTI_TABLE_ACTIVITIES . ' AS A WHERE A.id IN (SELECT E.activity_id FROM ' . BOOKACTI_TABLE_EVENTS . 'AS E WHERE E.id = %d)';
	$query = $wpdb->prepare($query, $event_id);
	$availability = $wpdb->get_row($query, OBJECT);
	return $availability;
}

function ba_plus_disable_event($event_id, $event_start, $event_end){
	global $wpdb;
	$query = 'UPDATE ' . BOOKACTI_TABLE_EVENTS . ' SET active = 0 WHERE id = %d and start = %s and end = %s';
	$query = $wpdb->prepare($query, $event_id, $event_start, $event_end);
	$updated = $wpdb->query($query);
	return $updated;
}