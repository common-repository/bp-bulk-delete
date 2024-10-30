<?php

/*
Plugin Name: BP Bulk Delete
Description: Bulk delete BuddyPress Activities, Messages and Notifications based on date
Version: 1.5
Author: PhiloPress
Author URI: https://philopress.com/
License:  GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


defined( 'ABSPATH' ) or die;

function bpbd_plugin_init() {
	if( is_admin() )
	    require( dirname( __FILE__ ) . '/bpbd-admin.php' );
}
add_action( 'bp_include', 'bpbd_plugin_init' );


function bpbd_add_tool_link( $links ) {
	$link = array( '<a href="' . admin_url( 'tools.php?page=bpbd' ) . '">Tool</a>', );
	return array_merge( $links, $link );
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'bpbd_add_tool_link' );
