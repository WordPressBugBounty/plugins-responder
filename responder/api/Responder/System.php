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

	private $last_response_empty = false;

	const SUBSCRIBERS_PAGE_SIZE = 500; // PR1-I17: rows per page in the "נמענים" tab (V1 maximum per call).
	const SUBSCRIBERS_PAGE_MAX  = 500; // V1 API: 0 <= limit <= 500.

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

			$this->last_error = '';
			$response         = $this->request( "/lists/{$list_id}/subscribers", 'POST', $subscriber_data );

			// PR1-I10: a wrong key ("Invalid access token ..."), a disabled account ("ERROR") or no reply
			// means the lead did not reach the system. Report it instead of treating it as sent.
			$api_error = $this->getApiError( $response );
			if ( '' !== $api_error ) {
				$this->submit_error = $api_error;
				return false;
			}

			$invalid_subscriber = $this->validateSubscriber( $response );

			// PR1-I10: a general API error ("ERRORS") means the lead was not saved - report it (logged
			// once, as a failed submission). Per-address rejections (invalid / banned email or phone)
			// keep the old behaviour.
			if ( ! empty( $invalid_subscriber['ERRORS'] ) ) {
				$this->submit_error = wp_json_encode( $invalid_subscriber['ERRORS'], JSON_UNESCAPED_UNICODE );
				return false;
			}

			$this->broadcast( 'create_subscriber', $subscriber_details, $response );

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

	/**
	 * PR1-I17: one page of a list's subscribers (רב מסר הישנה), for the "נמענים" tab.
	 * GET lists/{id}/subscribers with limit + offset (V1 caps limit at 500, so a large list was cut).
	 * V1 has no total count and no search: "count" is null (the tab pages with "next" until a short page),
	 * and a search looks up one subscriber by exact email or subscriber ID (subscriber_id).
	 *
	 * @return array|false array( 'rows', 'columns', 'count' => null, 'matched', 'offset', 'size', 'received' ) or false.
	 */
	public function getSubscribersSheetPageByListId( $list_id = 0, $offset = 0, $size = self::SUBSCRIBERS_PAGE_SIZE, $search = '' ) {
		$list_id = absint( $list_id );
		$offset  = max( 0, (int) $offset );
		$size    = min( self::SUBSCRIBERS_PAGE_MAX, max( 1, (int) $size ) );
		$search  = trim( (string) $search );

		if ( empty( $list_id ) ) {
			return false;
		}

		$this->last_error = '';

		if ( '' !== $search ) {
			// One exact match at most - nothing after the first page.
			$subscribers = 0 === $offset ? $this->request( "lists/{$list_id}/subscribers", 'GET', array( 'subscriber_id' => $search ) ) : array();
		} else {
			$subscribers = $this->request(
				"lists/{$list_id}/subscribers",
				'GET',
				array(
					'limit'  => $size,
					'offset' => $offset,
				)
			);
		}

		// No answer (timeout), an error text, or an object instead of the list of subscribers.
		$is_list = is_array( $subscribers ) && ( empty( $subscribers ) || array_keys( $subscribers ) === range( 0, count( $subscribers ) - 1 ) );

		if ( ! $is_list || ( empty( $subscribers ) && ( $this->last_response_empty || ! empty( $this->last_error ) ) ) ) {
			// A search for an email / ID that is not in the list is not an error.
			if ( '' !== $search && is_array( $subscribers ) && ! $this->last_response_empty ) {
				$subscribers = array();
			} else {
				return false;
			}
		}

		$personal_fields = PluginHelpers::getVal( $this->request( "lists/{$list_id}/personal_fields" ), 'PERSONAL_FIELDS', array() );
		$personal_fields = is_array( $personal_fields ) ? $personal_fields : array();
		$columns         = array(
			esc_html__( 'כתובת מייל', 'responder' ),
			esc_html__( 'שם', 'responder' ),
			esc_html__( 'טלפון', 'responder' ),
			esc_html__( 'סטטוס ברשימה', 'responder' ), // PR1-I18: the list includes inactive subscribers too
		);

		foreach ( $personal_fields as $personal_field ) {
			$columns[] = (string) PluginHelpers::getVal( $personal_field, 'NAME', '' );
		}

		$rows = array();

		foreach ( $subscribers as $subscriber ) {
			if ( ! is_array( $subscriber ) ) {
				continue;
			}

			$values = PluginHelpers::getVal( $subscriber, 'PERSONAL_FIELDS', array() );
			$values = is_array( $values ) ? $values : array();

			// The docs show PERSONAL_FIELDS as [ { id: value } ] - unwrap that form.
			if ( 1 === count( $values ) && isset( $values[0] ) && is_array( $values[0] ) ) {
				$values = $values[0];
			}

			$row = array(
				(string) PluginHelpers::getVal( $subscriber, 'EMAIL', '' ),
				(string) PluginHelpers::getVal( $subscriber, 'NAME', '' ),
				(string) PluginHelpers::getVal( $subscriber, 'PHONE', '' ),
				self::getSubscriberStatusLabel( $subscriber ),
			);

			foreach ( $personal_fields as $personal_field ) {
				$field_id = PluginHelpers::getVal( $personal_field, 'ID' );
				$value    = isset( $values[ $field_id ] ) && '' !== $values[ $field_id ] && null !== $values[ $field_id ]
					? $values[ $field_id ]
					: PluginHelpers::getVal( $personal_field, 'DEFAULT_VALUE', '' );
				$row[]    = is_scalar( $value ) ? (string) $value : '';
			}

			$rows[] = $row;
		}

		return array(
			'rows'     => $rows,
			'columns'  => $columns,
			'count'    => null,
			'matched'  => '' !== $search ? count( $rows ) : null,
			'offset'   => $offset,
			'size'     => $size,
			'received' => count( $subscribers ),
		);
	}

	/**
	 * PR1-I18: the subscriber's status in the list, from STATUS (1 active, 0 inactive) and STATUS_NUM
	 * (when inactive: 0 removed by automation, 1 removed manually / unsubscribed / blocked, 2 blocked - email bounces).
	 */
	public static function getSubscriberStatusLabel( $subscriber ) {
		$status = PluginHelpers::getVal( $subscriber, 'STATUS', '' );

		if ( '' === $status || null === $status ) {
			return '';
		}

		if ( 1 === (int) $status ) {
			return esc_html__( 'פעיל', 'responder' );
		}

		switch ( (int) PluginHelpers::getVal( $subscriber, 'STATUS_NUM', 0 ) ) {
			case 1:
				return esc_html__( 'הוסר או ביקש הסרה', 'responder' );
			case 2:
				return esc_html__( 'חסום (החזרות מייל)', 'responder' );
			default:
				return esc_html__( 'לא פעיל', 'responder' );
		}
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
			$this->last_response_empty = false;
			return CachedResponse::getCached( $path, $method, $post );
		}

		// PR1-I28: no connection object when the system has no saved credentials - no request, no fatal error.
		if ( ! is_object( $this->connection ) ) {
			$this->last_error          = 'rmp_system_not_connected';
			$this->last_response_empty = true;
			return array();
		}

		$raw_response = $this->connection->http_request( $path, $method, $post );
		$response     = PluginHelpers::jsonDecode( $raw_response );

		// PR1-I17: an empty body means the API gave no answer (e.g. the 30 s timeout) - not "no data".
		$this->last_response_empty = ( '' === trim( (string) $raw_response ) );

		// A reply that is not JSON (e.g. "Invalid access token ...") is kept as the error text (PR1-I10).
		if ( empty( $response ) && is_string( $raw_response ) && '' !== trim( $raw_response ) && null === json_decode( $raw_response ) ) {
			$response = $raw_response;
		}

		if ( gettype( $response ) === 'string' ) {
			$this->last_error = $response;
		}

		// PR1-I27: an account-level error comes back as JSON {"ERROR": "Account is disabled"} - keep it as
		// the error too, so the connection tab shows the red error instead of "מחובר בהצלחה".
		if ( is_array( $response ) && ! empty( $response['ERROR'] ) ) {
			$this->last_error = is_scalar( $response['ERROR'] ) ? (string) $response['ERROR'] : wp_json_encode( $response['ERROR'], JSON_UNESCAPED_UNICODE );
		}

		return CachedResponse::setCached( $path, $method, $post, $response );
	}

	/**
	 * The account-level error of an API reply, or '' when the reply is a normal answer (PR1-I10).
	 */
	protected function getApiError( $response ) {
		if ( is_string( $response ) ) {
			return '' !== trim( $response ) ? $response : 'empty_response';
		}

		if ( empty( $response ) ) {
			return ! empty( $this->last_error ) ? ( is_scalar( $this->last_error ) ? (string) $this->last_error : wp_json_encode( $this->last_error ) ) : 'empty_response';
		}

		if ( is_array( $response ) && ! empty( $response['ERROR'] ) ) {
			return is_scalar( $response['ERROR'] ) ? (string) $response['ERROR'] : wp_json_encode( $response['ERROR'], JSON_UNESCAPED_UNICODE );
		}

		return '';
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
