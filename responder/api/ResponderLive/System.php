<?php namespace RavMesser\API\ResponderLive;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\API\CachedResponse;
use RavMesser\API\SystemAbstract;
use RavMesser\API\ResponderLive\FieldsFormatter;
use RavMesser\API\ResponderLive\FieldsParser;
use RavMesser\API\ResponderLive\sdk\OAuth as ResponderLiveOAuth;
use RavMesser\Plugin\OptionsManager as PluginOptionsManager;
use RavMesser\Plugin\Helpers as PluginHelpers;
use Exception;

class System extends SystemAbstract {

	const CLIENT_ID                    = 7;
	const CLIENT_SECRET                = 'DVAMzVmvp2xnds6vVRA3kWVXf3S3qZaCzbZ7UC9c';
	protected $name                    = 'responder_live';
	protected $user_filled_credentials = array( 'user_token' );

	public function connect() {
		try {
			if ( $this->connection ) {
				return true;
			}

			$this->connection = new ResponderLiveOAuth(
				self::CLIENT_ID,
				self::CLIENT_SECRET,
				$this->user_credentials['user_token'],
				$this->user_credentials['auth_token']
			);
		} catch ( Exception $e ) {
			die( var_dump( $e->getMessage() ) );
		}
	}

	public function createSubscriber( $subscriber_details = array() ) {
		$list_id = PluginHelpers::getVal( $subscriber_details, 'list_id' );

		if ( ! empty( $list_id ) ) {
			$subscriber = new FieldsParser( $subscriber_details );
			$response   = $this->request( '/subscribers', 'POST', $subscriber->getFields() );
			$this->broadcast( 'create_subscriber', $subscriber_details, $response );

			$invalid_subscriber = $this->validateSubscriber( $response );
			if ( ! empty( $invalid_subscriber ) ) {
				return false;
			}

			$existing_subscriber_id = $this->validateSubscriberExistance( $response );
			if ( ! empty( $existing_subscriber_id ) ) {
				$subscriber_details['subscriber_id'] = $existing_subscriber_id;
				$this->updateSubscriber( $subscriber_details );
			}
		}
	}

	public function createSubscribersTag( $tag_name = '' ) {
		$tag_name = str_replace( ' ', '-', stripcslashes( $tag_name ) );

		$response_tag = array(
			'id'   => '',
			'name' => $tag_name,
		);

		if ( ! empty( $tag_name ) ) {
			$tag = $this->request( '/tags/subscribers', 'POST', array( 'name' => $tag_name ) );

			if ( isset( $tag['createdId'] ) && ! empty( $tag['createdId'] ) ) {
				$response_tag['id'] = $tag['createdId'];
			}
		}

		return $response_tag;
	}

	public function getListName( $list_id = 0 ) {
		$list_name = '';

		if ( ! empty( $list_id ) ) {
			$response  = $this->request( "/lists/{$list_id}" );
			$list_name = PluginHelpers::getVal( $response, 'name' );
		}

		return $list_name;
	}

	public function getLists() {
		$new_lists     = array();
		$current_lists = $this->request( 'lists' );

		if ( ! empty( $current_lists ) ) {
			foreach ( $current_lists as $list ) {
				if ( PluginHelpers::getVal( $list, 'is_dynamic', 0 ) === 0 ) {
					$new_lists[] = array(
						'id'   => PluginHelpers::getVal( $list, 'id' ),
						'name' => PluginHelpers::getVal( $list, 'name' ),
					);
				}
			}
		}

		return $new_lists;
	}

	public function getPersonalFieldsByListId( $list_id = 0 ) {
		$requested_fields          = $this->request( "lists/{$list_id}/fields" );
		$personal_fields           = PluginHelpers::getVal( $requested_fields, 'personal_fields', array() );
		$all_lists_personal_fields = PluginHelpers::getVal( $requested_fields, 'all_lists_personal_fields', array() );
		$all_fields                = array_merge( $personal_fields, $all_lists_personal_fields );

		$response_fields = array(
			array(
				'id'        => 'first',
				'type'      => 'text',
				'uri_param' => 'first',
				'name'      => esc_html__( 'שם פרטי', 'responder' ),
			),
			array(
				'id'        => 'last',
				'type'      => 'text',
				'uri_param' => 'last',
				'name'      => esc_html__( 'שם משפחה', 'responder' ),
			),
			array(
				'id'        => 'name',
				'type'      => 'text',
				'uri_param' => 'name',
				'name'      => esc_html__( 'שם מלא', 'responder' ),
			),
			array(
				'id'        => 'email',
				'type'      => 'email',
				'uri_param' => 'email',
				'name'      => esc_html__( 'כתובת מייל', 'responder' ),
			),
			array(
				'id'        => 'phone',
				'type'      => 'phone',
				'uri_param' => 'phone',
				'name'      => esc_html__( 'טלפון', 'responder' ),
			),
		);

		if ( ! empty( $all_fields ) ) {
			foreach ( $all_fields as $field ) {
				$field_name = PluginHelpers::getVal( $field, 'name', '' );

				$response_field = array(
					'id'        => PluginHelpers::getVal( $field, 'id', '' ),
					'type'      => FieldsFormatter::getTypeById(
						(int) PluginHelpers::getVal( $field, 'type_id', '' )
					),
					'name'      => $field_name,
					'uri_param' => PluginHelpers::urlToHandle( $field_name ),
				);

				$field_options = PluginHelpers::getVal( $field, 'options', array() );

				if ( ! empty( $field_options ) ) {
					$response_field['options'] = $field_options;
				}

				array_push( $response_fields, $response_field );
			}
		}

		return $response_fields;
	}

