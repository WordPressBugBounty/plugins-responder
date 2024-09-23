<?php namespace RavMesser\API\Responder;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

class FieldsFormatter {

	const FIELDS_MAP = array(
		0 => 'text',
		1 => 'date',
	);

	public static function getTypeById( $type_id = 0 ) {
		return self::FIELDS_MAP[ $type_id ];
	}
}
