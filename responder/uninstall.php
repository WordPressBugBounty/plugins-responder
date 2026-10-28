<?php
/**
 * Runs only when the site owner deletes the plugin (Plugins -> Delete), not on deactivate (PR1-I19).
 *
 * Removes the רב מסר / רב מסר הישנה credentials (key, secret, tokens - stored in plain text).
 * Widget forms, Contact Form 7 connections, settings and the Responder Debug log are kept
 * (product decision, 21/10/2026): after a reinstall the owner only connects again.
 *
 * @package Responder
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! function_exists( 'rmp_uninstall_remove_credentials' ) ) {
	function rmp_uninstall_remove_credentials() {
		$credentials = array(
			'Responder_Plugin_EnterUsername', // רב מסר הישנה key.
			'Responder_Plugin_EnterPassword', // רב מסר הישנה secret.
			'responder_live_user_token',      // רב מסר Token.
			'responder_live_auth_token',      // רב מסר auth token (JWT).
		);

		foreach ( $credentials as $option_name ) {
			delete_option( $option_name );
		}
	}
}

if ( is_multisite() && function_exists( 'get_sites' ) ) {
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $rmp_site_id ) {
		switch_to_blog( $rmp_site_id );
		rmp_uninstall_remove_credentials();
		restore_current_blog();
	}
} else {
	rmp_uninstall_remove_credentials();
}