	public function getSubscribersByListId( $list_id = 0 ) {
		$subscribers_list = array();
		$subscribers      = $this->request( "lists/{$list_id}/subscribers" );
		$fields           = $this->request( "lists/{$list_id}/fields" );

		foreach ( $subscribers as $subscriber ) {
			$subscriber_details = array(
				esc_html__( 'כתובת מייל', 'responder' ) => PluginHelpers::getVal( $subscriber, 'email' ),
				esc_html__( 'שם', 'responder' )         => PluginHelpers::getVal( $subscriber, 'name' ),
				esc_html__( 'טלפון', 'responder' )      => PluginHelpers::getVal( $subscriber, 'phone' ),
			);

			$personal_details = new FieldsFormatter(
				$fields,
				$subscriber['personal_fields']
			);

			if ( $personal_details->fieldsCount() > 0 ) {
				  $subscriber_details = array_merge( $subscriber_details, $personal_details->getNameValueFields() );
			}

			array_push( $subscribers_list, $subscriber_details );
		}

		return $subscribers_list;
	}

	public function getSubscribersSheetByListId( $list_id = 0 ) {
		$subscribers_list  = $this->getSubscribersByListId( $list_id );
		$subscribers_sheet = array(
			'count'   => count( $subscribers_list ),
			'rows'    => array(),
			'columns' => array(),
		);

		if ( $subscribers_sheet['count'] > 0 ) {
			$subscribers_sheet['columns'] = array_keys( $subscribers_list[0] );

			foreach ( $subscribers_list as $subscriber ) {
				array_push( $subscribers_sheet['rows'], array_values( $subscriber ) );
			}
		} else {
			$fields                    = $this->request( "lists/{$list_id}/fields" );
			$personal_fields           = PluginHelpers::getVal( $fields, 'personal_fields', array() );
			$all_lists_personal_fields = PluginHelpers::getVal( $fields, 'all_lists_personal_fields', array() );
			$fields                    = array_merge( $personal_fields, $all_lists_personal_fields );

			$subscribers_sheet['columns'] = array(
				esc_html__( 'כתובת מייל', 'responder' ),
				esc_html__( 'שם', 'responder' ),
				esc_html__( 'טלפון', 'responder' ),
			);

			foreach ( $fields as $field ) {
				array_push( $subscribers_sheet['columns'], $field['name'] );
			}
		}

		return $subscribers_sheet;
	}

	public function getSubscribersTags() {
		$tags          = $this->request( '/tags/subscribers' );
		$response_tags = array();

		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				$response_tag = array(
					'id'   => "{$tag['id']}",
					'name' => $tag['name'],
				);

				array_push( $response_tags, $response_tag );
			}
		}

		return $response_tags;
	}

	public function updateSubscriber( $subscriber_details = array() ) {
		$list_id       = PluginHelpers::getVal( $subscriber_details, 'list_id' );
		$subscriber_id = PluginHelpers::getVal( $subscriber_details, 'subscriber_id' );

		if ( ! empty( $list_id ) && ! empty( $subscriber_id ) ) {
			$subscriber_data = array(
				'inactive' => false,
			);

			if ( PluginHelpers::ifExistsAndEqual( $subscriber_details, 'onexisting_joindate', 'joindate' ) ) {
				$subscriber_data['list_seniority_date'] = date( 'Y-m-d' );
			}

			$response = $this->request( "/lists/{$list_id}/subscribers/{$subscriber_id}", 'PUT', $subscriber_data );

			$this->broadcast( 'update_subscriber', $subscriber_details, $response );

			if ( PluginHelpers::ifExistsAndEqual( $response, 'status', false ) ) {
				return false;
			}
		}
	}

	protected function request( $path = '', $method = 'GET', $post = array() ) {
		if ( CachedResponse::isCached( $path, $method, $post ) ) {
			return CachedResponse::getCached( $path, $method, $post );
		}

		$response = $this->connection->httpRequest( $path, $method, $post );
		$response = PluginHelpers::jsonDecode( $response );

		$error = '';

		if ( ( ! isset( $response['status'] ) || empty( $response['status'] ) ) && isset( $response['error'] ) ) {
			$error = $response['error'];
		} elseif ( gettype( $response ) === 'string' ) {
			$error = $response;
		}

		if ( ! empty( $error ) ) {
			$this->last_error = $response;
			return array();
		}

		$this->updateAuthToken();

		if ( isset( $response['data'] ) ) {
			$response = $response['data'];
		}

		return CachedResponse::setCached( $path, $method, $post, $response );
	}

	protected function validateSubscriber( $subscriber_data = array() ) {
		$invalid_subscriber = false;

		if ( is_string( $subscriber_data ) || $subscriber_data['status'] === false ) {
			$invalid_subscriber = true;
		}

		return $invalid_subscriber;
	}

	protected function validateSubscriberExistance( $subscriber_data = array() ) {
		$subscriber_id = 0;

		if ( isset( $subscriber_data['duplicate'] ) && $subscriber_data['duplicate'] === true ) {
			$subscriber_id = $subscriber_data['createdId'];
		}

		return $subscriber_id;
	}

	private function updateAuthToken() {
		$old_auth_token = $this->user_credentials['auth_token'];
		$new_auth_token = $this->connection->getAuthToken();

		if ( ! empty( $new_auth_token ) && $old_auth_token !== $new_auth_token ) {
			PluginOptionsManager::run()->updateOption( 'responder_live_auth_token', $new_auth_token );
		}
	}
}
