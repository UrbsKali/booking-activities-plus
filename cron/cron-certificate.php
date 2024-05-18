<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'bookacti_cron_check_certif', 'ba_plus_check_certificate_expiration' );
add_action( 'bookacti_cron_check_attest', 'ba_plus_check_attestation_expiration' );

if ( ! wp_next_scheduled( 'bookacti_cron_check_certif' ) ) {
    wp_schedule_event( time(), 'hourly', 'bookacti_cron_check_certif' );
}   
if ( ! wp_next_scheduled( 'bookacti_cron_check_attest' ) ) {
    wp_schedule_event( time(), 'hourly', 'bookacti_cron_check_attest' );
}   


/**
 * Check all users, and send them a mail if their certificate expire in less than 60 days
 */
function ba_plus_check_certificate_expiration(){
    echo "Checking for certificate expiration<br>";
    global $wpdb;
    $users = get_users();
    $today = new DateTime();
    $today = $today->format('Y-m-d');
    $interval_certif = array(
        'start' => $today,
        'end' => date('Y-m-d',strtotime('+60 day'))
    );
    foreach($users as $user){
        $user_id = $user->ID;
        $expire_date = get_user_meta($user_id, 'certificat_expire_date', true);
        $send_mail = get_user_meta( $user_id, 'send_mail_certif_expire', true );
        // check if null 
        if ( $expire_date == '' || $send_mail == '' ){
            continue;
        }
        if ( $send_mail == 'true' ){
            continue;
        }

        $expire_date = new DateTime($expire_date);
        $expire_date = $expire_date->format('Y-m-d');
        if ( $expire_date >= $interval_certif['start'] && $expire_date <= $interval_certif['end'] ){
            $to = $user->user_email;
            $subject = get_option( 'ba_plus_mail_certi_expire_title' );
            $subject = str_replace( '%doc%', "Certificat", $subject );
            $body = get_option( 'ba_plus_mail_certi_expire_body' );
            $body = str_replace( '%doc%', "Certificat", $body );
            $body = str_replace( '%user%', $user->display_name, $body );
            $body = str_replace( '%expire_date%', date_diff(new DateTime($today), new DateTime($expire_date))->days, $body );
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail( $to, $subject, $body, $headers );
            update_user_meta( $user_id, 'send_mail', 'true' );
        }
    }
}

/**
 * Check all users, and send them a mail if their certificate expire in less than 7 days
 */
function ba_plus_check_attestation_expiration(){
    echo "Checking for attestation expiration<br>";
    global $wpdb;
    $users = get_users();
    $today = new DateTime();
    $today = $today->format('Y-m-d');
    $interval_attes = array(
        'start' => $today,
        'end' => date('Y-m-d',strtotime('+7 day'))
    );
    foreach($users as $user){
        $user_id = $user->ID;
        $expire_date = get_user_meta($user_id, 'attestation_expire_date', true);
        $send_mail = get_user_meta( $user_id, 'send_mail_attes_expire', true );
        // check if null 
        if ( $expire_date == '' || $send_mail == '' ){
            continue;
        }
        if ( $send_mail == 'true' ){
            continue;
        }
        
        $expire_date = new DateTime($expire_date);
        $expire_date = $expire_date->format('Y-m-d');
        if ( $expire_date >= $interval_attes['start'] && $expire_date <= $interval_attes['end'] ){
            $to = $user->user_email;
            $subject = get_option( 'ba_plus_mail_certi_expire_title' );
            $subject = str_replace( '%doc%', "Attestation", $subject );
            $body = get_option( 'ba_plus_mail_certi_expire_body' );
            $body = str_replace( '%doc%', "Attestation", $body );
            $body = str_replace( '%user%', $user->display_name, $body );
            $body = str_replace( '%expire_date%', date_diff(new DateTime($today), new DateTime($expire_date))->days, $body );

            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail( $to, $subject, $body, $headers );
            update_user_meta( $user_id, 'send_mail', 'true' );
        }
        
    }
}