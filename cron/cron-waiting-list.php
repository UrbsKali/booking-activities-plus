<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


add_filter('cron_schedules', 'example_add_cron_interval');
function example_add_cron_interval($schedules)
{
    $schedules['five_seconds'] = array(
        'interval' => 5,
        'display' => esc_html__('Every five Seconds'),
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

        $timezone = new DateTimeZone('Europe/Paris');
        $diff = date_diff(date_create('now', $timezone), date_create($event_start, $timezone));
        if ($diff->invert == 1) {
            echo "&nbsp;&nbsp;Removing waiting list for event " . $event_id . "<br>";
            ba_plus_remove_all_waiting_list($event_id, $event_start, $event_end);
        }
    }
}

function ba_plus_remove_empty_events()
{
    echo "Checking for empty events<br>";
    // create a date object for today
    $timezone = new DateTimeZone('Europe/Paris');
    $today = new DateTime("now", $timezone);
    $tonight = new DateTime('+15 hour', $timezone);
    $interval = array(
        'start' => $today->format('Y-m-d H:i:s'),
        'end' => $tonight->format('Y-m-d H:i:s')
    );
    $args = array(
        'interval' => $interval,
        'active' => 1,
    );
    // get all events that are empty
    $events = bookacti_fetch_booked_events($args);
    foreach ($events["data"] as $event) {
        $booked = -bookacti_get_event_availability($event["id"], $event['start'], $event['end']) + $event['availability'];
        echo "&nbsp;&nbsp;Checking event " . $event["id"] . " with " . $booked . " booked<br>";
        if ($booked < 3) {
            echo "&nbsp;&nbsp;Removing event " . $event["id"] . "<br>";
            // get all bookings for the event
            $filters = array(
                'event_id' => $event['id'],
                'active' => 1
            );
            $filters = bookacti_format_booking_filters($filters);
            $event['bookings'] = bookacti_get_bookings($filters);

            // unbind the event
            $event_id = $event['id'];

            if ($event['repeat_freq'] != "none" || $event['repeat_from'] != "") {
                $event_new = bookacti_get_event_by_id($event['id']);
                $new_id = bookacti_unbind_selected_event_occurrence($event_new, $event['start'], $event['end']);
                if ($new_id) {
                    $event_id = $new_id;
                } else {
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;Event " . $event_id . " could not be unbind<br>";
                    // send mail to admin
                    $to = 'urbain.lantres@gmail.com';
                    $subject = "Erreur lors de la suppression d'un événement";
                    $body = "L'événement " . $event['title'] . " (" . $event['start'] . ") n'a pas pu être délié.";
                    wp_mail($to, $subject, $body);
                    continue;
                }
                $to = 'urbain.lantres@gmail.com';
                $subject = "Debug - Suppression d'un événement";
                $body = "L'événement " . $event['title'] . " (" . $event['start'] . ") a été supprimé. voici l'objet : " . print_r($event, true);
                wp_mail($to, $subject, $body);
            }

            // deactivate the event
            $deactivated = bookacti_deactivate_event($event_id);
            if ($deactivated) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;Event " . $event_id . " deactivated<br>";
            } else {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;Event " . $event_id . " could not be deactivated<br>";
                $to = 'urbain.lantres@gmail.com';
                $subject = "Erreur lors de la suppression d'un événement";
                $body = "L'événement " . $event['title'] . " (" . $event['start'] . ") n'a pas pu être désactivé.";
                wp_mail($to, $subject, $body);
                continue;
            }

            // Remove all bookings, refund, and send email to all users
            foreach ($event['bookings'] as $id => $booking) {
                if ($booking->state == "cancelled" || $booking->state == "refunded") {
                    continue;
                }
                $booking = bookacti_get_booking_by_id($booking->id, true);

                // cancel the booking
                $cancelled = ba_plus_set_refunded_booking($id);

                // refund the user
                $filters = array(
                    'user_id' => $booking->user_id,
                    'active' => 1
                );
                $filters = bapap_format_booking_pass_filters($filters);
                $pass = bapap_get_booking_passes($filters);

                if (empty($pass)) {
                    echo '&nbsp;&nbsp;&nbsp;&nbsp;L\'utilisateur ' . $booking->user_id . ' n\'a pas de forfaits actif.';
                }

                // sort the booking pass by expiration date, longer first
                usort($pass, function ($a, $b) {
                    return -strtotime($a->expiration_date) + strtotime($b->expiration_date);
                });

                $pass[0]->credits_current += intval($booking->booking_pass_credits);
                $credited = bapap_add_booking_pass_credits($booking->booking_pass_id, intval($booking->booking_pass_credits));
                // add to the log
                $log_data = array(
                    'credits_delta' => $booking->booking_pass_credits,
                    'credits_current' => $pass[0]->credits_current,
                    'credits_total' => $pass[0]->credits_total,
                    'reason' => "Annulation automatique (manque de participants) -" . $event['title'] . " (" . $event['start'] . ")",
                    'context' => 'updated_from_server',
                    'lang_switched' => 1
                );
                bapap_add_booking_pass_log($booking->booking_pass_id, $log_data);


                // send mail
                $user = get_user_by('id', $booking->user_id);
                $to = $user->user_email;
                echo "&nbsp;&nbsp;&nbsp;&nbsp;Send mail for cancel to : " . $to . "<br>";
                $subject = get_option('ba_plus_mail_cancel_title');
                $body = get_option('ba_plus_mail_cancel_body');
                $body = ba_plus_format_mail($body, $event['start'], $event['end'], $event['title'], $user);
                $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ACADEMIE FRANCAISE DE PILATES <sarah.portiche@academie-pilates.com>');
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
                            'message' => str_replace("<br>", "\n", $body)
                        )
                    );

                    $sms_sent = banp_send_sms_notification($notif);
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;Send SMS for cancel to : " . $phone . " (status:." . $sms_sent . ")<br> ";
                }
            }
        }
    }
}

