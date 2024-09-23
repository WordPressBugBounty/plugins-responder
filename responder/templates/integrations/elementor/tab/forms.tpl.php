<?php

use RavMesser\Plugin\API as PluginAPI;

$rmp_systems_names = PluginAPI::getConnectedSystemsNames();
$rmp_systems_count = count( $rmp_systems_names );
$rmp_chosen_system = $rmp_systems_names[0];

?>

<div class="unite_settings_wrapper unite_settings_wide unite-settings unite-inputs">

  <table class="unite_table_settings_wide">
	<tr id="elementor_forms_row" valign="middle">
	  <td colspan="2">

		<div id="elementor_forms"
		  class="unite-settings-repeater unite-setting-input-object"
		  data-itemvalues='<?php echo esc_attr( RavMesser\Plugin\SettingsPage::getElementorFormsSettings() ); ?>'>

		  <div class="unite-repeater-emptytext">
			<?php esc_html_e( 'לא נמצאו טפסים', 'responder' ); ?>
		  </div>

		  <div class="unite-repeater-template unite-repeater-item"
			data-itemvalue="">
			<div class="unite-repeater-item-head">
			  <a class="unite-repeater-trash unite-repeater-buttondelete"
				title="<?php esc_html_e( 'מחיקת טופס', 'responder' ); ?>">
				<i class="fa fa-trash" aria-hidden="true"></i>
			  </a>
			  <div class="unite-repeater-arrow"></div>
			  <span class="unite-repeater-title">
				<?php esc_html_e( 'טופס', 'responder' ); ?>
			  </span>
			</div>

			<div class="unite-repeater-item-settings">
			  <div class="unite_settings_wrapper unite_settings_wide unite-settings unite-inputs">

				<table class='unite_table_settings_wide'>

				  <?php if ( $rmp_systems_count > 1 ) : ?>
					<tr valign="middle">
					  <th scope="row">
						<?php esc_html_e( 'בחירת מערכת', 'responder' ); ?>
					  </th>
					  <td>
						<select name="chosen_system">
						  <option value="responder" selected="selected">
							<?php esc_html_e( 'רב מסר', 'responder' ); ?>
						  </option>
						  <option value="responder_live">
							<?php esc_html_e( 'רב מסר - מערכת חדשה', 'responder' ); ?>
						  </option>
						</select>
					  </td>
					</tr>
				  <?php else : ?>
					<input type="hidden" name="chosen_system" value="<?php echo esc_attr( $rmp_chosen_system ); ?>" />
				  <?php endif ?>

				  <tr valign="middle">
					<th scope="row">
					  <?php esc_html_e( 'שם הטופס (השם מוסתר למבקרים באתר)', 'responder' ); ?>
					</th>
					<td>
					  <input class="unite-input-regular" name="title" type="text" value="" />
					</td>
				  </tr>

				  <tr valign="middle">
					<th scope="row">
					  <?php esc_html_e( 'הרשימה ברב מסר אליה יוכנסו פרטי הנמענים', 'responder' ); ?>
					</th>
					<td>
					  <select name="list_id">
						<option disabled selected value=""><?php esc_html_e( 'בחירת רשימה', 'responder' ); ?></option>
					  </select>
					</td>
				  </tr>

				  <tr valign="middle">
					<th scope="row">
					  <?php esc_html_e( 'סוג הטופס', 'responder' ); ?>
					</th>
					<td>
					  <select name="form_defaults_type">
						<option value="vert" selected="selected">
						  <?php esc_html_e( 'טופס עומד', 'responder' ); ?>
						</option>
						<option value="hor">
						  <?php esc_html_e( 'טופס שוכב', 'responder' ); ?>
						</option>
					  </select>
					</td>
				  </tr>

				  <tr valign="middle" class="hidden">
					<th scope="row">
					  <?php esc_html_e( 'אם הנמען קיים ברשימה', 'responder' ); ?>
					</th>
					<td>
					  <select name="action_on_existing">
						<option value="update" selected="selected">
						  <?php esc_html_e( 'שמור ותק ועדכן פרטים', 'responder' ); ?>
						</option>
						<option value="resubscribe">
						  <?php esc_html_e( 'איפוס ותק והרשמה מחדש', 'responder' ); ?>
						</option>
					  </select>
					</td>
				  </tr>

				  <tr valign="middle" class="hidden">
					<th scope="row">
					  <?php esc_html_e( 'הוספת תגיות לנמען', 'responder' ); ?>
					</th>
					<td>
					  <select name="tags" multiple="multiple">
					  </select>
					</td>
				  </tr>

				  <tr valign="middle" class="hidden">
					<th scope="row">
					  <?php esc_html_e( 'אם הנמען קיים, להחליף את הפרטים הקיימים שלו בפרטים החדשים', 'responder' ); ?>
					</th>
					<td>
					  <input type="checkbox" name="onexisting_rejoin" value="rejoin" />
					</td>
				  </tr>

				  <tr valign="middle" class="hidden">
					<th scope="row">
					  <?php esc_html_e( 'אם הנמען קיים, לאפס את הותק שלו ברשימה', 'responder' ); ?>
					</th>
					<td>
					  <input type="checkbox" name="onexisting_joindate" value="joindate" />
					</td>
				  </tr>
				</table>

			  </div>
			</div>
		  </div>

		  <a class="unite-button-secondary unite-repeater-buttonadd">
			<?php esc_html_e( 'טופס חדש', 'responder' ); ?>
		  </a>

		  <div class="unite-repeater-items"></div>
		</div>

	  </td>
	</tr>
  </table>

</div>
