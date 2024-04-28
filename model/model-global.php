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

function ba_plus_cancel_event($event_id){
	global $wpdb;
	// remove all users from the event and send them a mail
	//$query = 'SELECT * FROM ' . BOOKACTI_TABLE_BOOKINGS . ' as B LEFT JOIN ' . $wpdb->users . ' as U ON B.user_id = U.id LEFT JOIN ' . BOOKACTI_TABLE_EVENTS . ' as E ON E.id = B.event_id WHERE B.event_id = %d';
	$query = "SELECT * FROM wp_bookacti_bookings as B LEFT JOIN wp_users as U ON B.user_id = U.id LEFT JOIN wp_bookacti_events as E ON E.id = B.event_id WHERE B.event_id = %d;";
	$query = $wpdb->prepare( $query, $event_id );
	$bookings = $wpdb->get_results( $query, OBJECT );

	if ( ! empty($bookings) ){
		foreach($bookings as $booking){
			$to = $booking->user_email;
			$subject = 'Event Cancelled';
			$body = 'The event ' . $booking->title . ' you have booked has been cancelled. We are sorry for the inconvenience.';
			$headers = array('Content-Type: text/html; charset=UTF-8');
			wp_mail( $to, $subject, $body, $headers );
		}
	}
	// remove all bookings
	$query = 'DELETE FROM ' . BOOKACTI_TABLE_BOOKINGS . ' WHERE event_id = %d';
	$query = $wpdb->prepare( $query, $event_id );
	$wpdb->query( $query );
	// remove the event
	$query = 'DELETE FROM ' . BOOKACTI_TABLE_EVENTS . ' WHERE id = %d';
	$query = $wpdb->prepare( $query, $event_id );
	$wpdb->query( $query );
}
