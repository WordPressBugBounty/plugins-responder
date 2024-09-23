<?php namespace RavMesser\API\Responder;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

use RavMesser\API\CachedResponse;
use RavMesser\API\SystemAbstract;
use RavMesser\API\Responder\FieldsFormatter;
use RavMesser\API\Responder\FieldsParser;
use RavMesser\Plugin\Helpers as PluginHelpers;
use ResponderOAuth;
use Exception;

class System extends SystemAbstract {

	const CLIENT_SECRET                = '72818D716ABC9AF0A959696576F418B0';
	const CLIENT_TOKEN                 = '6FF503AEA0B1E24E72583C99B0DD471A';
	protected $name                    = 'responder';
	protected $user_filled_credentials = array( 'user_token', 'user_secret' );

	public function createSubscriber( $subscriber_details = array() ) {
		$list_id = PluginHelpers::getVal( $subscriber_details, 'list_id' );

		if ( ! empty( $list_id ) ) {
			$subscriber = new FieldsParser( $subscriber_details );

			$subscriber_data = array(
				'subscribers' => wp_json_encode(
					array(
						$subscriber->getFields(),
					)
				),
			);

			$response = $this->request( "/lists/{$list_id}/subscribers", 'POST', $subscriber_data );
			$this->broadcast( 'create_subscriber', $subscriber_details, $response );

			$invalid_subscriber = $this->validateSubscriber( $response );
			if ( ! empty( $invalid_subscriber ) ) {
				return false;
			}

			$existing_subscriber = $this->validateSubscriberExistance( $response );

			if ( ! empty( $existing_subscriber ) ) {
				$existing_emails                  = PluginHelpers::getVal( $existing_subscriber, 'EMAILS_EXISTING', array() );
				$subscriber_details['identifier'] = array_pop( $existing_emails );

				$this->updateSubscriber( $subscriber_details );
			}
		}
	}

	public function getListName( $list_id = 0 ) {
		$list_name = '';

		if ( ! empty( $list_id ) ) {
			$response = $this->request(
				'lists',
				'GET',
				array(
					'list_ids' => array( $list_id ),
				)
			);

			$lists = PluginHelpers::getVal( $response, 'LISTS' );

			if ( ! empty( $lists ) ) {
				  $list_name = $lists[0]['DESCRIPTION'];
			}
		}

		return $list_name;
	}

	public function getLists() {
		$current_lists        = array();
		$new_list             = array();
		$request_lists_limit  = 500;
		$request_lists_offset = 0;
		$response_lists_count = 0;

		do {
			$response = $this->request(
				'lists',
				'GET',
				array(
					'limit'  => $request_lists_limit,
					'offset' => $request_lists_offset,
				)
			);

			if ( ! empty( $response ) && ! empty( $response['LISTS'] ) ) {
				  $response_lists       = PluginHelpers::getVal( $response, 'LISTS' );
				  $response_lists_count = count( $response_lists );
				  $current_lists        = array_merge( $current_lists, $response_lists );
				  $request_lists_offset += $response_lists_count;
			} else {
				break;
			}
		} while ( $response_lists_count === $request_lists_limit );

		if ( ! empty( $current_lists ) ) {
			foreach ( $current_lists as $list ) {
				array_push(
					$new_list,
					array(
						'id'   => PluginHelpers::getVal( $list, 'ID' ),
						'name' => PluginHelpers::getVal( $list, 'DESCRIPTION' ),
					)
				);
			}
		}

		return $new_list;
	}

