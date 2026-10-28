<?php

/**
 * This file is the plugin's auth api abstract
 *
 * @package Responder
 */

namespace RavMesser\API;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

abstract class SystemAbstract {

	public $last_error                 = '';
	protected $submit_error            = '';
	protected $connection              = null;
	protected $name                    = '';
	protected $user_credentials        = array();
	protected $user_filled_credentials = array();

	public function __construct( $user_credentials ) {
		$this->user_credentials = $user_credentials;

		if ( $this->areUserCredentialsValid() ) {
			$this->connect();
		}
	}

	public function areUserCredentialsValid() {
		$isValid = true;

		foreach ( $this->user_filled_credentials as $credential ) {
			if ( ! isset( $this->user_credentials[ $credential ] ) || empty( $this->user_credentials[ $credential ] ) ) {
				$isValid = false;
			}
		}

		return $isValid;
	}

	abstract public function createSubscriber( $subscriber_details = array());

	/**
	 * Sends a form lead and reports whether it reached the system (PR1-I10).
	 * Returns false when the API call failed (wrong / expired Token or key, disabled account,
	 * no reply). The failure is announced with the "RMP_submission_failed" action (log, admin
	 * notice, email); a success announces "RMP_submission_succeeded" (clears the notice).
	 * $context describes the form for the notice, e.g. array( 'integration' => 'Contact Form 7', 'form' => '#912' ).
	 */
	public function submitSubscriber( $subscriber_details = array(), $context = array() ) {
		$this->submit_error = '';

		if ( ! $this->areUserCredentialsValid() || null === $this->connection ) {
			$this->submit_error = 'not_connected';
		} elseif ( empty( $subscriber_details['list_id'] ) ) {
			// PR1-I32: a form saved without a list (e.g. an old Elementor widget form) sends nothing -
			// report it as a failed submission instead of a silent success.
			$this->submit_error = 'no_list: no list is selected in this form - choose a list in the form settings and save';
		} else {
			$this->createSubscriber( $subscriber_details );
		}

		if ( '' !== $this->submit_error ) {
			do_action( 'RMP_submission_failed', $this->getName(), $this->submit_error, $subscriber_details, $context );
			return false;
		}

		do_action( 'RMP_submission_succeeded', $this->getName() );
		return true;
	}

	public function getSubmitError() {
		return $this->submit_error;
	}

	abstract public function getListName( $list_id = 0);

	abstract public function getLists();

	public function getName() {
		return $this->name;
	}

	abstract public function getPersonalFieldsByListId( $list_id = 0);

	public function getState() {
		$state = '';

		if ( ! $this->areUserCredentialsValid() ) {
			$state = 'not_full';
		} elseif ( ! empty( $this->last_error ) ) {
			$state = 'auth_error';
		} else {
			$state = 'auth_success';
		}

		return $state;
	}

	abstract public function getSubscribersByListId( $list_id = 0);

	abstract public function getSubscribersSheetByListId( $list_id = 0);

	public function isValid() {
		return $this->areUserCredentialsValid() && empty( $this->last_error );
	}

	abstract public function updateSubscriber( $subscriber_details = array());

	protected function broadcast( $action_name, $subscription, $response ) {
		$subscription['system_name'] = $this->getName();

		switch ( $action_name ) {
			case 'create_subscriber':
				$tag = 'RMP_api_log_create_subscriber';
				break;
			case 'update_subscriber':
				$tag = 'RMP_api_log_update_subscriber';
				break;
		}

		do_action( $tag, $subscription, $response );
	}

	abstract protected function connect();

	abstract protected function request();

	abstract protected function validateSubscriber( $subscriber_data = array());

	abstract protected function validateSubscriberExistance( $subscriber_data = array());
}
