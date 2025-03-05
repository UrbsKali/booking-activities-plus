<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Initialize the settings for the plugin.
 */
function ba_plus_settings_init()
{

	register_setting('pluginPage', 'ba_plus_settings');
}


function ba_plus_settings_section_callback()
{

	echo __('', 'booking-activities-plus');

}

/**
 * Display the settings page for the plugin.
 */
function ba_plus_settings_page()
{

	echo do_shortcode( "[bap_settings]" );

}