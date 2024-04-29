<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


add_filter( 'cron_schedules', 'example_add_cron_interval' );
function example_add_cron_interval( $schedules ) { 
    $schedules['five_seconds'] = array(
        'interval' => 1,
        'display'  => esc_html__( 'Every Seconds' ), );
    return $schedules;
}


// add the clean waiting list cron job
add_action( 'bookacti_cron_clean_waiting_list', 'ba_plus_clean_waiting_list' );
add_action( 'bookacti_cron_remove_empty_events', 'ba_plus_remove_empty_events' );

if ( ! wp_next_scheduled( 'bookacti_cron_clean_waiting_list' ) ) {
    wp_schedule_event( time(), 'hourly', 'bookacti_cron_clean_waiting_list' );
}
if ( ! wp_next_scheduled( 'bookacti_cron_remove_empty_events' ) ) {
    wp_schedule_event( time(), 'hourly', 'bookacti_cron_remove_empty_events' );
}



/**
 * Cron job to check if there are any waiting list users that can be moved to the main list
 */


/**
 * Cron job to purge the waiting list of past events
 */
function ba_plus_clean_waiting_list(){
    echo "Checking for waiting list<br>";
    $waiting_list = ba_plus_get_all_waiting_list();
    foreach($waiting_list as $waiting){
        $event_id = $waiting->event_id;
        $event_start = $waiting->start;

        if ( $event_start < date('Y-m-d h:i:s') ){
            ba_plus_remove_all_waiting_list($event_id);
        }
    }
}

function ba_plus_remove_empty_events(){
    echo "Checking for empty events<br>";
    // create a date object for today
    $today = new DateTime();
    $today = $today->format('Y-m-d h:i:s');
    $tonight = new DateTime();
    $tonight = date('Y-m-d H:i:s',strtotime('+14 hour'));
    $interval = array(
        'start' => $today,
        'end' => $tonight
    );
    $args = array(
        'interval' => $interval,
    );
    // get all events that are empty
    $events = bookacti_fetch_events($args);
    foreach($events["data"] as $event){
        $booked = -bookacti_get_event_availability($event["id"], $event['start'], $event['end']) + $event['availability'];
        if ( $booked < 3 ){
            // Remove all bookings, refund, and send email to all users TD& 
            bookacti_cancel_event($event['id']);
        }
    }
}