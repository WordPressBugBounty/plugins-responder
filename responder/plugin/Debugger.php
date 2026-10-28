<?php

/**
 * This file is the plugin's debugger file
 *
 * @package Responder
 */

namespace RavMesser\Plugin;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\Plugin\API as PluginAPI;
use RavMesser\Plugin\OptionsManager as PluginOptionsManager;
use RavMesser\Plugin\Helpers as PluginHelpers;

class Debugger {

	const ENABLED_SETTING = 'responder_settings_advanced';
	const POST_TYPE       = 'responder_debug';
	const HAS_ENTRIES_OPTION = 'rmp_debug_has_unmatched_options';

	public static function addAsPluginSubMenu() {
		add_submenu_page(
			RAV_MESSER_MENU_SLUG,
			'Responder Debug',
			'Responder Debug',
			'manage_options',
			'edit.php?post_type=' . self::POST_TYPE
		);
	}

	public static function isActive() {
		$setting    = PluginOptionsManager::run()->getOption( self::ENABLED_SETTING );
		$is_enabled = PluginHelpers::ifExistsAndEqual( $setting, 'enable_debug', 'true' );

		return $is_enabled;
	}

	public static function onCreateSubscriber( $subscription, $response ) {
		$action_name = esc_html__( 'Submission', 'responder' );

		self::createDebuggerPost( $action_name, $subscription, $response );
	}

	public static function onPostEditContentDump( $post ) {
		if ( empty( $post ) || $post->post_type !== self::POST_TYPE ) {
			return;
		}

		$content = print_r(
			PluginHelpers::jsonDecode( $post->post_content ),
			true
		);

		echo '<div style="direction: ltr; text-align: left; margin-top: 15px; background-color: #fff; border-radius: 5px; padding: 10px; border: 1px solid #c5cace; overflow-x: auto">';
		echo '<pre style="margin: 0; padding: 0;">' . esc_html( $content ) . '</pre>';
		echo '</div>';
	}

	/**
	 * A lead that did not reach the system (PR1-I10): saved with the real API error.
	 * No API call here (the connection is what failed), so the list is shown by its ID.
	 */
	public static function onSubmissionFailed( $system_name, $error, $subscription = array(), $context = array() ) {
		$fields  = PluginHelpers::getVal( $subscription, 'fields', array() );
		$content = array(
			'system_name' => $system_name,
			'list_id'     => PluginHelpers::getVal( $subscription, 'list_id', 0 ),
			'form'        => SubmissionFailures::formLabel( $context ),
			'form_url'    => sanitize_url( PluginHelpers::getVal( $_SERVER, 'HTTP_REFERER' ) ),
			'subscriber'  => $fields,
			'error'       => SubmissionFailures::errorToText( $error ),
		);

		$subscription['fields'] = is_array( $fields ) ? $fields : array();

		wp_insert_post(
			array(
				'post_title'   => wp_slash( self::setPostTitle( 'Failed submission', '#' . $content['list_id'] . ' (' . SubmissionFailures::systemLabel( $system_name ) . ')', $subscription ) ),
				// wp_insert_post() unslashes its input - slash the JSON so escaped quotes survive.
				'post_content' => wp_slash( wp_json_encode( $content, JSON_UNESCAPED_UNICODE ) ),
				'post_type'    => self::POST_TYPE,
			)
		);
	}

