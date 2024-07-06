<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


add_filter('cron_schedules', 'example_add_cron_interval');
function example_add_cron_interval($schedules)
{
    $schedules['five_seconds'] = array(
        'interval' => 1,
        'display' => esc_html__('Every Seconds'),
    );
    return $schedules;
}


// add the clean waiting list cron job
add_action('bookacti_cron_clean_waiting_list', 'ba_plus_clean_waiting_list');
add_action('bookacti_cron_remove_empty_events', 'ba_plus_remove_empty_events');
add_action('bookacti_cron_auto_add_wl', 'ba_plus_auto_register_waiting_list');

if (!wp_next_scheduled('bookacti_cron_clean_waiting_list')) {
    wp_schedule_event(time(), 'five_seconds', 'bookacti_cron_clean_waiting_list');
}
if (!wp_next_scheduled('bookacti_cron_remove_empty_events')) {
    wp_schedule_event(time(), 'five_seconds', 'bookacti_cron_remove_empty_events');
}
if (!wp_next_scheduled('bookacti_cron_auto_add_wl')) {
    wp_schedule_event(time(), 'five_seconds', 'bookacti_cron_auto_add_wl');
}



/**
 * Cron job to check if there are any waiting list users that can be moved to the main list
 */


/**
 * Cron job to purge the waiting list of past events
 */
function ba_plus_clean_waiting_list()
{
    echo "Checking for old waiting list<br>";
    $waiting_list = ba_plus_get_all_waiting_list();
    foreach ($waiting_list as $waiting) {
        $event_id = $waiting->event_id;
        $event_start = $waiting->start_date;
        $event_end = $waiting->end_date;

        if ($event_start < date('Y-m-d h:i:s', strtotime('-24 hour'))) {
            echo "Removing waiting list for event " . $event_id . "<br>";
            ba_plus_remove_all_waiting_list($event_id, $event_start, $event_end);
        }
    }
}

function ba_plus_remove_empty_events()
{
    echo "Checking for empty events<br>";
    // create a date object for today
    $today = new DateTime();
    $today = $today->format('Y-m-d h:i:s');
    $tonight = new DateTime();
    $tonight = date('Y-m-d H:i:s', strtotime('+14 hour'));
    $interval = array(
        'start' => $today,
        'end' => $tonight
    );
    $args = array(
        'interval' => $interval,
        'active' => 1
    );
    // get all events that are empty
    $events = bookacti_fetch_booked_events($args);
    foreach ($events["data"] as $event) {
        $booked = -bookacti_get_event_availability($event["id"], $event['start'], $event['end']) + $event['availability'];
        if ($booked < 3 && $event['start'] < date('Y-m-d h:i:s', strtotime('+24 hour'))) {
            // get all bookings for the event
            $filters = array(
                'event_id' => $event['id'],
                'active' => 1
            );
            $filters = bookacti_format_booking_filters($filters);
            $event['bookings'] = bookacti_get_bookings($filters);
            // Remove all bookings, refund, and send email to all users
            foreach ($event['bookings'] as $id => $booking) {
                if ($booking->state == "cancelled" || $booking->state == "refunded") {
                    continue;
                }
                $booking = bookacti_get_booking_by_id($booking->id, true);

                // cancel the booking
                $cancelled = ba_plus_set_refunded_booking($id);

                // refund the user
                $booking_pass = bapap_get_booking_pass($booking->booking_pass_id);
                
                $booking_pass['credits_current'] += intval($booking->booking_pass_credits);
                $credited = bapap_add_booking_pass_credits($booking->booking_pass_id, intval($booking->booking_pass_credits));
                // add to the log
                $log_data = array(
                    'credits_delta' => $booking->booking_pass_credits,
                    'credits_current' => $booking_pass['credits_current'],
                    'credits_total' => $booking_pass['credits_total'],
                    'reason' => "Annulation automatique (manque de participants) -" . $event['title'] . " (" . $event['start'] . ")",
                    'context' => 'updated_from_server',
                    'lang_switched' => 1
                );
                bapap_add_booking_pass_log($booking->booking_pass_id, $log_data);


                // send mail
                $user = get_user_by('id', $booking->user_id);
                $to = $user->user_email;
                echo "Send mail for cancel to : " . $to . "<br>";
                $subject = get_option('ba_plus_mail_cancel_title');
                $body = get_option('ba_plus_mail_cancel_body');
                $body = ba_plus_format_mail($body, $event['start'], $event['end'], $event['title'], $user); 
                $headers = array('Content-Type: text/html; charset=UTF-8');
                wp_mail($to, $subject, $body, $headers);

                // Send SMS
                $phone = banp_get_user_phone_number($booking->user_id);
                if ($phone) {
                    $notif = array(
                        'id' => 0,
                        'active' => 1,
                        'sms' => array(
                            'active' => 1,
                            'to' => array($phone),
                            'message' => $body
                        )
                    );

                    $sms_sent = banp_send_sms_notification($notif);
                    echo "Send SMS for cancel to : " . $phone . " (status:.". $sms_sent .")<br> ";
                }
            }
            // unbind the event
            $event_id = $event['id'];
            if ($event['repeat_freq'] != "none") {
                $event_new = bookacti_get_event_by_id($event['id']);
                $new_id = bookacti_unbind_selected_event_occurrence($event_new, $event['start'], $event['end']);
                if ($new_id) {
                    $event_id = $new_id;
                }
            }

            // deactivate the event
            bookacti_deactivate_event($event_id);
        }
    }
}

