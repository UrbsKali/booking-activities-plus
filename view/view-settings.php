<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

function ba_plus_settings_init()
{

	register_setting('pluginPage', 'ba_plus_settings');

	add_settings_section(
		'ba_plus_pluginPage_section',
		__('', 'booking-activities-plus'),
		'ba_plus_settings_section_callback',
		'pluginPage'
	);

	add_settings_field(
		'ba_plus_text_field_0',
		__('Mail pour document expiré', 'booking-activities-plus'),
		'ba_plus_text_field_0_render',
		'pluginPage',
		'ba_plus_pluginPage_section'
	);

	add_settings_field(
		'ba_plus_textarea_field_1',
		__('', 'booking-activities-plus'),
		'ba_plus_textarea_field_1_render',
		'pluginPage',
		'ba_plus_pluginPage_section'
	);

	add_settings_field(
		'ba_plus_text_field_2',
		__('Mail pour liste d\'attente', 'booking-activities-plus'),
		'ba_plus_text_field_2_render',
		'pluginPage',
		'ba_plus_pluginPage_section'
	);

	add_settings_field(
		'ba_plus_textarea_field_3',
		__('', 'booking-activities-plus'),
		'ba_plus_textarea_field_3_render',
		'pluginPage',
		'ba_plus_pluginPage_section'
	);

	add_settings_field(
		'ba_plus_text_field_4',
		__('Mail pour annulation', 'booking-activities-plus'),
		'ba_plus_text_field_4_render',
		'pluginPage',
		'ba_plus_pluginPage_section'
	);

	add_settings_field(
		'ba_plus_textarea_field_5',
		__('', 'booking-activities-plus'),
		'ba_plus_textarea_field_5_render',
		'pluginPage',
		'ba_plus_pluginPage_section'
	);

	add_settings_field(
		'ba_plus_text_field_6',
		__('Mail pour quota d\'annulation faible', 'booking-activities-plus'),
		'ba_plus_text_field_6_render',
		'pluginPage',
		'ba_plus_pluginPage_section'
	);

	add_settings_field(
		'ba_plus_textarea_field_7',
		__('', 'booking-activities-plus'),
		'ba_plus_textarea_field_7_render',
		'pluginPage',
		'ba_plus_pluginPage_section'
	);

	add_settings_field(
		'ba_plus_text_field_8',
		__('Paramètres', 'booking-activities-plus'),
		'ba_plus_text_field_8_render',
		'pluginPage',
		'ba_plus_pluginPage_section'
	);



}


function ba_plus_text_field_0_render()
{
	?>
	<label for="ba_plus_mail_certi_expire_title">Titre</label><br>
	<input type="text" name="ba_plus_mail_certi_expire_title"
		value="<?php echo get_option('ba_plus_mail_certi_expire_title'); ?>"></input><br>
	<?php

}


function ba_plus_textarea_field_1_render()
{

	?>
	<label for="ba_plus_mail_certi_expire_body">Corps</label><br>
	<textarea name="ba_plus_mail_certi_expire_body" rows="10"
		cols="50"><?php echo get_option('ba_plus_mail_certi_expire_body'); ?></textarea><br>

	<?php

}


function ba_plus_text_field_2_render()
{
	?>
	<label for="ba_plus_mail_waiting_list_title">Titre</label><br>
	<input type="text" name="ba_plus_mail_waiting_list_title"
		value="<?php echo get_option('ba_plus_mail_waiting_list_title', "Vous êtes toujours dans la file d'attente"); ?>"></input><br>

	<?php

}


function ba_plus_textarea_field_3_render()
{

	?>
	<label for="ba_plus_mail_waiting_list_body">Corps</label><br>
	<textarea name="ba_plus_mail_waiting_list_body" rows="10"
		cols="50"><?php echo get_option('ba_plus_mail_waiting_list_body', "Bonjour %user%, \nCe mail à pour but des vous rappeler votre mise en file d'attente pour %event%\nA Bientôt"); ?></textarea><br>

	<?php

}


function ba_plus_text_field_4_render()
{

	?>
	<label for="ba_plus_mail_cancel_title">Titre</label><br>
	<input type="text" name="ba_plus_mail_cancel_title"
		value="<?php echo get_option('ba_plus_mail_cancel_title', "Scéance annulée"); ?>"></input><br>
	<?php

}


function ba_plus_textarea_field_5_render()
{
	?>
	<label for="ba_plus_mail_cancel_body">Corps</label><br>
	<textarea name="ba_plus_mail_cancel_body" rows="10"
		cols="50"><?php echo get_option('ba_plus_mail_cancel_body', "Bonjour %user%, \nL'évènement %event% à été annulé par manque de participant\nVeuillez nous excuser du dérangement"); ?></textarea><br>

	<?php

}


function ba_plus_text_field_6_render()
{

	?>
	<label for="ba_plus_mail_tree_cancel_left_title">Titre</label><br>
	<input type="text" name="ba_plus_mail_tree_cancel_left_title"
		value="<?php echo get_option('ba_plus_mail_tree_cancel_left_title', "Plus que trois annulations"); ?>"></input><br>
	<?php

}


function ba_plus_textarea_field_7_render()
{
	?>
	<label for="ba_plus_mail_tree_cancel_left_body">Corps</label><br>
	<textarea name="ba_plus_mail_tree_cancel_left_body" rows="10"
		cols="50"><?php echo get_option('ba_plus_mail_tree_cancel_left_body', "Bonjour %user%, \nCe mail à pour but de vous informer qu'il ne vous reste plus que 3 annulations gratuites\nA Bientôt"); ?></textarea><br>

	<?php

}


function ba_plus_text_field_8_render()
{
	?>

	<label for="ba_plus_refund_delay">Pré avis minimium d'annulation gratuite (en heures)</label>
	<input type="number" name="ba_plus_refund_delay" value="<?php echo get_option('ba_plus_refund_delay'); ?>">
	<?php

}


function ba_plus_settings_section_callback()
{

	echo __('', 'booking-activities-plus');

}


function ba_plus_settings_page()
{

	echo do_shortcode( "[bap_settings]" );

}