function ba_plus_auto_register_waiting_list()
{
    echo "Checking for waiting list<br>";
    $waiting_list = ba_plus_get_all_waiting_list();
    $timezone = new DateTimeZone('Europe/Paris');
    $today = new DateTime('now', $timezone);
    foreach ($waiting_list as $waiting) {
        $event_id = $waiting->event_id;
        $event_start = $waiting->start_date;
        $event_end = $waiting->end_date;

        $user = get_user_by('id', $waiting->user_id);
        $is_mail_send = get_user_meta($user->ID, 'send_mail_warning_48h_' . $event_id, true);


        // check if event is in less than 48 h / Paris
        $diff = date_diff($today, date_create($event_start, $timezone));
        if ($diff->days < 2 && $diff->invert == 0 && !$is_mail_send) {
            // send mail to user
            $to = $user->user_email;
            $subject = get_option('ba_plus_mail_waiting_list_title');
            $body = get_option('ba_plus_mail_waiting_list_body');
            $body = ba_plus_format_mail($body, $waiting->start_date, $waiting->end_date, $waiting->title, $user);
            $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ACADEMIE FRANCAISE DE PILATES <sarah.portiche@academie-pilates.com>');
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
                        'message' => str_replace("<br>", "\n", $body)
                    )
                );

                $sms_sent = banp_send_sms_notification($notif);
                echo "&nbsp;&nbsp;Send SMS for still in wl to : " . $phone . " (status: " . $sms_sent . ")<br> ";
            }
            $is_mail_send = true;
            update_user_meta($user->ID, 'send_mail_warning_48h_' . $event_id, $is_mail_send);
            echo "&nbsp;&nbsp;Send mail for still in wl to : " . $to . "<br>";
        }

        // auto register the user if there is a spot available
        $booked = ba_plus_check_if_event_is_full($event_id, $event_start, $event_end);
        $diff = date_diff($today, date_create($event_start, $timezone));
        if (!$booked && $diff->invert == 0) {
            // check user balance 
            $filters = array(
                'user_id' => $user->ID,
                'active' => 1
            );
            $filters = bapap_format_booking_pass_filters($filters);
            $pass = bapap_get_booking_passes($filters);

            if (empty($pass)) {
                echo "&nbsp;&nbsp;User " . $user->display_name . " has no active pass<br>";
                continue;
            }

            // sort the booking pass by expiration date, shorter first
            usort($pass, function ($a, $b) {
                return  strtotime($a->expiration_date) - strtotime($b->expiration_date);
            });

            // remove empty booking pass from the list
            $pass = array_filter($pass, function ($p) {
                return $p->credits_current > 0;
            });

            if (empty($pass)) {
                echo "&nbsp;&nbsp;User " . $user->display_name . " has no credit left<br>";
                // send mail to admin
                // send mail to admin
                $to = 'urbain.lantres@gmail.com';
                $subject = "Debug: file d'attente";
                $body = "La file d'attente " . $waiting->title . " (" . $waiting->start . ") n'a pas pu être traitée. L'utilisateur " . $user->display_name . " n'a pas de crédit.";
                wp_mail($to, $subject, $body);               
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
                $pass[0]->credits_current -= 1;
                bapap_update_booking_pass_data($pass[0]->id, array('credits_current' => $pass[0]->credits_current));
                // add to the log
                $log_data = array(
                    'credits_delta' => '-1',
                    'credits_current' => $pass[0]->credits_current,
                    'credits_total' => $pass[0]->credits_total,
                    'reason' => "Inscription automatique (via liste d'attente) - " . $waiting->title . " (" . $waiting->start . ")",
                    'context' => 'updated_from_server',
                    'lang_switched' => 1
                );
                bapap_add_booking_pass_log($pass[0]->id, $log_data);

                echo "&nbsp;&nbsp;User " . $user->display_name . " has been added to the event " . $waiting->title . "<br>";

                // Send email to user
                $user = get_user_by('id', $waiting->user_id);
                $to = $user->user_email;
                $subject = get_option('ba_plus_mail_booked_title');
                $body = get_option('ba_plus_mail_booked_body');
                $body = ba_plus_format_mail($body, $waiting->start_date, $waiting->end_date, $waiting->title, $user);
                $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ACADEMIE FRANCAISE DE PILATES <sarah.portiche@academie-pilates.com>');
                wp_mail($to, $subject, $body, $headers);
            }
        }
    }
}
