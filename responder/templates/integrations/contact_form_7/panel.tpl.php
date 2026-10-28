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

// Saved forms always have chosen_system (getFormSettings fills it). Only a form with no
// רב מסר settings yet gets the pre-selected current system (PR1-I5).
$chosen_system       = PluginHelpers::getVal( $settings, 'chosen_system', empty( $settings ) ? PluginAPI::getPreferredSystemName() : $rmp_systems_names[0] );
$list_id             = PluginHelpers::getVal( $settings, 'list' );
$url_redirect        = PluginHelpers::getVal( $settings, 'url_redirect' );
$url_open_new_tab    = PluginHelpers::getVal( $settings, 'url_open_new_tab' );
$pass_params         = PluginHelpers::getVal( $settings, 'pass_params' );
$on_existing         = PluginHelpers::getVal( $settings, 'onexisting' );
$onexisting_rejoin   = PluginHelpers::getVal( $settings, 'onexisting_rejoin' );
$onexisting_joindate = PluginHelpers::getVal( $settings, 'onexisting_joindate' );
$onexisting_sms      = PluginHelpers::getVal( $settings, 'onexisting_sms' );
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
		  <option value="responder_live" <?php echo esc_attr( $chosen_system === 'responder_live' ? 'selected' : '' ); ?>>
			<?php esc_html_e( 'רב מסר', 'responder' ); ?>
		  </option>
		  <option value="responder" <?php echo esc_attr( $chosen_system === 'responder' ? 'selected' : '' ); ?>>
			<?php esc_html_e( 'רב מסר הישנה', 'responder' ); ?>
		  </option>
		</select>

		<?php // PR1-I20: the old system has no tags / existing-contact options - say so before they are dropped. ?>
		<p class="description rmp-cf7-dropped-note" hidden>
		  <?php esc_html_e( 'ברב מסר הישנה אין תגיות ואין את אפשרויות "אם הנמען קיים" של רב מסר. אם תשמרו את הטופס כך, התגיות וההגדרות האלה יימחקו. חזרה לרב מסר לפני השמירה תחזיר אותן.', 'responder' ); ?>
		</p>
	  </div>
	<?php else : ?>
	  <input type="hidden" id="responder-chosen-system" name="responder[chosen_system]" value="<?php echo esc_attr( $chosen_system ); ?>" />
	<?php endif ?>

	<div class="mail-field">
	  <label for="responder-subscribers-list" class="mail-field-title">
		<?php esc_html_e( 'הרשימה ברב מסר אליה יוכנסו פרטי הנמענים', 'responder' ); ?>
	  </label>

	  <select name="responder[list]" id="responder-subscribers-list" data-value="<?php echo esc_attr( $list_id ); ?>" data-saved-system="<?php echo esc_attr( empty( $settings ) ? '' : $chosen_system ); ?>" data-placeholder="<?php esc_attr_e( 'בחירת רשימה', 'responder' ); ?>" data-missing-text="<?php esc_attr_e( 'הרשימה שנשמרה (%s) לא קיימת בחשבון', 'responder' ); ?>">
		<option value="<?php echo esc_attr( $list_id ); ?>" selected></option>
	  </select>

	  <?php // PR1-I20: messages for a saved list that is gone, and for saving without a list. ?>
	  <p class="rmp-cf7-list-missing notice notice-warning inline" hidden>
		<?php esc_html_e( 'הרשימה שנשמרה בטופס לא קיימת יותר בחשבון רב מסר, ולכן נרשמים מהטופס לא נכנסים אליה. בחרו רשימה אחרת ושמרו.', 'responder' ); ?>
	  </p>
	  <p class="rmp-cf7-list-required notice notice-error inline" hidden>
		<?php esc_html_e( 'לא נבחרה רשימה ברב מסר, ולכן הטופס לא נשמר. בחרו רשימה (או החזירו את "בחירת מערכת" למערכת השמורה) ושמרו שוב.', 'responder' ); ?>
	  </p>
	</div>

	<div class="mail-field hidden">
	  <label for="cf7_onexisting" class="mail-field-title">
		<?php esc_html_e( 'אם הנמען קיים ברשימה', 'responder' ); ?>
	  </label>

	  <select id="cf7_onexisting" name="responder[onexisting]">
		<?php // PR1-I22: "update" is the default - marked as selected also when nothing is saved yet, so CF7 does not see a change. ?>
		<option value="update" <?php echo esc_attr( $on_existing !== 'resubscribe' ? 'selected' : '' ); ?>>
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

	  <p>
		<label for="onexisting_sms">
		  <input id="onexisting_sms" type="checkbox" name="responder[onexisting_sms]" value="sms" <?php echo esc_attr( $onexisting_sms ? 'checked' : '' ); ?> />
		  <?php esc_html_e( 'אם הנמען ביקש הסרה מ-SMS, להחזיר אותו לקבלת SMS (רב מסר בלבד, רק כשמילא טלפון)', 'responder' ); ?>
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
		<br>
		<i class="form-info"><?php esc_html_e( 'כל השדות שמולאו בטופס, כולל מייל, טלפון ושדות מוסתרים, יתווספו לכתובת עמוד התודה.', 'responder' ); ?></i>
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
	  <?php // PR1-I20: the old system has one name field - first + last are sent as the name when "שם מלא" is empty. ?>
	  <i class="form-info rmp-cf7-old-system-name" hidden><?php esc_html_e( 'ברב מסר הישנה יש שדה שם אחד: אם "שדה שם מלא" ריק או לא מולא, נשלחים שם פרטי ושם משפחה יחד כשם.', 'responder' ); ?></i>
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
	  <h2><?php esc_html_e( 'שדות מותאמים - התאמת שדות הטופס לשדות ברב מסר', 'responder' ); ?></h2>

	  <div
	  	id="responder-custom-fields"
	  	data-value="<?php echo esc_attr( $custom_fields ); ?>">
	  </div>

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
