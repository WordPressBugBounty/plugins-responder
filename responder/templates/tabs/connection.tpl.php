<?php
  $responder_options = RavMesser\Plugin\OptionsManager::run()->getOptions( array( 'Responder_Plugin_EnterUsername', 'Responder_Plugin_EnterPassword' ) );
  $responder_options = array(
	  array(
		  'key'   => 'Responder_Plugin_EnterUsername',
		  'value' => $responder_options['Responder_Plugin_EnterUsername'],
		  'label' => esc_html__( 'מפתח', 'responder' ),
	  ),
	  array(
		  'key'   => 'Responder_Plugin_EnterPassword',
		  'value' => $responder_options['Responder_Plugin_EnterPassword'],
		  'label' => esc_html__( 'סוד', 'responder' ),
	  ),
  );

  $responder_live_user_token = RavMesser\Plugin\OptionsManager::run()->getOption( 'responder_live_user_token' );
  $is_responder_live_enabled = RavMesser\Plugin\API::isResponderLiveEnabled();
  $not_valid_systems_auth    = RavMesser\Plugin\API::notValidSystemsAuth();
  $connection_tab_url        = RavMesser\Plugin\SettingsPage::getUrl( '#plugin_config-2' );

	?>

<h1>
  <?php esc_html_e( 'חיבור לרב מסר', 'responder' ); ?>
</h1>

<div class="responder-settings-page-wraper">

  <?php if ( $not_valid_systems_auth ) : ?>
	<p>בקשו מצוות התמיכה של רב מסר אסימונים (טוקנים) לתוסף הטפסים של וורדפרס.</p>
	<p>אלה מאפשרים את החיבור המאובטח בין אתר הוורדפרס שלכם לבין המשתמש שלכם ברב מסר.</p>
	<p>הכניסו את האסימונים בשדות <strong>מפתח </strong>ו<strong>סוד </strong>ולחצו על שמירה.</p>
	<p>ודאו שקיבלתם הודעה על תקינות החיבור וראו כי נוספו לשוניות- טפסים, טפסים לאלמנטור ונמענים לתפריט הראשי.</p>
  <?php endif ?>

  <form method="post" action="<?php echo esc_url( $connection_tab_url ); ?>">
	<input type="hidden" name="rmp_action" value="connection_settings" />
	<?php settings_fields( RAV_MESSER_OPTIONS_GROUP ); ?>

	<table class="plugin-options-table">
	  <tr>
		<th colspan="2" align="right">
		  <h3><?php esc_html_e( 'חיבור לרב מסר', 'responder' ); ?></h3>
		</th>
	  </tr>

	  <?php foreach ( $responder_options as $option ) : ?>
		<tr valign="top">
		  <th scope="row">
			<p>
			  <label for="<?php echo esc_attr( $option['key'] ); ?>">
				<?php echo esc_html( $option['label'] ); ?>
			  </label>
			</p>
		  </th>
		  <td>
			<p>
			  <input type="text" size="50"
				id="<?php echo esc_attr( $option['key'] ); ?>"
				name="<?php echo esc_attr( 'responder[' . $option['key'] . ']' ); ?>"
				value="<?php echo esc_attr( $option['value'] ); ?>"
			  />
			</p>
		  </td>
		</tr>
	  <?php endforeach ?>

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
	switch ( RavMesser\Plugin\API::run( 'responder' )->getState() ) {
		case 'not_full':
			RavMesser\Plugin\AdminNotices::authEmptyError( 'responder' );
			break;

		case 'auth_error':
			RavMesser\Plugin\AdminNotices::authError( '', RavMesser\Plugin\API::run( 'responder' )->last_error );
			break;

		case 'auth_success':
			RavMesser\Plugin\AdminNotices::authSuccess();
			break;
	}
	?>

  <?php if ( $is_responder_live_enabled ) : ?>

	<form method="post" action="<?php echo esc_url( $connection_tab_url ); ?>">
	  <input type="hidden" name="rmp_action" value="connection_settings" />
		<?php settings_fields( RAV_MESSER_OPTIONS_GROUP ); ?>

	  <table class="plugin-options-table">
		<tr>
		  <th colspan="2" align="right">
			<h3>
				<?php esc_html_e( 'חיבור לרב מסר - מערכת חדשה', 'responder' ); ?>
			</h3>

			<a href="https://www.youtube.com/watch?v=CD6D8Pi7z0Y" target="_blank">
				<?php esc_html_e( 'צפייה בסרטון הדרכה', 'responder' ); ?>
			</a>
		  </th>
		</tr>

		<tr valign="top">
		  <th scope="row">
			<p>
			  <label for="responder_live_user_token">
				<?php esc_html_e( 'מפתח', 'responder' ); ?>
			  </label>
			</p>
		  </th>
		  <td>
			<p>
			  <input type="text" size="50"
				id="responder_live_user_token"
				name="responder[responder_live_user_token]"
				value="<?php echo esc_attr( $responder_live_user_token ); ?>"
			  />
			</p>
		  </td>
		</tr>

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
		switch ( RavMesser\Plugin\API::run( 'responder_live' )->getState() ) {
			case 'not_full':
				RavMesser\Plugin\AdminNotices::authEmptyError( 'responder_live' );
				break;

			case 'auth_error':
				RavMesser\Plugin\AdminNotices::authError( '', RavMesser\Plugin\API::run( 'responder_live' )->last_error );
				break;

			case 'auth_success':
				RavMesser\Plugin\AdminNotices::authSuccess();
				break;
		}
		?>

  <?php endif ?>

</div>
