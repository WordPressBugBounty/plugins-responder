<?php

use RavMesser\Plugin\API as PluginAPI;

$rmp_systems_names = PluginAPI::getConnectedSystemsNames();
$rmp_systems_count = count( $rmp_systems_names );
$rmp_chosen_system = $rmp_systems_names[0];

?>

<h1>
  <?php esc_html_e( 'טפסים ל-Contact Form 7', 'responder' ); ?>
</h1>
<p>
	<a href="https://www.youtube.com/watch?v=ptTF3qpQtrk" target="_blank"><?php esc_html_e( 'צפייה בסרטון הדרכה', 'responder' ); ?></a>
</p>
<form id="cf7-responder-form" class="responder-form-wrapper">
  <div class="responder-form-row">
	<div class="responder-form-field">
	  <label for="cf7-form-name" class="label">
		<?php esc_html_e( 'שם הטופס (השם מוסתר למבקרים באתר)', 'responder' ); ?>
	  </label>
	  <input type="text"
		id="cf7-form-name"
		name="form_name"
		value="
		<?php
		printf(
		  /* translators: %s date of now */
			esc_html__( 'טופס רב מסר %s', 'responder' ),
			date( 'Y-m-d h:i:s' )
		);
		?>
"
	  />
	</div>
  </div>

  <?php if ( $rmp_systems_count > 1 ) : ?>
	<div class="responder-form-row">
	  <div class="responder-form-field">
		<label for="chosen-system" class="label">
		  <?php esc_html_e( 'בחירת מערכת', 'responder' ); ?>
		</label>

		<select id="chosen-system" name="chosen_system">
		  <option value="responder" selected="selected">
			<?php esc_html_e( 'רב מסר', 'responder' ); ?>
		  </option>
		  <option value="responder_live">
			<?php esc_html_e( 'רב מסר - מערכת חדשה', 'responder' ); ?>
		  </option>
		</select>
	  </div>
	</div>
  <?php else : ?>
	<input type="hidden" id="chosen-system" name="chosen_system" value="<?php echo esc_attr( $rmp_chosen_system ); ?>" />
  <?php endif ?>

  <div class="responder-form-row">
	<div class="responder-form-field">
	  <label for="cf7-subscribers-lists" class="label"><?php esc_html_e( 'הרשימה ברב מסר אליה יוכנסו פרטי הנמענים', 'responder' ); ?></label>
	  <select id="cf7-subscribers-lists" name="list">
		<option disabled selected value=""><?php esc_html_e( 'בחירת רשימה', 'responder' ); ?></option>

		<?php $lists = RavMesser\Plugin\API::run( $rmp_chosen_system )->getLists(); ?>
		<?php foreach ( $lists as $list ) : ?>
		  <option value="<?php echo esc_attr( $list['id'] ); ?>">
			<?php echo esc_attr( $list['name'] ); ?>
		  </option>
		<?php endforeach ?>
	  </select>
	</div>
  </div>

  <div class="responder-form-row">
	<div class="responder-form-field">
	  <label for="cf7_onexisting" class="label"><?php esc_html_e( 'אם הנמען קיים ברשימה', 'responder' ); ?></label>
	  <select id="cf7_onexisting" name="action_on_existing" class="res-select-generator-page">
		<option value="update" selected><?php esc_html_e( 'שמור ותק ועדכן פרטים', 'responder' ); ?></option>
		<option value="resubscribe"><?php esc_html_e( 'איפוס ותק והרשמה מחדש', 'responder' ); ?></option>
	  </select>
	</div>
  </div>

  <div class="responder-form-row">
	<div class="responder-form-field mbottom_10">
	  <label class="label">
		<?php esc_html_e( 'אם הנמען קיים ברשימה', 'responder' ); ?>
	  </label>
	</div>
	<div class="responder-form-field one-liner">
	  <input id="onexisting_rejoin" type="checkbox" name="onexisting_rejoin" value="rejoin" />
	  <label for="onexisting_rejoin">
		<?php esc_html_e( 'אם הנמען קיים, להחליף את הפרטים הקיימים שלו בפרטים החדשים', 'responder' ); ?>
	  </label>
	</div>
	<div class="responder-form-field one-liner">
	  <input id="onexisting_joindate" type="checkbox" name="onexisting_joindate" value="joindate" />
	  <label for="onexisting_joindate"><?php esc_html_e( 'אם הנמען קיים, לאפס את הותק שלו ברשימה', 'responder' ); ?></label>
	</div>
  </div>

  <div class="responder-form-row">
	<div class="responder-form-field mbottom_10">
	  <label for="tags" class="label"><?php esc_html_e( 'הוספת תגיות לנמען', 'responder' ); ?></label>
	</div>
	<div class="responder-form-field">
	  <select id="tags" name="tags" multiple="multiple" disabled></select>
	</div>
  </div>

  <div class="responder-form-row">
	<h2>
	  <?php esc_html_e( 'בחירת שדות לטופס (השדות מגיעים מהרשימה הנבחרת ברב מסר)', 'responder' ); ?>
	</h2>
	<p class="fields-table-warning">
	  <?php esc_html_e( 'כתובת מייל או טלפון חייבים להופיע כשדות חובה בטופס', 'responder' ); ?>
	</p>

	<table class="fields-table">
	  <thead>
		<tr>
		  <th valign="top" align="right"><?php esc_html_e( 'שם השדה', 'responder' ); ?></th>
		  <th valign="top"><?php esc_html_e( 'הצג', 'responder' ); ?><i class="fa fa-eye" aria-hidden="true"></i></th>
		  <th valign="top"><?php esc_html_e( 'חובה', 'responder' ); ?><i class="fa fa-asterisk" aria-hidden="true"></i></th>
		  <th valign="top" colspan="2"><?php esc_html_e( 'שדה נסתר', 'responder' ); ?><i class="fa fa-eye-slash" aria-hidden="true"></i></th>
		</tr>
	  </thead>
	  <tbody>
		<tr>
		  <td colspan="5" align="center"><?php esc_html_e( 'יש לבחור רשימה כדי לראות שדות לבחירה', 'responder' ); ?></td>
		</tr>
	  </tbody>
	  <tfoot hidden>
		<tr class="custom-fields-title"><th colspan="5" align="right" ><h2 class="mbottom_0"><?php esc_html_e( 'שדות מותאמים', 'responder' ); ?></h2></th></tr>
		<tr class="empty-list-text"><td colspan="5" align="center"><?php esc_html_e( 'יש לבחור רשימה כדי לראות שדות לבחירה', 'responder' ); ?></td></tr>
		<tr class="loading-text"><td colspan="5" align="center" ><?php esc_html_e( 'טוען...', 'responder' ); ?></td></tr>
		<tr class="field-row">
		  <td>{{ fieldName }}</td>
		  <td class="responder-show-field" align="center">
			<input type="checkbox" name="show-field" value="{{ fieldId }}" />
		  </td>
		  <td class="responder-required-field" align="center">
			<input type="checkbox" name="required-field" value="{{ fieldId }}" />
		  </td>
		  <td class="responder-hidden-field" align="center">
			<input type="checkbox" name="hidden-field" value="{{ fieldId }}" />
		  </td>
		  <td class="responder-hidden-field-value" hidden>
			<select class="hidden-field-value-action" name="hidden-field-value-action-{{ fieldId }}">
			  <option value="" selected disabled><?php esc_html_e( 'בחרו אחת מהאפשרויות', 'responder' ); ?></option>
			  <option value="value"><?php esc_html_e( 'הגדרת ערך קבוע', 'responder' ); ?></option>
			  <option value="param"><?php esc_html_e( 'שאיבת ערך השדה מכתובת הקישור', 'responder' ); ?></option>
			</select>
			<input type="text"
			  class="hidden-field-value"
			  name="hidden-field-value-{{ fieldId }}"
			  placeholder="<?php esc_html_e( 'הזינו כאן את ערך השדה נסתר', 'responder' ); ?>"
			  hidden
			/>
			<div class="hidden-field-param" hidden>
			  <?php esc_html_e( 'לדוגמה: http://yourpage.com/pagename?{{ uriParam }}=value', 'responder' ); ?>
			</div>
		  </td>
		</tr>
	  </tfoot>
	</table>
  </div>

  <div class="responder-form-row">
	<h2><?php esc_html_e( 'כתובת עמוד תודה (רשות)', 'responder' ); ?></h2>
	<div class="responder-form-field mbottom_10">
	  <input id="f_ty" class="ltr" type="text" name="thank_you_url" placeholder="https://example.co.il/thankyou" style="padding-left: 8px" />
	</div>
	<div class="responder-form-field one-liner">
	  <input id="url_open_newtab" type="checkbox" name="thank_you_newtab" />
	  <label for="url_open_newtab"><?php esc_html_e( 'פתיחה בחלון חדש', 'responder' ); ?></label>
	</div>
	<div class="responder-form-field one-liner">
	  <input id="send_params_to_link" type="checkbox" name="thank_you_params" />
	  <label for="send_params_to_link"><?php esc_html_e( 'העברת פרמטרים לכתובת עמוד התודה', 'responder' ); ?></label>
	</div>

	<i class="form-info">
	  <?php esc_html_e( 'אם תשאירו את השדה ריק, הנמענים שלכם יישארו באותו דף לאחר שליחת טופס ההרשמה.', 'responder' ); ?>
	</i>
  </div>

  <div class="responder-form-row">
	<h2><?php esc_html_e( 'טקסט כפתור', 'responder' ); ?></h2>
	<div class="responder-form-field">
	  <input id="f_send" type="text" name="submit_text" value="<?php esc_html_e( 'שליחה', 'responder' ); ?>"
		placeholder="<?php esc_html_e( 'שליחה', 'responder' ); ?>" />
	</div>
  </div>

  <div class="responder-form-row">
	<div class="responder-form-field">
	  <button
		id="cf7-create-form"
		class="button-primary"
		data-save_text=" <?php esc_html_e( 'שומר...', 'responder' ); ?>"
		data-success_text="<?php esc_html_e( 'הטופס נוצר בהצלחה', 'responder' ); ?>"
		data-text="<?php esc_html_e( 'שמירה ומעבר ל-Contact Form 7', 'responder' ); ?>"
		disabled
	  >
		<?php esc_html_e( 'שמירה ומעבר ל-Contact Form 7', 'responder' ); ?>
	  </button>
	</div>
  </div>
</form>
