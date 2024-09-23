<?php

use RavMesser\Integrations\ContactForm7\DataManager as ContactForm7DataManager;
use RavMesser\Integrations\ContactForm7\FormHelper as ContactForm7FormHelper;
use RavMesser\Plugin\API as PluginAPI;
use RavMesser\Plugin\Helpers as PluginHelpers;

$settings          = ContactForm7DataManager::getFormSettings( $args->id() );
$rmp_systems_names = PluginAPI::getConnectedSystemsNames();
$rmp_systems_count = count( $rmp_systems_names );

$name  = PluginHelpers::getVal( $settings, 'name' );
$first = PluginHelpers::getVal( $settings, 'first' );
$last  = PluginHelpers::getVal( $settings, 'last' );
$email = PluginHelpers::getVal( $settings, 'email' );
$phone = PluginHelpers::getVal( $settings, 'phone' );

$chosen_system       = PluginHelpers::getVal( $settings, 'chosen_system', $rmp_systems_names[0] );
$list_id             = PluginHelpers::getVal( $settings, 'list' );
$url_redirect        = PluginHelpers::getVal( $settings, 'url_redirect' );
$url_open_new_tab    = PluginHelpers::getVal( $settings, 'url_open_new_tab' );
$pass_params         = PluginHelpers::getVal( $settings, 'pass_params' );
$on_existing         = PluginHelpers::getVal( $settings, 'onexisting' );
$onexisting_rejoin   = PluginHelpers::getVal( $settings, 'onexisting_rejoin' );
$onexisting_joindate = PluginHelpers::getVal( $settings, 'onexisting_joindate' );
$tags                = PluginHelpers::getVal( $settings, 'tags', array() );
$tags_data_attr      = '["' . implode( '","', $tags ) . '"]';
$direction_class     = is_rtl() ? 'rtl' : 'ltr';

$custom_fields = wp_json_encode( ContactForm7FormHelper::getCustomFieldsAndOptions( $settings ) );
?>

