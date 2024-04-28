<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Check all users, and send them a mail if their certificate expire in less than 60 days
 */
function ba_plus_check_certificate_expiration(){
    echo "Checking for certificate expiration\n";
    global $wpdb;
    $users = get_users();
    $today = new DateTime();
    $today = $today->format('Y-m-d');
    $interval_certif = array(
        'start' => $today,
        'end' => date('Y-m-d',strtotime('+60 day'))
    );
    $interval_attes = array(
        'start' => $today,
        'end' => date('Y-m-d',strtotime('+7 day'))
    );
    foreach($users as $user){
        $user_id = $user->ID;
        $doc_type = get_user_meta($user_id, 'doc_type', true);
        $expire_date = get_user_meta($user_id, 'expire_date', true);
        $send_mail = get_user_meta( $user_id, 'send_mail', true );
        if ( $send_mail == 'true' ){
            continue;
        }
        if ( $doc_type == 'certificat' ){
            $expire_date = new DateTime($expire_date);
            $expire_date = $expire_date->format('Y-m-d');
            if ( $expire_date >= $interval_certif['start'] && $expire_date <= $interval_certif['end'] ){
                $to = $user->user_email;
                $subject = 'Expiration de votre certificat';
                $body = 'Votre certificat expire dans moins de 60 jours. Pensez à le renouveler.';
                $headers = array('Content-Type: text/html; charset=UTF-8');
                wp_mail( $to, $subject, $body, $headers );
                update_user_meta( $user_id, 'send_mail', 'true' );
            }
        }
    }
}