	/**
	 * Elementor Pro answers that match no option of a רב מסר choice field (PR1-I13): the field was not sent,
	 * so the answer is kept here - also when the debug is off.
	 */
	public static function onUnmatchedChoiceOptions( $system_name, $subscription = array(), $unmatched = array(), $context = array() ) {
		$fields  = PluginHelpers::getVal( $subscription, 'fields', array() );
		$content = array(
			'system_name' => $system_name,
			'list_id'     => PluginHelpers::getVal( $subscription, 'list_id', 0 ),
			'form'        => SubmissionFailures::formLabel( $context ),
			'form_url'    => sanitize_url( PluginHelpers::getVal( $_SERVER, 'HTTP_REFERER' ) ),
			'note'        => 'These answers match no option of the list field, so the field was not sent. Match the options under "התאמת אפשרויות הבחירה" in the form\'s רב מסר settings.',
			'unmatched'   => $unmatched,
			'subscriber'  => $fields,
		);

		$subscription['fields'] = is_array( $fields ) ? $fields : array();

		wp_insert_post(
			array(
				'post_title'   => wp_slash( self::setPostTitle( 'Unmatched options', '#' . $content['list_id'] . ' (' . SubmissionFailures::systemLabel( $system_name ) . ')', $subscription ) ),
				'post_content' => wp_slash( wp_json_encode( $content, JSON_UNESCAPED_UNICODE ) ),
				'post_type'    => self::POST_TYPE,
			)
		);

		update_option( self::HAS_ENTRIES_OPTION, 1, false );
	}

	/**
	 * A lead sent to a form that is no longer in "טפסים לאלמנטור" (PR1-I15): nothing was sent to רב מסר,
	 * so the details are kept here - also when the debug is off.
	 */
	public static function onFormNotFound( $form_id = '', $fields = array(), $context = array() ) {
		$fields   = is_array( $fields ) ? $fields : array();
		$identity = '';

		foreach ( $fields as $field ) {
			$value = (string) PluginHelpers::getVal( $field, 'value', '' );

			if ( '' === $identity && '' !== $value ) {
				$identity = $value;
			}

			if ( false !== strpos( (string) PluginHelpers::getVal( $field, 'name', '' ), 'email' ) && '' !== $value ) {
				$identity = $value;
				break;
			}
		}

		$content = array(
			'form'       => SubmissionFailures::formLabel( $context ),
			'form_id'    => $form_id,
			'form_url'   => sanitize_url( PluginHelpers::getVal( $_SERVER, 'HTTP_REFERER' ) ),
			'note'       => 'No form with this ID in "טפסים לאלמנטור" (deleted, or a cached page with an old form). Nothing was sent to רב מסר. Remove the widget from the page or set it to an existing form, and clear the page cache.',
			'subscriber' => $fields,
		);

		wp_insert_post(
			array(
				'post_title'   => wp_slash( 'Form not found From: ' . $identity . ' to Form: ' . $form_id . ' (' . PluginHelpers::getVal( $context, 'integration', '' ) . ')' ),
				'post_content' => wp_slash( wp_json_encode( $content, JSON_UNESCAPED_UNICODE ) ),
				'post_type'    => self::POST_TYPE,
			)
		);

		update_option( self::HAS_ENTRIES_OPTION, 1, false );
	}

	public static function onUpdateSubscriber( $subscription, $response ) {
		$action_name = esc_html__( 'Update', 'responder' );

		self::createDebuggerPost( $action_name, $subscription, $response );
	}

	public static function register() {
		// PR1-I10: a lead that did not reach רב מסר is always saved here, also when the debug is off.
		add_action( 'RMP_submission_failed', array( __CLASS__, 'onSubmissionFailed' ), 10, 4 );
		add_action( 'RMP_unmatched_choice_options', array( __CLASS__, 'onUnmatchedChoiceOptions' ), 10, 4 );
		add_action( 'RMP_form_not_found', array( __CLASS__, 'onFormNotFound' ), 10, 3 );
		add_action( 'init', array( __CLASS__, 'registerDebuggerPostType' ) );
		add_action( 'edit_form_after_title', array( __CLASS__, 'onPostEditContentDump' ) );

		// The menu also stays once an "Unmatched options" entry was saved with the debug off (PR1-I13).
		if ( self::isActive() || SubmissionFailures::hasFailures() || get_option( self::HAS_ENTRIES_OPTION ) ) {
			add_action( 'admin_menu', array( __CLASS__, 'addAsPluginSubMenu' ) );
		}

		if ( ! self::isActive() ) {
			return;
		}

		add_action( 'RMP_api_log_create_subscriber', array( __CLASS__, 'onCreateSubscriber' ), 10, 2 );
		add_action( 'RMP_api_log_update_subscriber', array( __CLASS__, 'onUpdateSubscriber' ), 10, 2 );
	}

