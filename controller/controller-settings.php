<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Save admin settings
 */
function ba_plus_save_settings() {
    $ba_plus_mail_certi_expire_title = $_POST['ba_plus_mail_certi_expire_title'];
    $ba_plus_mail_certi_expire_body = $_POST['ba_plus_mail_certi_expire_body'];

    $ba_plus_mail_waiting_list_title = $_POST['ba_plus_mail_waiting_list_title'];
    $ba_plus_mail_waiting_list_body = $_POST['ba_plus_mail_waiting_list_body'];

    $ba_plus_mail_cancel_title = $_POST['ba_plus_mail_cancel_title'];
    $ba_plus_mail_cancel_body = $_POST['ba_plus_mail_cancel_body'];

    $ba_plus_mail_tree_cancel_left_body = $_POST['ba_plus_mail_tree_cancel_left_body'];
    $ba_plus_mail_tree_cancel_left_title = $_POST['ba_plus_mail_tree_cancel_left_title'];


    $ba_plus_refund_delay = intval($_POST['ba_plus_refund_delay']);


    if (isset($ba_plus_mail_certi_expire_title)) {
        update_option('ba_plus_mail_certi_expire_title', $ba_plus_mail_certi_expire_title);
    }
    if (isset($ba_plus_mail_certi_expire_body)) {
        update_option('ba_plus_mail_certi_expire_body', $ba_plus_mail_certi_expire_body);
    }
    if (isset($ba_plus_mail_waiting_list_title)) {
        update_option('ba_plus_mail_waiting_list_title', $ba_plus_mail_waiting_list_title);
    }
    if (isset($ba_plus_mail_waiting_list_body)) {
        update_option('ba_plus_mail_waiting_list_body', $ba_plus_mail_waiting_list_body);
    }
    if (isset($ba_plus_mail_cancel_title)) {
        update_option('ba_plus_mail_cancel_title', $ba_plus_mail_cancel_title);
    }
    if (isset($ba_plus_mail_cancel_body)) {
        update_option('ba_plus_mail_cancel_body', $ba_plus_mail_cancel_body);
    }
    if (isset($ba_plus_mail_tree_cancel_left_body)) {
        update_option('ba_plus_mail_tree_cancel_left_body', $ba_plus_mail_tree_cancel_left_body);
    }
    if (isset($ba_plus_mail_tree_cancel_left_title)) {
        update_option('ba_plus_mail_tree_cancel_left_title', $ba_plus_mail_tree_cancel_left_title);
    }
    if (isset($ba_plus_refund_delay)) {
        update_option('ba_plus_refund_delay', $ba_plus_refund_delay);
    }

    wp_send_json( array('success' => true) );
}
add_action('wp_ajax_nopriv_baPlusSaveSettings', 'ba_plus_save_settings');
add_action('wp_ajax_baPlusSaveSettings', 'ba_plus_save_settings');