function ba_plus_auto_register_waiting_list()
{
    echo "Checking for waiting list<br>";
    $waiting_list = ba_plus_get_all_waiting_list();
    foreach ($waiting_list as $waiting) {
        $event_id = $waiting->event_id;
        $event_start = $waiting->start_date;
        $event_end = $waiting->end_date;

        $user = get_user_by('id', $waiting->user_id);
        $is_mail_send = get_user_meta($user->ID, 'send_mail_warning_48h_' . $event_id, true);


        // check if event is in less than 48 h 
        if ($event_start < date('Y-m-d h:i:s', strtotime('+48 hour')) && !$is_mail_send) {
            // send mail to user
            $to = $user->user_email;
            $subject = get_option('ba_plus_mail_waiting_list_title');
            $body = get_option('ba_plus_mail_waiting_list_body');
            $body = ba_plus_format_mail($body, $waiting->start_date, $waiting->end_date, $waiting->title, $user);
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($to, $subject, $body, $headers);

            // send sms
            $phone = banp_get_user_phone_number($waiting->user_id);
            if ($phone) {
                $notif = array(
                    'id' => 0,
                    'active' => 1,
                    'sms' => array(
                        'active' => 1,
                        'to' => array($phone),
                        'message' => $body
                    )
                );

                $sms_sent = banp_send_sms_notification($notif);
                echo "Send SMS for still in wl to : " . $phone . " (status:.". $sms_sent .")<br> ";
            }
            $is_mail_send = true;
            update_user_meta($user->ID, 'send_mail_warning_48h_' . $event_id, $is_mail_send);
            echo "Send mail for still in wl to : " . $to . "<br>";
        }

        // auto register the user if there is a spot available
        $booked = ba_plus_check_if_event_is_full($event_id, $event_start, $event_end);
        if (!$booked) {
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
            if ($pass->credits_current <= 0) {
                continue;
            }

            ba_plus_remove_waiting_list_by_event_id($event_id, $waiting->user_id, $event_start, $event_end);
            // add the user to the event
            $booking_data = bookacti_sanitize_booking_data(
                array(
                    'user_id' => $waiting->user_id,
                    'form_id' => $waiting->template_id,
                    'event_id' => $waiting->event_id,
                    'event_start' => $waiting->start_date,
                    'event_end' => $waiting->end_date,
                    'quantity' => 1,
                    'status' => "booked",
                    'payment_status' => "paid",
                    'active' => 'according_to_status'
                )
            );
            $booking_id = bookacti_insert_booking($booking_data);
            if ($booking_id) {
                // Remove one credit from the user
                $pass->credits_current -= 1;
                bapap_update_booking_pass_data($pass->id, array('credits_current' => $pass->credits_current));
                // add to the log
                $log_data = array(
                    'credits_delta' => '-1',
                    'credits_current' => $pass->credits_current,
                    'credits_total' => $pass->credits_total,
                    'reason' => "Inscription automatique (via liste d'attente) - " . $waiting->title . " (" . $waiting->start . ")",
                    'context' => 'updated_from_server',
                    'lang_switched' => 1
                );
                bapap_add_booking_pass_log($pass->id, $log_data);

                echo "User " . $user->display_name . " has been added to the event " . $waiting->title . "<br>";

                // Send email to user
                $user = get_user_by('id', $waiting->user_id);
                $to = $user->user_email;
                $subject = get_option('ba_plus_mail_booked_title');
                $body = get_option('ba_plus_mail_booked_body');
                $body = ba_plus_format_mail($body, $waiting->start_date, $waiting->end_date, $waiting->title, $user);
                $headers = array('Content-Type: text/html; charset=UTF-8');
                wp_mail($to, $subject, $body, $headers);
            }
        }

    }
}