<?php
/**
* Uninstall FeenBan
**/

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

$option_names = array(
	'feenban_option_name' 
);

if ( is_multisite() ) {
	if( sizeof( $option_names ) > 0 ) {
		foreach( $option_names as $option_name ) {
			delete_site_option( $option_name );
		}
	}
} else {
	if( sizeof( $option_names ) > 0 ) {
		foreach( $option_names as $option_name ) {
			delete_option( $option_name );
		}
	}
}
