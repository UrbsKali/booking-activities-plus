<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

add_shortcode('bookingactivities_waitinglist', 'ba_plus_shortcode_waiting_list');
add_shortcode('bookingactivities_certificate', 'ba_plus_shortcode_certificate');
add_shortcode('bookingactivities_cancel_balance', 'ba_plus_shortcode_cancel_balance');
add_shortcode('bookingactivities_planning', 'ba_plus_planning');
add_shortcode('bap_settings', 'ba_plus_admin_refund_delay');
add_shortcode('bap_forfaits_admin', 'ba_plus_admin_forfaits_admin');


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

	wp_enqueue_script('ba-wl-btn');


	return apply_filters('bookacti_shortcode_' . $tag . '_output', $waiting_list, $raw_atts, $content);
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
		$msg .= '<div class="ba-error">' . __('Vous n\'avez aucun certificat médical enregistré', 'ba-plus') . '</div>';
		$error_attest = true;
	}
	if (empty($attestation_expire_date)) {
		$msg .= '<div class="ba-error">' . __('Vous n\'avez aucune attestation enregistrée', 'ba-plus') . '</div>';
		$error_certif = true;
	}


	$date = datefmt_create(
		"fr-FR",
		IntlDateFormatter::FULL,
		IntlDateFormatter::NONE,
		'Europe/Paris',
		IntlDateFormatter::GREGORIAN
	);


	$timezone = new DateTimeZone('Europe/Paris');
    $today = new DateTime('now', $timezone);

	$certif_expire_date = new DateTime($certificate_expire_date, $timezone);
	$attest_expire_date = new DateTime($attestation_expire_date, $timezone);
    $certif_diff = date_diff($today, $certif_expire_date);
    $attest_diff = date_diff($today, $attest_expire_date);

	// add warning if attestation is expired
	$str_date_att = datefmt_format($date, $attest_expire_date);
	if ($attest_diff->invert == 1 && !$error_attest) {
		$msg .= '<div class="ba-error">' . __('Votre attestation est expiré depuis le ', 'ba-plus') . $str_date_att . '</div>';
		$error_attest = true;
	}
	// add warning if certificate is expired
	$str_date_certif = datefmt_format($date, $certif_expire_date);
	if ($certif_diff->invert == 1 && !$error_certif) {
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

function ba_plus_shortcode_cancel_balance($raw_atts = array(), $content = null, $tag = '')
{
	// Check if user is logged in
	if (!is_user_logged_in()) {
		return bookacti_shortcode_login_form($raw_atts, $content, $tag);
	}

	$user_id = get_current_user_id();

	// if user is admin, get set the user_id to the one passed in the shortcode, and make it editable
	if (current_user_can('manage_options') && !empty($raw_atts['user_id'])) {
		$user_id = intval($raw_atts['user_id']);

		wp_enqueue_script('ba-frontendadmin-settings');
		wp_enqueue_style('ba-popup-style');

		$message = '<input type="number" id="ba-cancel-balance" value="' . get_user_meta($user_id, 'nb_cancel_left', true) . '" data-user-id="' . $user_id . '">';
		$message .= '<br><button id="ba-cancel-balance-save">' . __('Enregistrer', 'ba-plus') . '</button>';
		return $message;
	}


	if (!empty($raw_atts['user_id'])) {
		$user_id = intval($raw_atts['user_id']);
	}

	$balance = get_user_meta($user_id, 'nb_cancel_left', true);
	if (empty($balance)) {
		$balance = 0;
	}
	$message = '<div class="ba-balance">';
	$message .= '<div class="ba-balance-amount">' . __('Nombre d\'annulations gratuites restantes : ', 'ba-plus') . $balance . '</div>';
	$message .= '</div>';
	return $message;
}

function ba_plus_planning($atts = array(), $content = null, $tag = '')
{
	// Check if user is admin
	if (!is_user_logged_in()) {
		return bookacti_shortcode_login_form($atts, $content, $tag);
	}
	$user_id = get_current_user_id();

	wp_enqueue_style('ba-planning-style');
	wp_enqueue_script('ba-planning');

	$planning = ba_plus_create_planning($atts);
	return $planning;
}

function ba_plus_admin_refund_delay($atts = array(), $content = null, $tag = '')
{
	// Check if user is admin
	if (!is_user_logged_in()) {
		return bookacti_shortcode_login_form($atts, $content, $tag);
	}
	$user_id = get_current_user_id();

	wp_enqueue_script('ba-admin-settings');
	wp_enqueue_style('ba-popup-style');

	$html = '<div class="ba-admin-settings">';
	$html .= '<div class="ba-admin-settings-title">' . __('Paramètres de remboursement', 'ba-plus') . '</div>';
	$html .= '<div class="ba-admin-settings-item">';
	$html .= '<label for="ba-admin-settings-refund-delay">' . __('Pré avis minimium d\'annulation gratuite', 'ba-plus') . '</label>';
	$html .= '<input type="number" id="ba-admin-settings-refund-delay" value="' . get_option('ba_plus_refund_delay', 0) . '">';
	$html .= '</div>';
	$html .= '</div>';
	// add a button to save the settings
	$html .= '<button id="ba-admin-settings-save">' . __('Enregistrer', 'ba-plus') . '</button>';

	return $html;
}

function ba_plus_admin_forfaits_admin($atts = array(), $content = null, $tag = '')
{
	// Check if user is admin
	if (!is_user_logged_in()) {
		return bookacti_shortcode_login_form($atts, $content, $tag);
	}

	if (!current_user_can('manage_options')) {
		return __('Vous n\'avez pas les droits pour accéder à cette page', 'ba-plus');
	}

	// get user_id from the shortcode
	$user_id = 0;
	if (!empty($atts['user_id'])) {
		$user_id = intval($atts['user_id']);
	} else {
		return __('Vous devez spécifier un utilisateur dans les attributs du shortcode', 'ba-plus');
	}


	wp_enqueue_script('ba-admin-settings');
	wp_enqueue_style('ba-popup-style');

	ob_start();

	//get the current user passes
	$passes = bapap_get_booking_passes(bapap_format_booking_pass_filters(array('user_id' => $user_id, 'active' => 1)));
	// get the first pass
	if (empty($passes)) {
		$pass_id = 'none';
	} else {
		foreach ($passes as $p) {
			$pass = $p;
			break;
		}
		$pass_id = $pass->pass_template_id;
	}



	$booking_pass_templates = bapap_get_booking_pass_templates(bapap_format_booking_pass_template_filters());
	$booking_pass_templates_options = array('none' => esc_html__('Aucun', 'booking-activities'));
	foreach ($booking_pass_templates as $booking_pass_template) {
		$booking_pass_templates_options[$booking_pass_template->id] = !empty($booking_pass_template->title) ? apply_filters('bookacti_translate_text', $booking_pass_template->title) : sprintf(esc_html__('Booking pass template #%s', 'ba-prices-and-credits'), $booking_pass_template->id);
	}

	$current_booking_pass_template_ids = isset($_GET['booking_pass_template_id']) ? $_GET['booking_pass_template_id'] : 0;
	$args = array(
		'name'     => 'booking_pass_template_id',
		'id'       => 'bapap-booking-passes-filter-booking-pass-template',
		'type'     => 'select',
		'multiple' => 'no',
		'class'    => 'bookacti-select2-no-ajax',
		'options'  => $booking_pass_templates_options,
		'value'    => $pass_id
	);
	bookacti_display_field($args);

	$output = ob_get_contents();
	ob_end_clean();

	// add a date picker to select the start date of the pass
	$output .= '<div class="ba-admin-settings-item">';
	$output .= '<label for="ba-admin-settings-pass-start-date">' . __('Date de début du forfait', 'ba-plus') . '</label>';
	$output .= '<input type="date" id="ba-plus-settings-pass-start-date">';
	$output .= '</div>';

	$output .= '<br><button id="ba-plus-admin-pass-add" data-user-id="' . $user_id . '">' . __('Ajouter un forfait', 'ba-plus') . '</button>';

	return $output;
}
