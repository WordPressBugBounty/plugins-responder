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

	private $last_response_empty = false;

	const SUBSCRIBERS_PAGE_SIZE = 100; // PR1-I17: rows per page in the "נמענים" tab.
	const SUBSCRIBERS_PAGE_MAX  = 500;

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
			$subscriber       = new FieldsParser( $subscriber_details );
			$this->last_error = '';
			$response         = $this->request( '/subscribers', 'POST', $subscriber->getFields() );

			// PR1-I10: request() returns array() when the API answered with an error (wrong / expired
			// Token) or did not answer. Report it instead of treating the lead as sent.
			if ( empty( $response ) ) {
				$this->submit_error = ! empty( $this->last_error ) ? $this->last_error : 'empty_response';
				return false;
			}

			$invalid_subscriber = $this->validateSubscriber( $response );
			if ( ! empty( $invalid_subscriber ) ) {
				// PR1-I10: "status": false = the API did not save the lead (e.g. list not found).
				// Logged once, as a failed submission (RMP_submission_failed).
				$this->submit_error = wp_json_encode( $response, JSON_UNESCAPED_UNICODE );
				return false;
			}

			$this->broadcast( 'create_subscriber', $subscriber_details, $response );

			$existing_subscriber_id = $this->validateSubscriberExistance( $response );
			if ( ! empty( $existing_subscriber_id ) ) {
				$subscriber_details['subscriber_id'] = $existing_subscriber_id;
				$this->addTagsToExistingSubscriber( $subscriber->getFields(), $subscriber_details );
				$this->updateSubscriber( $subscriber_details );
				$this->resubscribeExistingSubscriberToSms( $subscriber->getFields(), $subscriber_details );
			}
		}
	}

	/**
	 * PR1-I30: "אם הנמען ביקש הסרה מ-SMS, להחזיר אותו לקבלת SMS" (per form, off by default).
	 * POST /subscribers turns only the email back on for an existing contact (unsubscribed=false);
	 * the SMS opt-out (unsubscribed_phone) is changed only by PUT /subscribers/{id}. Sent only when
	 * the option is on and the visitor filled in a phone in this form.
	 */
	private function resubscribeExistingSubscriberToSms( $fields = array(), $subscriber_details = array() ) {
		$subscriber_id = (int) PluginHelpers::getVal( $subscriber_details, 'subscriber_id', 0 );

		if ( empty( $subscriber_id ) || empty( $fields['phone'] ) ) {
			return;
		}

		if ( ! PluginHelpers::ifExistsAndEqual( $subscriber_details, 'onexisting_sms', 'sms' ) ) {
			return;
		}

		// The API answers this endpoint with "status": false also when it saved the change, so the
		// answer is only logged (Debug), not used to fail the submission.
		$response = $this->request( "/subscribers/{$subscriber_id}", 'PUT', array( 'unsubscribed_phone' => false ) );
		$this->broadcast( 'update_subscriber', $subscriber_details, $response );
	}

	/**
	 * The form's tags are added to a contact who is already in the account, also when
	 * "להחליף את הפרטים הקיימים" is off (PR1-I21). Without override the API ignores the
	 * tags of an existing contact, so only the identifier, list and tags are sent again
	 * with override - name, phone and custom fields are not sent and stay as they are.
	 */
	private function addTagsToExistingSubscriber( $fields = array(), $subscriber_details = array() ) {
		if ( empty( $fields['tags'] ) || ! empty( $fields['override'] ) ) {
			return;
		}

		$tags_only = array(
			'list_ids'     => $fields['list_ids'],
			'tags'         => $fields['tags'],
			'unsubscribed' => false,
			'override'     => true,
		);

		if ( ! empty( $fields['email'] ) ) {
			$tags_only['email'] = $fields['email'];
		} elseif ( ! empty( $fields['phone'] ) ) {
			$tags_only['phone'] = $fields['phone'];
		} else {
			return;
		}

		$response = $this->request( '/subscribers', 'POST', $tags_only );
		$this->broadcast( 'update_subscriber', $subscriber_details, $response );
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

		// PR1-I32: a field can come in both arrays (e.g. list 0 returns the account fields twice) -
		// keep it once, or the Elementor widget declares the same control twice (PHP notices).
		$unique_fields = array();
		foreach ( $all_fields as $field ) {
			$field_id = (string) PluginHelpers::getVal( $field, 'id', '' );
			if ( '' === $field_id || ! isset( $unique_fields[ $field_id ] ) ) {
				$unique_fields[ '' === $field_id ? 'i' . count( $unique_fields ) : $field_id ] = $field;
			}
		}
		$all_fields = array_values( $unique_fields );

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

	/**
	 * PR1-I17: false when the list could not be loaded (no answer / error from the API), so the
	 * "נמענים" tab shows an error instead of an empty list or a PHP fatal error.
	 */
	public function getSubscribersByListId( $list_id = 0 ) {
		$subscribers_list = array();
		$this->last_error = '';
		$subscribers      = $this->request( "lists/{$list_id}/subscribers" );

		if ( ! is_array( $subscribers ) || ( empty( $subscribers ) && ( $this->last_response_empty || ! empty( $this->last_error ) ) ) ) {
			return false;
		}

		$fields = $this->request( "lists/{$list_id}/fields" );

		foreach ( $subscribers as $subscriber ) {
			if ( ! is_array( $subscriber ) ) {
				continue;
			}

			$subscriber_details = array(
				esc_html__( 'כתובת מייל', 'responder' ) => PluginHelpers::getVal( $subscriber, 'email' ),
				esc_html__( 'שם', 'responder' )         => PluginHelpers::getVal( $subscriber, 'name' ),
				esc_html__( 'טלפון', 'responder' )      => PluginHelpers::getVal( $subscriber, 'phone' ),
			);

			$personal_details = new FieldsFormatter(
				$fields,
				PluginHelpers::getVal( $subscriber, 'personal_fields', array() )
			);

			if ( $personal_details->fieldsCount() > 0 ) {
				  $subscriber_details = array_merge( $subscriber_details, $personal_details->getNameValueFields() );
			}

			array_push( $subscribers_list, $subscriber_details );
		}

		return $subscribers_list;
	}

	/**
	 * PR1-I17: one page of a list's subscribers, for the "נמענים" tab (large lists did not load in one call).
	 * Uses POST /subscribers/search with offset + size. searchKey is required by the API (an empty one is refused).
	 * With an empty search box a single space is sent: it matches every subscriber, also those with a phone and
	 * no email ("@", used before, matched emails only - PR1-I17 follow-up, checked live on lists 11040 / 10707).
	 *
	 * @return array|false array( 'rows', 'columns', 'count' (subscribers in the list), 'matched' (API count) ) or false.
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
		$query            = http_build_query(
			array(
				'listId'    => $list_id,
				'searchKey' => '' === $search ? ' ' : $search,
				'offset'    => $offset,
				'size'      => $size,
			)
		);
		$result           = $this->request( 'subscribers/search?' . $query, 'POST' );

		// No answer, an API error, or a reply without "subscribers" (e.g. 400 "Missing required request parameters").
		if ( ! is_array( $result ) || ! isset( $result['subscribers'] ) || ! is_array( $result['subscribers'] ) ) {
			return false;
		}

		$subscribers = $result['subscribers'];
		$fields      = $this->request( "lists/{$list_id}/fields" );
		$sheet       = $this->getSubscribersSheetColumns( $fields );
		$columns     = $sheet['labels'];
		$column_ids  = $sheet['ids'];
		$rows        = array();

		foreach ( (array) $subscribers as $subscriber ) {
			if ( ! is_array( $subscriber ) ) {
				continue;
			}

			// The fixed columns by position (PR1-I18: a personal field may have the same name, e.g. "סטטוס").
			$row = array(
				PluginHelpers::getVal( $subscriber, 'email' ),
				PluginHelpers::getVal( $subscriber, 'name' ),
				PluginHelpers::getVal( $subscriber, 'phone' ),
				self::getSubscriberStatusLabel( $subscriber ),
			);

			$personal_details = new FieldsFormatter( $fields, PluginHelpers::getVal( $subscriber, 'personal_fields', array() ) );
			$personal_values  = $personal_details->fieldsCount() > 0 ? $personal_details->getIdValueFields() : array();

			// Same column order on every page: a value per personal field column (by field id - PR1-I26), empty when the subscriber has none.
			foreach ( $column_ids as $field_id ) {
				$row[] = isset( $personal_values[ $field_id ] ) ? $personal_values[ $field_id ] : '';
			}

			foreach ( $row as $index => $value ) {
				$row[ $index ] = is_scalar( $value ) ? (string) $value : '';
			}
			$rows[] = $row;
		}

		// The search "count" is the number of all matches (36,857 for a 36K list, any page size).
		$matched    = PluginHelpers::getVal( $result, 'count', null );
		$matched    = is_numeric( $matched ) ? (int) $matched : null;
		$list_count = $this->getListSubscribersCount( $list_id );

		return array(
			'rows'     => $rows,
			'columns'  => $columns,
			'count'    => null !== $list_count ? $list_count : ( '' === $search ? $matched : null ),
			'matched'  => $matched,
			'offset'   => $offset,
			'size'     => $size,
			'received' => count( $subscribers ),
		);
	}

	/**
	 * PR1-I17: the tab's columns - email, name, phone, status, then the list's and all-lists personal fields.
	 * PR1-I26: one column per field id. Two fields with the same name (e.g. "utm_source") get their own columns,
	 * labelled "name (id)", instead of sharing one column where the later (often empty) value hid the other.
	 *
	 * @return array array( 'labels' => column titles, 'ids' => field id of each personal-field column ).
	 */
	private function getSubscribersSheetColumns( $fields ) {
		$personal_fields           = PluginHelpers::getVal( $fields, 'personal_fields', array() );
		$all_lists_personal_fields = PluginHelpers::getVal( $fields, 'all_lists_personal_fields', array() );
		$labels                    = array(
			esc_html__( 'כתובת מייל', 'responder' ),
			esc_html__( 'שם', 'responder' ),
			esc_html__( 'טלפון', 'responder' ),
			esc_html__( 'סטטוס ברשימה', 'responder' ), // PR1-I18
		);
		$personal = array(); // field id => name

		foreach ( array_merge( (array) $personal_fields, (array) $all_lists_personal_fields ) as $field ) {
			if ( ! is_array( $field ) || ! isset( $field['id'] ) ) {
				continue;
			}

			$name = (string) PluginHelpers::getVal( $field, 'name', '' );
			$id   = (string) $field['id'];

			if ( '' !== $name && ! isset( $personal[ $id ] ) ) {
				$personal[ $id ] = $name;
			}
		}

		$name_counts = array_count_values( $personal );

		foreach ( $personal as $id => $name ) {
			$labels[] = $name_counts[ $name ] > 1 ? $name . ' (' . $id . ')' : $name;
		}

		return array(
			'labels' => $labels,
			'ids'    => array_map( 'strval', array_keys( $personal ) ),
		);
	}

	/**
	 * PR1-I18: the subscriber's status in the list, from the search fields blocked / unsubscribed /
	 * inactive / inactive_account / active (0 or 1). Empty when the API sent none of them.
	 */
	public static function getSubscriberStatusLabel( $subscriber ) {
		$flag = function ( $key ) use ( $subscriber ) {
			$value = PluginHelpers::getVal( $subscriber, $key, null );
			return null === $value || '' === $value ? null : (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		};

		if ( $flag( 'blocked' ) ) {
			return esc_html__( 'חסום', 'responder' );
		}

		if ( $flag( 'unsubscribed' ) ) {
			return esc_html__( 'ביקש הסרה', 'responder' );
		}

		if ( $flag( 'inactive' ) || $flag( 'inactive_account' ) || false === $flag( 'active' ) ) {
			return esc_html__( 'לא פעיל', 'responder' );
		}

		if ( null === $flag( 'active' ) && null === $flag( 'inactive' ) ) {
			return '';
		}

		return esc_html__( 'פעיל', 'responder' );
	}

	/**
	 * PR1-I17: the number of subscribers in a list ("count" in GET /lists), or null when unknown.
	 */
	public function getListSubscribersCount( $list_id = 0 ) {
		foreach ( (array) $this->request( 'lists' ) as $list ) {
			if ( is_array( $list ) && (int) PluginHelpers::getVal( $list, 'id', 0 ) === (int) $list_id ) {
				$count = PluginHelpers::getVal( $list, 'count', null );
				return is_numeric( $count ) ? (int) $count : null;
			}
		}

		return null;
	}

	public function getSubscribersSheetByListId( $list_id = 0 ) {
		$subscribers_list = $this->getSubscribersByListId( $list_id );

		if ( false === $subscribers_list ) {
			return false;
		}

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
				array_push( $subscribers_sheet['columns'], PluginHelpers::getVal( $field, 'name', '' ) );
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
			$this->last_response_empty = false;
			return CachedResponse::getCached( $path, $method, $post );
		}

		// PR1-I28: no connection object when the system has no saved credentials - no request, no fatal error.
		if ( ! is_object( $this->connection ) ) {
			$this->last_error          = 'rmp_system_not_connected';
			$this->last_response_empty = true;
			return array();
		}

		$raw_response = $this->connection->httpRequest( $path, $method, $post );
		$response     = PluginHelpers::jsonDecode( $raw_response );

		// PR1-I17: an empty body means the API gave no answer (e.g. the 30 s timeout) - not "no data".
		$this->last_response_empty = ( '' === trim( (string) $raw_response ) );

		// A reply that is not JSON (e.g. "Unauthorized ..." from the SDK) is kept as the error text (PR1-I10).
		if ( empty( $response ) && is_string( $raw_response ) && '' !== trim( $raw_response ) && null === json_decode( $raw_response ) ) {
			$this->last_error = $raw_response;
			return array();
		}

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

		if ( is_string( $subscriber_data ) || ( isset( $subscriber_data['status'] ) && $subscriber_data['status'] === false ) ) {
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
