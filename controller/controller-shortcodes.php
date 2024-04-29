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

		$waiting_list = bookacti_create_user_waiting_list($filters, $atts['columns'], $atts['per_page']);
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
	$doc_type = get_user_meta(get_current_user_id(), 'doc_type', true);
	$expire_date = get_user_meta(get_current_user_id(), 'expire_date', true);

	// If the user has no certificate, return an error message
	if (empty($doc_type) || empty($expire_date)) {
		return '<div class="ba-error">' . __('Vous n\'avez pas de certificat ou d\'attestation enregistré.', 'ba-plus') . '</div>';
	}
	if ($doc_type == 'certificat') {
		$doc_type = __('Certificat', 'ba-plus');
	} else {
		$doc_type = __('Attestation', 'ba-plus');
	}
	$date = datefmt_create(
		"fr-FR",
		IntlDateFormatter::FULL,
		IntlDateFormatter::NONE,
		'Europe/Paris',
		IntlDateFormatter::GREGORIAN
	);
	$message = '';
	// add warning if certificate is expired
	$str_date = datefmt_format($date, strtotime($expire_date));
	if (strtotime($expire_date) < strtotime('now')) {
		$message = '<div class="ba-error">' . __('Votre certificat est expiré depuis le ', 'ba-plus') . $str_date . '</div>';
	}
	$message .= '<div class="ba-certificate">';
	$message .= '<div class="ba-certificate-type">' . __('Type de document : ', 'ba-plus') . $doc_type . '</div>';
	$message .= '<div class="ba-certificate-expire">' . __('Date d\'expiration : ', 'ba-plus') . $str_date . '</div>';
	$message .= '</div>';
	return $message;
}

function ba_plus_shortcode_cancel_balance($raw_atts = array(), $content = null, $tag = ''){
	// Check if user is logged in
	if (!is_user_logged_in()) {
		return bookacti_shortcode_login_form($raw_atts, $content, $tag);
	}
	$balance = get_user_meta(get_current_user_id(), 'nb_cancel_left', true);
	$message = '<div class="ba-balance">';
	$message .= '<div class="ba-balance-amount">' . __('Nombre d\'annulation gratuite restante : ', 'ba-plus') . $balance . '</div>';
	$message .= '</div>';
	return $message;
}