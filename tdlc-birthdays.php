<?php
/*
Plugin Name: TDLC Birthdays
Plugin URI: https://buddyuser.com/plugin-tdlc-birthdays/
Description: This simple Buddypress Widget uses a Birthday field from Buddypress extended profile to display a list of upcoming birthdays of the user's friends. English, French, German, Hungarian, Italian, Japanese, Polish, Russian and Spanish languages available.
Author: Venutius
Version: 1.1.0
Author URI: http://www.buddyuser.com
License: Licensed under the The GNU General Public License 3.0 (GPL) http://www.gnu.org/licenses/gpl.html
*/
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;
/* WP 2.8 is required for the multi-instance widget */
global $wp_version;
if((float)$wp_version >= 4.8){ 

	/* Only load code that needs BuddyPress to run once BP is loaded and initialized. */
	function tdlc_load_tdlcbirthdays() {
		require( dirname( __FILE__ ) . '/core.php' );
	}
	add_action( 'bp_include', 'tdlc_load_tdlcbirthdays' );
	 
}
require( dirname( __FILE__ ) . '/includes/settings-class.php' );
require( dirname( __FILE__ ) . '/includes/tdlc-mail-send-class.php' );

/**
 * Actions performed on hook: plugins loaded.
 */
function tdlc_plugin_init() {
	if ( class_exists( 'Buddypress' ) ) {
		
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'tdlc_plugin_links' );
	}
}

add_action( 'plugins_loaded', 'tdlc_plugin_init' );

if (function_exists('bp_is_active') && bp_is_active( 'xprofile' ) ){
    register_activation_hook( __FILE__,'tdlc_activate');
    register_deactivation_hook( __FILE__,'tdlc_deactivate');
}

function tdlc_activate() {
	
	$tdlc_activate = new tdlc_mail_send_Class();
	$tdlc_activate->activate();
	
}

function tdlc_deactivate() {
	
	$tdlc_deactivate = new tdlc_mail_send_Class();
	$tdlc_deactivate->deactivate();
	
}

add_action('bp_init','tdlc_birthdays_activate');

function tdlc_birthdays_activate(){
  if (function_exists('bp_is_active') && bp_is_active( 'xprofile' ) ){
    if(class_exists('tdlc_mail_send_Class')){ 
   
        // instantiate the plugin class
        $tdlc_obj = new tdlc_mail_send_Class();
    }
  } 
}

/**
 * Actions performed on hook: plugins action links.
 *
 * @param array $links  Action Links.
 */
function tdlc_plugin_links( $links ) {
	$tdlc_links = array(
		'<a href="' . admin_url( 'options-general.php?page=tdlc-birthdays' ) . '">' . esc_html__( 'Settings', 'tdlc-birthdays' ) . '</a>',
		'<a href="https://buddyuser.com/plugin-tdlc-birthdays" target="_blank" title="' . esc_html__( 'Visit our site for more information.', 'tdlc-birthdays' ) . '">' . esc_html__( 'Plugin home page', 'tdlc-birthdays' ) . '</a>',
	);
	return array_merge( $links, $tdlc_links );
}
?>