<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter('um_account_page_default_tabs_hook', 'my_custom_tab_in_um', 100 );
function my_custom_tab_in_um( $tabs ) {
	$tabs[800]['mytab']['icon'] = 'um-faicon-pencil';
	$tabs[800]['mytab']['title'] = 'Réservations';
	$tabs[800]['mytab']['custom'] = true;
	return $tabs;
}
	
/* make our new tab hookable */

add_action('um_account_tab__mytab', 'um_account_tab__mytab');
function um_account_tab__mytab( $info ) {
	global $ultimatemember;
	extract( $info );

	$output = $ultimatemember->account->get_tab_output('mytab');
	if ( $output ) { echo $output; }
}

/* Finally we add some content in the tab */

add_filter('um_account_content_hook_mytab', 'um_account_content_hook_mytab');
function um_account_content_hook_mytab( $output ){
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
        echo "<br><h2>File d'attente</h2><br>";
        echo do_shortcode( '[bookingactivities_waitinglist columns="events,actions" user_id='. $user_id . ']' );
        echo "<br><h2>Forfaits</h2><br>";
        echo do_shortcode( '[bookingactivities_passes user_id='. $user_id . ']');
        echo do_shortcode( "[bookingactivities_certificate user_id='".$user_id."']" );
        echo "<hr>";
        echo do_shortcode( "[boockinactivities_cancel_balance user_id='".$user_id."']" );

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
function ba_plus_test2( $args ) {
    $user_id = um_profile_id();
    if ( ! $user_id ) {
        return;
    }
    // affiche les shortcodes pour avoir les informations de l'utilisateur (résa, listes d'attente et passes)
    echo "<br><h2>Réservations</h2><br>";
    echo do_shortcode( '[bookingactivities_list columns="events" user_id='. $user_id . ']' );
    echo "<br><h2>File d'attente</h2><br>";
    echo do_shortcode( '[bookingactivities_waitinglist columns="events,actions" user_id='. $user_id . ']' );
    echo "<br><h2>Forfaits</h2><br>";
    echo do_shortcode( '[bookingactivities_passes user_id='. $user_id . ']');
}
add_action( "um_profile_content_main_default", "ba_plus_test2", 10, 1 );