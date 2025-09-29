<form id="uc_advanced_settings_wrapper" method="post" action="<?php echo esc_url( RavMesser\Plugin\SettingsPage::getUrl( '#plugin_config-advanced' ) ); ?>">
	<input type="hidden" name="rmp_action" value="debugger_settings" />

	<h1>
		<?php esc_html_e( 'הגדרות מתקדמות', 'responder' ); ?>
	</h1>

	<p>
		<?php esc_html_e( 'כאן תוכלו להגדיר הגדרות מתקדמות', 'responder' ); ?>
	</p>

	<div id="unite_settings_wide_output" class="unite_settings_wrapper unite_settings_wide unite-settings unite-inputs">

	<table class="unite_table_settings_wide">
	  <tbody>
	  <tr valign="top">
		  <th scope="row">
			<?php esc_html_e( 'מיקום הפלאגין:', 'responder' ); ?>
		  </th>

		  <td>
			<pre style="display:inline-block;direction:ltr;margin:0"><?php echo esc_html( RAV_MESSER_PLUGIN_PATH ); ?></pre>
		  </td>
		</tr>

		<tr valign="top">
		  <th scope="row">
			<?php esc_html_e( 'גרסת WordPress:', 'responder' ); ?>
		  </th>

		  <td>
			<pre style="display:inline-block;direction:ltr;margin:0"><?php
				global $wp_version;
				echo esc_html( $wp_version )
			?></pre>
		  </td>
		</tr>

		<tr valign="top">
		  <th scope="row">
			<?php esc_html_e( 'גרסת PHP:', 'responder' ); ?>
		  </th>

		  <td>
			<pre style="display:inline-block;direction:ltr;margin:0"><?php echo esc_html( PHP_VERSION ); ?></pre>
		  </td>
		</tr>

		<tr valign="top">
		  <th scope="row">
			<?php esc_html_e( 'זמן שרת:', 'responder' ); ?>
		  </th>

		  <td>
			<pre style="display:inline-block;direction:ltr;margin:0"><?php echo date('Y-m-d H:i:s P', time()); ?></pre>
		  </td>
		</tr>

		<tr valign="top">
		  <th scope="row">
			<?php esc_html_e( 'תמיכה ב-cURL:', 'responder' ); ?>
		  </th>

		  <td>
			<?php
			  $cURL_support = esc_html__( 'לא', 'responder' );

			if ( function_exists( 'curl_init' ) ) {
				$cURL_support = esc_html__( 'כן', 'responder' );
			}
			?>
			<pre style="display:inline-block;direction:ltr;margin:0"><?php echo esc_html( $cURL_support ); ?></pre>
		  </td>
		</tr>

		<tr valign="top">
		  <th scope="row">
			<?php esc_html_e( 'לאפשר DEBUG', 'responder' ); ?>
		  </th>

		  <?php $is_debugger_active = RavMesser\Plugin\SettingsPage::isDebuggerActive(); ?>
		  <td>
			<span class="radio_wrapper">
			  <label for="enable_debug_1">
				<input type="radio" id="enable_debug_1" value="true" name="responder[enable_debug]" <?php echo esc_attr( $is_debugger_active ? 'checked' : '' ); ?> />
				<?php esc_html_e( 'כן', 'responder' ); ?>
			  </label>
			  &nbsp;
			  &nbsp;
			  <label for="enable_debug_2">
				<input type="radio" id="enable_debug_2" value="false" name="responder[enable_debug]" <?php echo esc_attr( ! $is_debugger_active ? 'checked' : '' ); ?> />
				<?php esc_html_e( 'לא', 'responder' ); ?>
			  </label>
			</span>
		  </td>
		</tr>

	  </tbody>
	</table>

  </div>

  <div class="vert_sap30"></div>

  <button type="submit" name="save_cha" class="res-button-primary">
	<?php esc_html_e( 'שמירת הגדרות מתקדמות', 'responder' ); ?>
  </button>
</form>
