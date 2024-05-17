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
add_action( 'bookacti_cron_auto_add_wl', 'ba_plus_auto_register_waiting_list' );

if ( ! wp_next_scheduled( 'bookacti_cron_clean_waiting_list' ) ) {
    wp_schedule_event( time(), 'five_seconds', 'bookacti_cron_clean_waiting_list' );
}
if ( ! wp_next_scheduled( 'bookacti_cron_remove_empty_events' ) ) {
    wp_schedule_event( time(), 'five_seconds', 'bookacti_cron_remove_empty_events' );
}
if ( ! wp_next_scheduled( 'bookacti_cron_auto_add_wl' ) ) {
    wp_schedule_event( time(), 'five_seconds', 'bookacti_cron_auto_add_wl' );
}



/**
 * Cron job to check if there are any waiting list users that can be moved to the main list
 */


/**
 * Cron job to purge the waiting list of past events
 */
function ba_plus_clean_waiting_list(){
    echo "Checking for old waiting list<br>";
    $waiting_list = ba_plus_get_all_waiting_list();
    foreach($waiting_list as $waiting){
        $event_id = $waiting->event_id;
        $event_start = $waiting->start;

        if ( $event_start < date('Y-m-d h:i:s', strtotime( '-10 hour' ) ) ) {
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
            for ($i = 0; $i < count($event['bookings']); $i++) {
                // refund the user
                $filters = array(
                    'user_id' => $event['bookings'][$i]['user_id'],
                    'active' => 1
                );
                $filters = bapap_format_booking_pass_filters($filters);
                $pass = bapap_get_booking_passes($filters);

                foreach ($pass as $p) {
                    $pass = $p;
                    break;
                }

                $pass->credits_current -= 1;
                bapap_update_booking_pass_data($pass->id, array('credits_current' => $pass->credits_current));
                // add to the log
                $log_data = array( 
                    'credits_current' => $pass->credits_current,
                    'credits_total' => $pass->credits_total,
                    'reason' => "Annulation automatique (manque de participants) - Remboursement d'un crédit",
                    'context' => 'updated_from_server',
                    'lang_switched' => 1
                );
                bapap_add_booking_pass_log( $pass->id, $log_data );


                // send mail
                $booking = $event['bookings'][$i];
                $user = get_user_by('id', $booking['user_id']);
                $to = $user->user_email;
                $subject = get_option( 'ba_plus_mail_cancel_title' );
                $body = get_option( 'ba_plus_mail_cancel_body' );
                $body = str_replace( '%event%', $event['title'], $body );
                $headers = array('Content-Type: text/html; charset=UTF-8');
                wp_mail( $to, $subject, $body, $headers );
                echo "Mail sent to ".$user->display_name."<br>";
            }
        }
    }
}

function ba_plus_auto_register_waiting_list(){
    echo "Checking for waiting list<br>";
    $waiting_list = ba_plus_get_all_waiting_list();
    foreach($waiting_list as $waiting){
        $event_id = $waiting->event_id;

        // auto register the user if there is a spot available
        $booked = ba_plus_check_if_event_is_full($event_id);
        if ( !$booked ){
            // check user balance 
            $filters = array(
                'user_id' => $waiting->user_id,
                'active' => 1
            );
            $filters = bapap_format_booking_pass_filters($filters);
            $pass = bapap_get_booking_passes($filters);

            foreach ($pass as $p) {
                $pass = $p;
                break;
            }
            if ( $pass->credits_current <= 0 ){
                continue;
            }

            ba_plus_remove_waiting_list_by_event_id($event_id, $waiting->user_id);
            // add the user to the event
            $booking_data = bookacti_sanitize_booking_data( array( 
				'user_id'        => $waiting->user_id,
				'form_id'        => $waiting->template_id,
				'event_id'       => $waiting->event_id,
				'event_start'    => $waiting->start,
				'event_end'      => $waiting->end,
				'quantity'       => 1,
				'status'         => "pending",
				'payment_status' => "none",
				'active'         => 'according_to_status'
			) );
            $booking_id = bookacti_insert_booking( $booking_data );
            if ( $booking_id ) {
                // Remove one credit from the user

                $pass->credits_current -= 1;
                bapap_update_booking_pass_data($pass->id, array('credits_current' => $pass->credits_current));
                // add to the log
                $log_data = array( 
                    'credits_current' => $pass->credits_current,
                    'credits_total' => $pass->credits_total,
                    'reason' => "Inscription automatique (via liste d'attente) - Retrait d'un crédit",
                    'context' => 'updated_from_server',
                    'lang_switched' => 1
                );
                bapap_add_booking_pass_log( $pass->id, $log_data );



                // Send email to user
                $user = get_user_by( 'id', $waiting->user_id );
                $to = $user->user_email;
                $subject = get_option( 'ba_plus_mail_booked_title', 'Votre inscription a été validée');
                $body = get_option( 'ba_plus_mail_booked_body', 'Votre inscription à l\'événement %event% a été validée. Vous pouvez consulter les détails de votre inscription sur votre espace personnel.');
                $body = str_replace( '%event%', $waiting->title, $body );
                $headers = array('Content-Type: text/html; charset=UTF-8');
                wp_mail( $to, $subject, $body, $headers );
                echo "Mail sent to ".$user->display_name."<br>";
            }
        }

    }
}