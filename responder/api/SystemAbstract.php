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
