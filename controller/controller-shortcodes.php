<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

add_shortcode('bookingactivities_waitinglist', 'ba_plus_shortcode_waiting_list');
add_shortcode('bookingactivities_certificate', 'ba_plus_shortcode_certificate');
add_shortcode( 'boockinactivities_cancel_balance', 'ba_plus_shortcode_cancel_balance' );


function ba_plus_shortcode_waiting_list($raw_atts = array(), $content = null, $tag = '')
{
	// Normalize attribute keys, lowercase
	$raw_atts = array_change_key_case((array) $raw_atts, CASE_LOWER);
	$atts = $raw_atts;

	// If the user is not logged in
	if (!is_user_logged_in()) {
		// Check if the user is passed to the URL
		$user_email = '';
		if (!empty($_REQUEST['user_auth_key'])) {
			$user_decrypted = bookacti_decrypt(sanitize_text_field($_REQUEST['user_auth_key']), 'user_auth');
			if (is_email(sanitize_email($user_decrypted))) {
				$user_email = sanitize_email($user_decrypted);
			}
			if ($user_email && (empty($raw_atts['user_id']) || (!empty($raw_atts['user_id']) && $raw_atts['user_id'] === 'current'))) {
				$atts['in__user_id'] = array($user_email);
				$user = get_user_by('email', $user_email);
				if ($user) {
					$atts['in__user_id'][] = intval($user->ID);
				}
				unset($atts['user_id']);
			}
		}

		// If a login form is defined, show it instead of the booking list
		if (!$user_email && !empty($raw_atts['login_form'])) {
			$atts['form'] = $raw_atts['login_form'];
			return bookacti_shortcode_login_form($atts, $content, $tag);
		}
	}

	// Sanitize the attributes
	$atts = bookacti_sanitize_booking_list_shortcode_attributes($atts);

	$waiting_list = '';
	if ($atts !== false) {
		$templates = array();
		if (isset($atts['templates'])) {
			$templates = $atts['templates'];
			unset($atts['templates']);
		}

		// Format booking filters
		$filters = bookacti_format_booking_filters($atts);

		// Allow to filter by any template
		if (!empty($templates) && is_array($templates)) {
			$filters['templates'] = $templates;
		}

		// Let third party change the filters
		$filters = apply_filters('bookacti_user_booking_list_booking_filters', $filters, $atts, $content);

		$waiting_list = ba_plus_create_user_waiting_list($filters, $atts['columns'], $atts['per_page']);
	}



	return apply_filters('bookacti_shortcode_' . $tag . '_output', $waiting_list, $raw_atts, $content);
	//return "uysdvf";
}


/**
 * Show current certificate / Attestation
 */
function ba_plus_shortcode_certificate($raw_atts = array(), $content = null, $tag = '')
{
	// Check if user is logged in
	if (!is_user_logged_in()) {
		return bookacti_shortcode_login_form($raw_atts, $content, $tag);
	}
	$user_id = get_current_user_id();

	if (!empty($raw_atts['user_id'])) {
		$user_id = intval($raw_atts['user_id']);
	}

	$certificate_expire_date = get_user_meta($user_id, 'certif_med', true);
	$attestation_expire_date = get_user_meta($user_id, 'attest_med', true);

	$error_certif = false;
	$error_attest = false;

	$msg = '';
	if (empty($certificate_expire_date)) {
		$msg .= '<div class="ba-error">' . __('Vous n\'avez aucun certificat médical d\'enregistré', 'ba-plus') . '</div>';
		$error_attest = true;
	}
	if (empty($attestation_expire_date)) {
		$msg .= '<div class="ba-error">' . __('Vous n\'avez aucune attestation d\'enregistré', 'ba-plus') . '</div>';
		$error_certif = true;
	}


	$date = datefmt_create(
		"fr-FR",
		IntlDateFormatter::FULL,
		IntlDateFormatter::NONE,
		'Europe/Paris',
		IntlDateFormatter::GREGORIAN
	);

	// add warning if attestation is expired
	$str_date_att = datefmt_format($date, strtotime($attestation_expire_date));
	if (date('Y-m-d', strtotime($attestation_expire_date)) < date('Y-m-d') && !$error_attest) {
		$msg .= '<div class="ba-error">' . __('Votre attestation est expiré depuis le ', 'ba-plus') . $str_date_att . '</div>';
		$error_attest = true;
	}
	// add warning if certificate is expired
	$str_date_certif = datefmt_format($date, strtotime($certificate_expire_date));
	if (date('Y-m-d', strtotime($certificate_expire_date)) < date('Y-m-d') && !$error_certif) {
		$msg .= '<div class="ba-error">' . __('Votre certificat est expiré depuis le ', 'ba-plus') . $str_date_certif . '</div>';
		$error_certif = true;
	}

	$msg .= '<div class="ba-certificate">';
	if (!$error_certif) {
		$msg .= '<div class="ba-certificate-item">';
		$msg .= '<div class="ba-certificate-title">' . __('Certificat médical', 'ba-plus') . '</div>';
		$msg .= '<div class="ba-certificate-date">' . __('Date d\'expiration : ', 'ba-plus') . $str_date_certif . '</div>';
		$msg .= '</div>';
	}
	if (!$error_attest) {
		$msg .= '<div class="ba-certificate-item">';
		$msg .= '<div class="ba-certificate-title">' . __('Attestation', 'ba-plus') . '</div>';
		$msg .= '<div class="ba-certificate-date">' . __('Date d\'expiration : ', 'ba-plus') . $str_date_att . '</div>';
		$msg .= '</div>';
	}
	$msg .= '</div>';
	return $msg;
}

function ba_plus_shortcode_cancel_balance($raw_atts = array(), $content = null, $tag = ''){
	// Check if user is logged in
	if (!is_user_logged_in()) {
		return bookacti_shortcode_login_form($raw_atts, $content, $tag);
	}

	$user_id = get_current_user_id();

	if (!empty($raw_atts['user_id'])) {
		$user_id = intval($raw_atts['user_id']);
	}

	$balance = get_user_meta($user_id, 'nb_cancel_left', true);
	if (empty($balance)) {
		$balance = 0;
	}
	$message = '<div class="ba-balance">';
	$message .= '<div class="ba-balance-amount">' . __('Nombre d\'annulation gratuite restante : ', 'ba-plus') . $balance . '</div>';
	$message .= '</div>';
	return $message;
}