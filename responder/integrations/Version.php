<?php namespace RavMesser\Integrations;

defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

class Version {

	public static function isGreaterOrEqualTo( $integration_name, $version ) {
		$current_version = self::getIntegrationVersion( $integration_name );

		return self::compare( $current_version, $version, 'ge' );
	}

	public static function isLessThen( $integration_name, $version ) {
		$current_version = self::getIntegrationVersion( $integration_name );

		return self::compare( $current_version, $version, 'lt' );
	}

	private static function compare( $current_version, $version_in_question, $operator ) {
		return version_compare( $current_version, $version_in_question, $operator );
	}

	private static function getIntegrationConstant( $integration_name = '' ) {
		switch ( $integration_name ) {
			case 'elementor':
				return 'ELEMENTOR_VERSION';
			break;
			case 'elementor_pro':
				return 'ELEMENTOR_PRO_VERSION';
			break;
			default:
				return 'NOT_A_REAL_INTEGRATION';
			break;
		}
	}

	private static function getIntegrationVersion( $integration_name ) {
		$version_constant    = self::getIntegrationConstant( $integration_name );
		$integration_version = '0.0.0';

		if ( defined( $version_constant ) ) {
			$escaped_version = esc_attr( constant( $version_constant ) );

			if ( ! empty( $escaped_version ) ) {
				$integration_version = $escaped_version;
			}
		}

		return $integration_version;
	}
}
