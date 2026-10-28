<?php
  $rmp_options_manager = RavMesser\Plugin\OptionsManager::run();
  $rmp_old_options     = $rmp_options_manager->getOptions( array( 'Responder_Plugin_EnterUsername', 'Responder_Plugin_EnterPassword' ) );

  $rmp_live_fields = array(
	  array(
		  'key'   => 'responder_live_user_token',
		  'value' => $rmp_options_manager->getOption( 'responder_live_user_token' ),
		  'label' => esc_html__( 'Token', 'responder' ),
	  ),
  );

  $rmp_old_fields = array(
	  array(
		  'key'   => 'Responder_Plugin_EnterUsername',
		  'value' => $rmp_old_options['Responder_Plugin_EnterUsername'],
		  'label' => esc_html__( 'מפתח', 'responder' ),
	  ),
	  array(
		  'key'   => 'Responder_Plugin_EnterPassword',
		  'value' => $rmp_old_options['Responder_Plugin_EnterPassword'],
		  'label' => esc_html__( 'סוד', 'responder' ),
	  ),
  );

  $rmp_live_api   = RavMesser\Plugin\API::run( 'responder_live' );
  $rmp_old_api    = RavMesser\Plugin\API::run( 'responder' );
  // PR1-I27: check each filled-in connection here (the lists call is cached, so no extra API call when the
  // "נמענים" tab already made it) - the state must not depend on another tab having run first.
  foreach ( array( $rmp_live_api, $rmp_old_api ) as $rmp_api ) {
	  if ( $rmp_api->areUserCredentialsValid() && empty( $rmp_api->last_error ) ) {
		  $rmp_api->getLists();
	  }
  }

  $rmp_live_state = $rmp_live_api->getState();
  $rmp_old_state  = $rmp_old_api->getState();

  // The old system's fields are optional now: an empty pair means "not used".
  $rmp_old_partly_filled = ( '' !== (string) $rmp_old_options['Responder_Plugin_EnterUsername'] ) xor ( '' !== (string) $rmp_old_options['Responder_Plugin_EnterPassword'] );

  $rmp_connection_tab_url = RavMesser\Plugin\SettingsPage::getUrl( '#plugin_config-2' );
  $rmp_connect_help_link  = 'https://support.responder.co.il/portal/he/kb/articles/%D7%AA%D7%95%D7%A1%D7%A3-%D7%94%D7%98%D7%A4%D7%A1%D7%99%D7%9D-%D7%A9%D7%9C-%D7%A8%D7%91-%D7%9E%D7%A1%D7%A8-%D7%9C-wordpress';
?>

<div class="responder-settings-page-wraper">

  <h1 class="responder-connection-title"><?php esc_html_e( 'חיבור לרב מסר', 'responder' ); ?></h1>

  <p class="responder-connection-help">
	<?php esc_html_e( 'צריכים עזרה להתחבר?', 'responder' ); ?>
	<a href="<?php echo esc_url( $rmp_connect_help_link ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'כל המידע כאן', 'responder' ); ?></a>
  </p>

  <form method="post" action="<?php echo esc_url( $rmp_connection_tab_url ); ?>" autocomplete="off">
	<?php wp_nonce_field( 'responder_settings', 'responder_nonce' ); ?>
	<input type="hidden" name="rmp_action" value="connection_settings" />
	<?php settings_fields( RAV_MESSER_OPTIONS_GROUP ); ?>

	<table class="plugin-options-table">
	  <?php
		foreach ( $rmp_live_fields as $rmp_field ) {
			include RAV_MESSER_TEMPLATES_DIR . '/partials/secret_field.tpl.php';
		}
		?>
	  <tr>
		<td colspan="2">
		  <p class="submit">
			<input type="submit" name="save_cha" class="button-primary" value="<?php esc_html_e( 'שמירה', 'responder' ); ?>" />
		  </p>
		</td>
	  </tr>
	</table>
  </form>

  <?php
	switch ( $rmp_live_state ) {
		case 'not_full':
			// Show the "copy your Token" hint only while no system is connected at all.
			if ( ! $rmp_old_api->isValid() ) {
				RavMesser\Plugin\AdminNotices::authEmptyError( 'responder_live' );
			}
			break;

		case 'auth_error':
			RavMesser\Plugin\AdminNotices::authError( 'responder_live', $rmp_live_api->last_error );
			break;

		case 'auth_success':
			RavMesser\Plugin\AdminNotices::authSuccess();
			break;
	}
	?>

  <h1 class="responder-connection-title responder-connection-title-old"><?php esc_html_e( 'חיבור לרב מסר הישנה', 'responder' ); ?></h1>

  <form method="post" action="<?php echo esc_url( $rmp_connection_tab_url ); ?>" autocomplete="off">
	<?php wp_nonce_field( 'responder_settings', 'responder_nonce' ); ?>
	<input type="hidden" name="rmp_action" value="connection_settings" />
	<?php settings_fields( RAV_MESSER_OPTIONS_GROUP ); ?>

	<table class="plugin-options-table">
	  <?php
		foreach ( $rmp_old_fields as $rmp_field ) {
			include RAV_MESSER_TEMPLATES_DIR . '/partials/secret_field.tpl.php';
		}
		?>
	  <tr>
		<td colspan="2">
		  <p class="submit">
			<input type="submit" name="save_cha" class="button-primary" value="<?php esc_html_e( 'שמירה', 'responder' ); ?>" />
		  </p>
		</td>
	  </tr>
	</table>
  </form>

  <?php
	switch ( $rmp_old_state ) {
		case 'not_full':
			// Both fields empty = the old system is not used: no error. Only one filled = remind to fill both.
			if ( $rmp_old_partly_filled ) {
				RavMesser\Plugin\AdminNotices::authEmptyError( 'responder' );
			}
			break;

		case 'auth_error':
			RavMesser\Plugin\AdminNotices::authError( 'responder', $rmp_old_api->last_error );
			break;

		case 'auth_success':
			RavMesser\Plugin\AdminNotices::authSuccess();
			break;
	}
	?>

</div>