<div id="cf7-responder-panel" class="<?php echo esc_attr( 'metabox-holder metabox-responder ' . $direction_class ); ?>">
  <input type="hidden" name="responder[save_changes]" value="false" />

  <h2>
	<?php esc_html_e( 'הגדרות חיבור רב מסר עם הטופס', 'responder' ); ?>
  </h2>

  <p>
	<?php esc_html_e( 'כאן תוכלו לערוך שינויים בחיבור הטופס עם רב מסר. מומלץ לא לשנות את ההגדרות', 'responder' ); ?>
  </p>

  <div class="responder-main-fields">

	<?php if ( $rmp_systems_count > 1 ) : ?>
	  <div class="mail-field">
		<label class="mail-field-title" for="responder-chosen-system">
		  <?php esc_html_e( 'בחירת מערכת', 'responder' ); ?>
		</label>

		<select name="responder[chosen_system]" id="responder-chosen-system">
		  <option value="responder" <?php echo esc_attr( $chosen_system === 'responder' ? 'selected' : '' ); ?>>
			<?php esc_html_e( 'רב מסר', 'responder' ); ?>
		  </option>
		  <option value="responder_live" <?php echo esc_attr( $chosen_system === 'responder_live' ? 'selected' : '' ); ?>>
			<?php esc_html_e( 'רב מסר - מערכת חדשה', 'responder' ); ?>
		  </option>
		</select>
	  </div>
	<?php else : ?>
	  <input type="hidden" id="responder-chosen-system" name="responder[chosen_system]" value="<?php echo esc_attr( $chosen_system ); ?>" />
	<?php endif ?>

	<div class="mail-field">
	  <label for="responder-subscribers-list" class="mail-field-title">
		<?php esc_html_e( 'הרשימה ברב מסר אליה יוכנסו פרטי הנמענים', 'responder' ); ?>
	  </label>

	  <select name="responder[list]" id="responder-subscribers-list" data-value="<?php echo esc_attr( $list_id ); ?>">
		<option value="<?php echo esc_attr( $list_id ); ?>" selected></option>
	  </select>
	</div>

	<div class="mail-field hidden">
	  <label for="cf7_onexisting" class="mail-field-title">
		<?php esc_html_e( 'אם הנמען קיים ברשימה', 'responder' ); ?>
	  </label>

	  <select id="cf7_onexisting" name="responder[onexisting]">
		<option value="update" <?php echo esc_attr( $on_existing === 'update' ? 'selected' : '' ); ?>>
		  <?php esc_html_e( 'שמור ותק ועדכן פרטים', 'responder' ); ?>
		</option>
		<option value="resubscribe" <?php echo esc_attr( $on_existing === 'resubscribe' ? 'selected' : '' ); ?>>
		  <?php esc_html_e( 'איפוס ותק והרשמה מחדש', 'responder' ); ?>
		</option>
	  </select>
	</div>

	<div class="mail-field hidden">
	  <label for="responder-tags" class="mail-field-title">
		<?php esc_html_e( 'הוספת תגיות לנמען', 'responder' ); ?>
	  </label>

	  <select id="responder-tags" name="responder[tags][]" multiple="multiple" data-value='<?php echo esc_attr( $tags_data_attr ); ?>' disabled></select>
	</div>

	<div class="mail-field hidden">
	  <label class="mail-field-title">
		<?php esc_html_e( 'אם הנמען קיים ברשימה', 'responder' ); ?>
	  </label>

	  <p>
		<label for="onexisting_rejoin">
		  <input id="onexisting_rejoin" type="checkbox" name="responder[onexisting_rejoin]" value="rejoin" <?php echo esc_attr( $onexisting_rejoin ? 'checked' : '' ); ?> />
		  <?php esc_html_e( 'אם הנמען קיים, להחליף את הפרטים הקיימים שלו בפרטים החדשים', 'responder' ); ?>
		</label>
	  </p>

	  <p>
		<label for="onexisting_joindate">
		  <input id="onexisting_joindate" type="checkbox" name="responder[onexisting_joindate]" value="joindate" <?php echo esc_attr( $onexisting_joindate ? 'checked' : '' ); ?> />
		  <?php esc_html_e( 'אם הנמען קיים, לאפס את הותק שלו ברשימה', 'responder' ); ?>
		</label>
	  </p>
	</div>

	<div class="mail-field">
	  <label for="responder-thankyou-url" class="mail-field-title">
		<?php esc_html_e( 'כתובת עמוד תודה (רשות)', 'responder' ); ?>
	  </label>

	  <input id="responder-thankyou-url" type="text" name="responder[url_redirect]" placeholder="<?php esc_html_e( 'לדוגמה:', 'responder' ); ?> http://yoursite.com" value="<?php echo esc_url( $url_redirect ); ?>">

	  <p>
		<label>
		  <input type="checkbox" name="responder[url_open_new_tab]" <?php echo esc_attr( $url_open_new_tab ? 'checked' : '' ); ?> />
		  <?php esc_html_e( 'פתיחה בחלון חדש', 'responder' ); ?>
		</label>
	  </p>

	  <p>
		<label>
		  <input type="checkbox" name="responder[pass_params]" <?php echo esc_attr( $pass_params ? 'checked' : '' ); ?> />
		  <?php esc_html_e( 'העברת פרמטרים לכתובת עמוד התודה', 'responder' ); ?>
		</label>
	  </p>
	</div>

	<hr>

	<h2>
	  <?php esc_html_e( 'שדות ראשיים - התאמת שדות הטופס לשדות ברב מסר', 'responder' ); ?>
	</h2>

	<p class="mail-field">
	  <label for="responder-name">
		<?php esc_html_e( 'שדה שם מלא (במעבר לרב מסר המערכת מפצלת את השם באופן אוטומטי לשם פרטי ושם משפחה)', 'responder' ); ?>
	  </label>
	  <input type="text" id="responder-name" name="responder[name]" placeholder="<?php esc_html_e( 'לדוגמה:', 'responder' ); ?> [your-name]" value="<?php echo esc_attr( $name ); ?>" />
	</p>

	<p class="mail-field hidden">
	  <label for="responder-first">
		<?php esc_html_e( 'שדה שם פרטי', 'responder' ); ?>
	  </label>
	  <input type="text" id="responder-first" name="responder[first]" placeholder="<?php esc_html_e( 'לדוגמה:', 'responder' ); ?> [your-first]" value="<?php echo esc_attr( $first ); ?>" />
	</p>

	<p class="mail-field hidden">
	  <label for="responder-last">
		<?php esc_html_e( 'שדה שם משפחה', 'responder' ); ?>
	  </label>
	  <input type="text" id="responder-last" name="responder[last]" placeholder="<?php esc_html_e( 'לדוגמה:', 'responder' ); ?> [your-last]" value="<?php echo esc_attr( $last ); ?>" />
	</p>

	<p class="mail-field">
	  <label for="responder-email">
		<?php esc_html_e( 'שדה כתובת מייל', 'responder' ); ?>
	  </label>
	  <input type="text" id="responder-email" name="responder[email]" placeholder="<?php esc_html_e( 'לדוגמה:', 'responder' ); ?> [your-email]" value="<?php echo esc_attr( $email ); ?>" />
	</p>

	<p class="mail-field">
	  <label for="responder-phone">
		<?php esc_html_e( 'שדה טלפון', 'responder' ); ?>
	  </label>
	  <input type="text" id="responder-phone" name="responder[phone]" placeholder="<?php esc_html_e( 'לדוגמה:', 'responder' ); ?> [your-phone]" value="<?php echo esc_attr( $phone ); ?>" />
	</p>

	<div id="responder-custom-fields-wrapper">
	  <h2>
		<?php esc_html_e( 'שדות מותאמים - התאמת שדות הטופס לשדות ברב מסר', 'responder' ); ?>
	  </h2>

	  <div id="responder-custom-fields" data-value="<?php echo esc_attr( $custom_fields ); ?>">
	  </div>

	  <script type="text/template" id="tmpl-custom-field-template">
		<div class="custom-field">
		  <div class="field-map">
			<p>
			  <label for="responder-custom-value-{{ data.fieldIndex }}" data-count="{{ data.fieldIndex }}">
				<?php esc_html_e( 'שם השדה כפי שמופיע בטופס:', 'responder' ); ?>
			  </label>

			  <input type="text" name="responder[CustomValue{{data.fieldIndex}}]" id="responder-custom-value-{{data.fieldIndex}}" placeholder="<?php esc_html_e( 'לדוגמה:', 'responder' ); ?> [field-123]" value="{{data.customField.value}}" />
			</p>

			<p>
			  <label for="responder-custom-key-{{ data.fieldIndex }}">
				<?php esc_html_e( 'התאמה לשדה המותאם ברב מסר:', 'responder' ); ?>
			  </label>

			  <select id="responder-custom-key-{{data.fieldIndex}}" name="responder[CustomKey{{data.fieldIndex}}]">
				<option disabled {{ data.selectedPersonalField.id ? '' : 'selected'}}>---</option>
				<# _.each(data.personalFields, function(personalField) { #>
				  <# var selectedOption = personalField.id === data.selectedPersonalField.id ? 'selected' : ''; #>

				  <option value="{{ personalField.id }}" {{selectedOption}}>
					{{personalField.name}}
				  </option>
				<# }); #>
			  </select>
			</p>
		  </div>

		  <# if (data.selectedPersonalField.options && data.selectedPersonalField.options.length) { #>
			<div class="field-options-map">
			  <span class="description">
				<?php esc_html_e( 'התאמת אפשרויות הבחירה של', 'responder' ); ?> <span style="text-decoration: underline">{{data.selectedPersonalField.name}}</span>
			  </span>

			  <# _.each(data.selectedPersonalField.options, function(selectedPersonalFieldOption, optionIndex) { #>
				<# var customFieldOption = data.customField.options ? data.customField.options[optionIndex] || {} : {} #>

				<div class="field-option-map">
				  <p>
					<input type="text" name="responder[CustomValue{{ data.fieldIndex }}_{{ optionIndex + 1 }}]" placeholder="<?php esc_html_e( 'לדוגמה:', 'responder' ); ?> [field-option-123]" value="{{ customFieldOption.value }}" />
				  </p>

				  <p>
					<select name="responder[CustomKey{{ data.fieldIndex }}_{{ optionIndex + 1 }}]">
					  <option disabled {{ customFieldOption.key ? '' : 'selected' }}>---</option>
					  <# _.each(data.selectedPersonalField.options, function(personalFieldOption) { #>
						<# var selectedOption = personalFieldOption.id === customFieldOption.key ? 'selected' : ''; #>
						<option value="{{ personalFieldOption.id }}" {{ selectedOption }}>
						  {{ personalFieldOption.name }}
						</option>
					  <# }); #>
					</select>
				  </p>
				</div>
			  <# }) #>
			</div>
		  <# } #>

		</div>
	  </script>

	  <a
		id="add-custom-field"
		class="button action"
		data-loading-text="<?php esc_html_e( 'טוען שדות מחיבור רב מסר...', 'responder' ); ?>"
		data-button-text="<?php esc_html_e( 'הוספת שדה לחיבור רב מסר', 'responder' ); ?>"
		disabled
	  >
		<?php esc_html_e( 'טוען שדות מחיבור רב מסר...', 'responder' ); ?>
	  </a>
	</div>

  </div>
</div>