	public static function registerDebuggerPostType() {
		$post_type_labels = array(
			'name'               => esc_html__( 'Responder Debug', 'responder' ),
			'singular_name'      => esc_html__( 'Responder Submission', 'responder' ),
			'add_new'            => esc_html__( 'Add New Submission', 'responder' ),
			'add_new_item'       => esc_html__( 'Add New Submission', 'responder' ),
			'edit_item'          => esc_html__( 'Edit Submission', 'responder' ),
			'new_item'           => esc_html__( 'New Submission', 'responder' ),
			'view_item'          => esc_html__( 'View Submission', 'responder' ),
			'view_items'         => esc_html__( 'View Submission', 'responder' ),
			'search_items'       => esc_html__( 'Search Submission', 'responder' ),
			'not_found'          => esc_html__( 'No Submissions Found', 'responder' ),
			'not_found_in_trash' => esc_html__( 'No Submissions found in trash', 'responder' ),
			'all_items'          => esc_html__( 'All Submissions', 'responder' ),
		);

		$post_type_options = array(
			'labels'              => $post_type_labels,
			'public'              => false,
			'rewrite'             => false,
			'show_ui'             => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'post',
			// The entries hold leads (name, email, phone): site admins only (PR1-I10, PR1-I23) - the list, its search,
			// an entry, edit, trash and restore. Every right is set, so none falls back to the normal post rights of Editors / Authors.
			'capabilities'        => array(
				'edit_post'              => 'manage_options',
				'read_post'              => 'manage_options',
				'delete_post'            => 'manage_options',
				'edit_posts'             => 'manage_options',
				'edit_others_posts'      => 'manage_options',
				'edit_published_posts'   => 'manage_options',
				'edit_private_posts'     => 'manage_options',
				'delete_posts'           => 'manage_options',
				'delete_others_posts'    => 'manage_options',
				'delete_published_posts' => 'manage_options',
				'delete_private_posts'   => 'manage_options',
				'publish_posts'          => 'manage_options',
				'read_private_posts'     => 'manage_options',
				'create_posts'           => 'do_not_allow',
			),
			'map_meta_cap'        => false,
			'description'         => esc_html__( 'Submission Record', 'responder' ),
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-sos',
			'menu_position'       => 90,
			'has_archive'         => false,
			'query_var'           => false,
			'can_export'          => false,
		);

		register_post_type( self::POST_TYPE, $post_type_options );
	}

	public static function saveSetting( $settings ) {
		PluginOptionsManager::run()->updateOption( self::ENABLED_SETTING, $settings );
	}

	public static function printOut( $variable_or_condition ) {
		if ( PluginHelpers::ifExistsAndNotEmptyArray( $GLOBALS, '_REQUEST', 'page' ) ) {
			if ( $GLOBALS['_REQUEST']['page'] === 'Responder_PluginSettings' ) {
				die( var_export( $variable_or_condition, true ) );
			}
		}
	}

	/**
	 * Logs a failed connection (wrong Token / key / secret) with the full API reply.
	 * One line in the PHP error log when WP_DEBUG_LOG is on; a "Responder Debug" entry when the plugin debug is on.
	 * Saved credential values are replaced with *** first.
	 */
	public static function logConnectionError( $system_name, $raw_error ) {
		$labels = array(
			'responder_live' => 'רב מסר',
			'responder'      => 'רב מסר הישנה',
		);
		$label  = isset( $labels[ $system_name ] ) ? $labels[ $system_name ] : $system_name;
		$error  = is_scalar( $raw_error ) ? (string) $raw_error : wp_json_encode( $raw_error, JSON_UNESCAPED_UNICODE );
		$error  = self::maskCredentials( $error );

		// Only when the site logs PHP errors on purpose (WP_DEBUG + WP_DEBUG_LOG) - not on regular client sites.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( '[Responder] Connection error (' . $system_name . '): ' . $error );
		}