	public function getPersonalFieldsByListId( $list_id = 0 ) {
		$personal_fields = $this->request( "lists/{$list_id}/personal_fields" );
		$personal_fields = PluginHelpers::getVal( $personal_fields, 'PERSONAL_FIELDS', array() );

		$response_fields = array(
			array(
				'id'        => 'name',
				'type'      => 'text',
				'uri_param' => 'name',
				'name'      => esc_html__( 'שם', 'responder' ),
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

		if ( ! empty( $personal_fields ) ) {
			foreach ( $personal_fields as $personal_field ) {
				$personal_field_name = PluginHelpers::getVal( $personal_field, 'NAME', '' );

				array_push(
					$response_fields,
					array(
						'id'        => (int) PluginHelpers::getVal( $personal_field, 'ID', '' ),
						'type'      => FieldsFormatter::getTypeById(
							(int) PluginHelpers::getVal( $personal_field, 'TYPE', '0' )
						),
						'name'      => $personal_field_name,
						'uri_param' => PluginHelpers::urlToHandle( $personal_field_name ),
					)
				);
			}
		}

		return $response_fields;
	}

	public function getSubscribersByListId( $list_id = 0 ) {
		$subscribers_list = array();
		$subscribers      = $this->request( "lists/{$list_id}/subscribers" );
		$personal_fields  = $this->request( "lists/{$list_id}/personal_fields" );
		$personal_fields  = PluginHelpers::getVal( $personal_fields, 'PERSONAL_FIELDS', array() );

		if ( ! empty( $personal_fields ) ) {
			$personal_fields = PluginHelpers::arrayToAssoc( $personal_fields, 'ID' );
		}

		foreach ( $subscribers as $subscriber ) {
			$subscriber_details = array(
				esc_html__( 'כתובת מייל', 'responder' ) => PluginHelpers::getVal( $subscriber, 'EMAIL' ),
				esc_html__( 'שם', 'responder' )         => PluginHelpers::getVal( $subscriber, 'NAME' ),
				esc_html__( 'טלפון', 'responder' )      => PluginHelpers::getVal( $subscriber, 'PHONE' ),
			);

			foreach ( $personal_fields as $personal_field ) {
				$personal_field_id          = PluginHelpers::getVal( $personal_field, 'ID' );
				$subscriber_personal_fields = PluginHelpers::getVal( $subscriber, 'PERSONAL_FIELDS', array() );

				if ( isset( $subscriber_personal_fields[ $personal_field_id ] ) && ! empty( $subscriber_personal_fields[ $personal_field_id ] ) ) {
					$subscriber_details[ $personal_field['NAME'] ] = $subscriber_personal_fields[ $personal_field_id ];
				} else {
					$subscriber_details[ $personal_field['NAME'] ] = $personal_field['DEFAULT_VALUE'];
				}
			}

			if ( ! empty( $subscriber['PERSONAL_FIELDS'] ) ) {
				foreach ( $subscriber['PERSONAL_FIELDS'] as $field_id => $field_value ) {
					$subscriber_details[ $personal_fields[ $field_id ]['NAME'] ] = $field_value;
				}
			} else {
				foreach ( $personal_fields as $personal_field ) {
					$subscriber_details[ $personal_field['NAME'] ] = $personal_field['DEFAULT_VALUE'];
				}
			}

			array_push( $subscribers_list, $subscriber_details );
		}

		return $subscribers_list;
	}

	public function getSubscribersSheetByListId( $list_id = 0 ) {
		$subscribers_list = $this->getSubscribersByListId( $list_id );

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
			$personal_fields              = $this->request( "lists/{$list_id}/personal_fields" );
			$personal_fields              = PluginHelpers::getVal( $personal_fields, 'PERSONAL_FIELDS', array() );
			$subscribers_sheet['columns'] = array(
				esc_html__( 'כתובת מייל', 'responder' ),
				esc_html__( 'שם', 'responder' ),
				esc_html__( 'טלפון', 'responder' ),
			);

			foreach ( $personal_fields as $personal_field ) {
				array_push( $subscribers_sheet['columns'], $personal_field['NAME'] );
			}
		}

		return $subscribers_sheet;
	}

	public function updateSubscriber( $subscriber_details = array() ) {
		$list_id = PluginHelpers::getVal( $subscriber_details, 'list_id' );

		if ( ! empty( $list_id ) ) {
			$subscriber      = new FieldsParser( $subscriber_details );
			$subscriber_data = array();

			if ( $subscriber->shouldResubscibe() ) {
				$personal_fields = $this->getPersonalFieldsByListId( $list_id );
				$subscriber->prepareResubscriptionFields( $personal_fields );
			}

			$subscriber_data = array(
				'subscribers' => wp_json_encode(
					array(
						$subscriber->getFields(),
					)
				),
			);

			$response = $this->request( "/lists/{$list_id}/subscribers", 'PUT', $subscriber_data );
			$this->broadcast( 'update_subscriber', $subscriber_details, $response );

			$invalid_subscriber = $this->validateSubscriber( $response );
			if ( ! empty( $invalid_subscriber ) ) {
				return false;
			}
		}
	}

	protected function connect() {
		try {
			if ( $this->connection ) {
				return true;
			}

			$this->connection = new ResponderOAuth(
				self::CLIENT_TOKEN,
				self::CLIENT_SECRET,
				$this->user_credentials['user_token'],
				$this->user_credentials['user_secret']
			);
		} catch ( Exception $e ) {
			die( var_dump( $e->getMessage() ) );
		}
	}

	protected function request( $path = '', $method = 'GET', $post = array() ) {
		if ( CachedResponse::isCached( $path, $method, $post ) ) {
			return CachedResponse::getCached( $path, $method, $post );
		}

		$response = $this->connection->http_request( $path, $method, $post );
		$response = PluginHelpers::jsonDecode( $response );

		if ( gettype( $response ) === 'string' ) {
			$this->last_error = $response;
		}

		return CachedResponse::setCached( $path, $method, $post, $response );
	}

	protected function validateSubscriber( $subscriber_data = array() ) {
		$not_valid_lists        = array();
		$validation_lists_names = array(
			'EMAILS_INVALID',
			'EMAILS_BANNED',
			'PHONES_INVALID',
			'BAD_PERSONAL_FIELDS',
			'ERRORS',
		);

		foreach ( $validation_lists_names as $list_name ) {
			if ( isset( $subscriber_data[ $list_name ] ) && ! empty( $subscriber_data[ $list_name ] ) ) {
				$not_valid_lists[ $list_name ] = $subscriber_data[ $list_name ];
			}
		}

		return $not_valid_lists;
	}

	protected function validateSubscriberExistance( $subscriber_data = array() ) {
		$existence_lists       = array();
		$existence_lists_names = array(
			'EMAILS_EXISTING',
			'PHONES_EXISTING',
		);

		foreach ( $existence_lists_names as $list_name ) {
			if ( isset( $subscriber_data[ $list_name ] ) && ! empty( $subscriber_data[ $list_name ] ) ) {
				$existence_lists[ $list_name ] = $subscriber_data[ $list_name ];
			}
		}

		return $existence_lists;
	}
}
