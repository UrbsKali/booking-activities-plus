<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add a new function to the waiting list
function ba_plus_update_waiting_list($event_id, $start, $end)
{
    // auto register the user if there is a spot available
    $booked = ba_plus_check_if_event_is_full($event_id, $start, $end);
    if ($booked) {
        return;
    }

    // get first user in the waiting list
    $waiting = ba_plus_get_event_waiting_list($event_id, $start, $end);
    if (!$waiting || empty($waiting) || !is_array($waiting)) {
        return;
    }

    foreach ($waiting as $item) {
        // get user
        $user = get_user_by('id', $item->user_id);
        // check user balance 
        $filters = array(
            'user_id' => $user->ID,
            'active' => 1
        );
        $filters = bapap_format_booking_pass_filters($filters);
        $pass = bapap_get_booking_passes($filters);

        if (empty($pass)) {
            continue;
        }

        // sort the booking pass by expiration date, shorter first
        usort($pass, function ($a, $b) {
            return strtotime($a->expiration_date) - strtotime($b->expiration_date);
        });

        // remove empty booking pass from the list
        $pass = array_filter($pass, function ($p) {
            return $p->credits_current > 0;
        });

        if (empty($pass)) {
            // send mail to admin
            $to = 'urbain.lantres@gmail.com';
            $subject = "Debug: file d'attente";
            $body = "La file d'attente " . $waiting->title . " (" . $waiting->start . ") n'a pas pu être traitée. L'utilisateur " . $user->display_name . " n'a pas de crédit.";
            wp_mail($to, $subject, $body);
            // delete the waiting list

            // send mail to user
            $user = get_user_by('id', $user->ID);
            $to = $user->user_email;
            $subject = 'Plus de crédit pour l\'événement ' . $item->title;
            $body = 'Bonjour ' . $user->display_name . '<br>Nous vous avons désinscrit de votre place sur la liste d\'attente du cours ' . $item->title . ' (' . $item->start . '), car vous n\'avez plus d\'unité disponible.<br>Merci pour votre compréhension.<br>Cordialement,<br>L\'Espace Pilates de la Vallée de Chevreuse';
            $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ACADEMIE FRANCAISE DE PILATES <sarah.portiche@academie-pilates.com>');
            wp_mail($to, $subject, $body, $headers);



            ba_plus_remove_waiting_list_by_event_id($event_id, $user->ID, $start, $end);

            continue;
        }

        ba_plus_remove_waiting_list_by_event_id($event_id, $user->ID, $start, $end);
        // add the user to the event
        $booking_data = bookacti_sanitize_booking_data(
            array(
                'user_id' => $user->ID,
                'form_id' => $item->template_id,
                'event_id' => $item->event_id,
                'event_start' => $start,
                'event_end' => $end,
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

            // Send email to user
            $user = get_user_by('id', $user->ID);
            $to = $user->user_email;
            $subject = get_option('ba_plus_mail_booked_title');
            $body = get_option('ba_plus_mail_booked_body');
            $body = ba_plus_format_mail($body, $start, $end, $item->title, $user);
            $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ACADEMIE FRANCAISE DE PILATES <sarah.portiche@academie-pilates.com>');
            wp_mail($to, $subject, $body, $headers);

            // Send SMS
            $phone = banp_get_user_phone_number($user->ID);
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
            }
            // add to the log
            $log_data = array(
                'credits_delta' => '-1',
                'credits_current' => $pass[0]->credits_current,
                'credits_total' => $pass[0]->credits_total,
                'reason' => "Inscription automatique (via liste d'attente) - " . $item->title . " (" . $start . ")",
                'context' => 'updated_from_server',
                'lang_switched' => 1
            );
            bapap_add_booking_pass_log($pass[0]->id, $log_data);

            $updated = bookacti_update_metadata('booking', $booking_id, array('booking_pass_id' => $pass[0]->id, 'booking_pass_credits' => 1));
            if (!$updated) {
                wp_mail("urbain.jeu@gmail.com", "Erreur lors de l'inscription automatique", "L'inscription automatique de " . $user->display_name . " à l'événement " . $item->title . " (" . $start . ") a eu un problème, aucune donnée lié au passe n'ont pu être sauvegardé ." . print_r($booking_data, true));
            }
        } else {
            // send mail to admin
            wp_mail("urbain.jeu@gmail.com", "Erreur lors de l'inscription automatique", "L'inscription automatique de " . $user->display_name . " à l'événement " . $item->title . " (" . $start . ") a échoué.");
        }
        // leave the loop if a user has been added
        return true;
    }
}


function ba_plus_filter_cancel_booking($new_booking, $new_state, $args)
{
    if ($new_state != "cancelled" && !($new_state == "refunded" && $args['is_admin'])) {
        wp_mail("urbain.jeu@gmail.com", "Debug: not updating WL", "Booking cancelled: " . print_r($new_booking, true) . " - " . print_r($args, true));
        return;
    }
    wp_mail("urbain.jeu@gmail.com", "Debug: updating WL", "Booking cancelled: " . print_r($new_booking, true) . " - " . print_r($args, true));
    ba_plus_update_waiting_list($new_booking->event_id, $new_booking->event_start, $new_booking->event_end);
}
add_action("bookacti_booking_state_changed", "ba_plus_filter_cancel_booking", 10, 3);
