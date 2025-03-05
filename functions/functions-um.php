<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter('um_account_page_default_tabs_hook', 'ba_plus_booking_tab', 100 );
/**
 * Add a booking tab to a tabs collection.
 *
 * This function adds or modifies a booking-related tab within the tabs array,
 * likely used in a user profile or dashboard context.
 *
 * @param array $tabs The existing tabs array to modify.
 * @return array Modified tabs array with the booking tab included.
 */
function ba_plus_booking_tab( $tabs ) {
	$tabs[800]['bookingtab']['icon'] = 'um-faicon-pencil';
	$tabs[800]['bookingtab']['title'] = 'Réservations';
	$tabs[800]['bookingtab']['custom'] = true;
    $tabs[800]['bookingtab']['show_button'] = false;
	return $tabs;
}
	

add_action('um_account_tab__bookingtab', 'um_account_tab__bookingtab');
/**
 * Add or modify a booking tab in Ultimate Member account area.
 *
 * This function handles the integration between Booking Activities Plus and Ultimate Member plugin,
 * creating or modifying the booking tab in user account area.
 * 
 * @param array $info Tab information array provided by Ultimate Member.
 * @return array Modified tab information.
 */
function um_account_tab__bookingtab( $info ) {
	global $ultimatemember;
	extract( $info );

	$output = $ultimatemember->account->get_tab_output('bookingtab');
	if ( $output ) { echo $output; }
}

/* Finally we add some content in the tab */
add_filter('um_account_content_hook_bookingtab', 'um_account_content_hook_bookingtab');
/**
 * Hooks into Ultimate Member account page to display booking tab content.
 * 
 * This function handles the content display for the booking tab in the user's account area
 * when using the Ultimate Member plugin.
 * 
 * @param string $output The current output HTML for the account tab content.
 * @return string Modified output HTML with booking information.
 * 
 * @hook um_account_content_hook_bookingtab
 */
function um_account_content_hook_bookingtab( $output ){
    wp_enqueue_script('ba-wl-btn');

	ob_start();
	?>
		
	<div class="um-field">
		
		<!-- Here goes your custom content -->
        <?php
        $user_id = um_profile_id();
        if ( ! $user_id ) {
            return;
        }
        // affiche les shortcodes pour avoir les informations de l'utilisateur (résa, listes d'attente et passes)
        echo "<br><h2>Réservations</h2><br>";
        echo do_shortcode( '[bookingactivities_list columns="status,events,actions" user_id='. $user_id . ']' );
        echo "<br><h2>Files d'attentes</h2><br>";
        echo do_shortcode( '[bookingactivities_waitinglist columns="events,actions" user_id='. $user_id . ']' );
        echo "<br><h2>Forfaits</h2><br>";
        echo do_shortcode( '[bookingactivities_passes user_id='. $user_id . ']');
        echo do_shortcode( "[bookingactivities_certificate user_id='".$user_id."']" );
        echo "<hr>";
        echo do_shortcode( "[bookingactivities_cancel_balance user_id='".$user_id."']" );

        ?>
		
	</div>		
		
	<?php
		
	$output .= ob_get_contents();
	ob_end_clean();
	return $output;
}



/**
 * Add info to the bottom of the user profile page
 * Mostly used to display the user's bookings, waiting lists and passes
 * on the admin frontend
 */
function ba_plus_admin_booking_tab( $args ) {
    $user_id = um_profile_id();
    if ( ! $user_id ) {
        return;
    }
    // affiche les shortcodes pour avoir les informations de l'utilisateur (résa, listes d'attente et passes)
    echo "<br><h2>Nombre d'annulations restantes</h2><br>";
    echo do_shortcode( "[bookingactivities_cancel_balance user_id='".$user_id."']" );
    echo "<br><h2>Réservations</h2><br>";
    echo do_shortcode( '[bookingactivities_list columns="status,events" user_id='. $user_id . ']' );
    echo "<br><h2>Files d'attentes</h2><br>";
    echo do_shortcode( '[bookingactivities_waitinglist columns="events,actions" user_id='. $user_id . ']' );
    echo "<br><h2>Forfaits</h2><br>";
    echo do_shortcode( '[bookingactivities_passes user_id='. $user_id . ']');
    echo do_shortcode( "[bap_forfaits_admin user_id='".$user_id."']" );
}
add_action( "um_profile_content_main_default", "ba_plus_admin_booking_tab", 10, 1 );