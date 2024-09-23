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

	public static function onUpdateSubscriber( $subscription, $response ) {
		$action_name = esc_html__( 'Update', 'responder' );

		self::createDebuggerPost( $action_name, $subscription, $response );
	}

	public static function register() {
		if ( ! self::isActive() ) {
			return;
		}

		add_action( 'init', array( __CLASS__, 'registerDebuggerPostType' ) );
		add_action( 'admin_menu', array( __CLASS__, 'addAsPluginSubMenu' ) );
		add_action( 'edit_form_after_title', array( __CLASS__, 'onPostEditContentDump' ) );

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
			'description'         => esc_html__( 'Submission Record', 'responder' ),
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => true,
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

	private static function createDebuggerPost( $action_name, $subscription, $response ) {
		$content['system_name'] = PluginHelpers::getVal( $subscription, 'system_name' );
		$content['list_id']     = PluginHelpers::getVal( $subscription, 'list_id', 0 );
		$content['list_name']   = '';
		$content['form_url']    = sanitize_url( PluginHelpers::getVal( $_SERVER, 'HTTP_REFERER' ) );
		$content['subscriber']  = $subscription['fields'];
		$content['response']    = $response;

		if ( ! empty( $content['system_name'] ) && ! empty( $content['list_id'] ) ) {
			$content['list_name'] = PluginAPI::run( $content['system_name'] )->getListName( (int) $content['list_id'] );
		}

		wp_insert_post(
			array(
				'post_title'   => self::setPostTitle( $action_name, $content['list_name'], $subscription ),
				'post_content' => wp_json_encode( $content, JSON_UNESCAPED_UNICODE ),
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