		if ( ! self::isActive() ) {
			return;
		}

		wp_insert_post(
			array(
				'post_title'   => 'Connection error: ' . $label,
				'post_content' => wp_slash(
					wp_json_encode(
						array(
							'system_name' => $system_name,
							'error'       => $error,
						),
						JSON_UNESCAPED_UNICODE
					)
				),
				'post_type'    => self::POST_TYPE,
			)
		);
	}

	/**
	 * Replaces every saved credential value (Token, auth token, key, secret) found in $data
	 * with ***. Works on strings and on nested arrays.
	 */
	public static function maskCredentials( $data ) {
		$secrets = array();

		foreach ( array( 'responder_live_user_token', 'responder_live_auth_token', 'Responder_Plugin_EnterUsername', 'Responder_Plugin_EnterPassword' ) as $option_name ) {
			$value = (string) PluginOptionsManager::run()->getOption( $option_name );

			if ( strlen( $value ) >= 6 ) {
				$secrets[] = $value;
			}
		}

		if ( empty( $secrets ) ) {
			return $data;
		}

		if ( is_array( $data ) ) {
			foreach ( $data as $key => $item ) {
				$data[ $key ] = self::maskCredentials( $item );
			}

			return $data;
		}

		if ( is_string( $data ) ) {
			return str_replace( $secrets, '***', $data );
		}

		return $data;
	}

	private static function createDebuggerPost( $action_name, $subscription, $response ) {
		$content['system_name'] = PluginHelpers::getVal( $subscription, 'system_name' );
		$content['list_id']     = PluginHelpers::getVal( $subscription, 'list_id', 0 );
		$content['list_name']   = '';
		$content['form_url']    = sanitize_url( PluginHelpers::getVal( $_SERVER, 'HTTP_REFERER' ) );
		$content['subscriber']  = $subscription['fields'];
		$content['response']    = self::maskCredentials( $response );

		if ( ! empty( $content['system_name'] ) && ! empty( $content['list_id'] ) ) {
			$content['list_name'] = PluginAPI::run( $content['system_name'] )->getListName( (int) $content['list_id'] );
		}

		wp_insert_post(
			array(
				// wp_insert_post() unslashes its input - slash it so \ and the JSON's escaped quotes survive (PR1-I11).
				'post_title'   => wp_slash( self::setPostTitle( $action_name, $content['list_name'], $subscription ) ),
				'post_content' => wp_slash( wp_json_encode( $content, JSON_UNESCAPED_UNICODE ) ),
				'post_type'    => self::POST_TYPE,
			)
		);
	}

	private static function setPostTitle( $action_name, $list_name, $subscription ) {
		$subscriber_identity = PluginHelpers::getVal( $subscription['fields'], 'name' );

		if ( empty( $subscriber_identity ) ) {
			$subscriber_identity = PluginHelpers::getVal( $subscription['fields'], 'first' );
		}

		if ( empty( $subscriber_identity ) ) {
			$subscriber_identity = PluginHelpers::getVal( $subscription['fields'], 'email' );
		}

		if ( empty( $subscriber_identity ) ) {
			$subscriber_identity = PluginHelpers::getVal( $subscription['fields'], 'phone' );
		}

		if ( ! empty( $subscriber_identity ) ) {
			$subscriber_identity = $subscriber_identity['value'];
		}

		return "{$action_name} From: {$subscriber_identity} to List: {$list_name}";
	}
}
