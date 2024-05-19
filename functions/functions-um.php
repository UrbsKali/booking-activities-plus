<?php
if (!defined('ABSPATH')) {
    exit;
}

/* First we need to extend main profile tabs */

add_filter('um_profile_tabs', 'add_custom_profile_tab', 1000 );
function add_custom_profile_tab( $tabs ) {

	$tabs['mycustomtab'] = array(
		'name' => 'My custom tab',
		'icon' => 'um-faicon-comments',
	);
		
	return $tabs;
		
}

/* Then we just have to add content to that tab using this action */

add_action('um_profile_content_mycustomtab_default', 'um_profile_content_mycustomtab_default');
function um_profile_content_mycustomtab_default( $args ) {
	echo 'Hello world!';
}