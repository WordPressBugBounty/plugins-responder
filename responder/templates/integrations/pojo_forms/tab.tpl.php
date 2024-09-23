<?php $lists = RavMesser\Plugin\API::run( 'responder' )->getLists(); ?>

<h1>
  <?php esc_html_e( 'טפסים ל-Pojo Form', 'responder' ); ?>
</h1>

<form id="pojo-responder-form" class="responder-form-wrapper">
  <div class="responder-form-row">
	<div class="responder-form-field">
	  <label for="pojo-form-name" class="label">
		<?php esc_html_e( 'שם הטופס (השם מוסתר למבקרים באתר)', 'responder' ); ?>
	  </label>
	  <input type="text"
		id="pojo-form-name"
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

  <input type="hidden" name="chosen_system" value="responder" />

  <div class="responder-form-row">
	<div class="responder-form-field">
	  <label for="pojo-subscribers-lists" class="label">
		<?php esc_html_e( 'הרשימה ברב מסר אליה יוכנסו פרטי הנמענים', 'responder' ); ?>
	  </label>

	  <select id="pojo-subscribers-lists" name="list_id">
		<option disabled selected value="">
		  <?php esc_html_e( 'בחירת רשימה', 'responder' ); ?>
		</option>

		<?php foreach ( $lists as $list ) : ?>
		  <option value="<?php echo esc_attr( $list['id'] ); ?>">
			<?php echo esc_html( $list['name'] ); ?>
		  </option>
		<?php endforeach ?>
	  </select>
	</div>
  </div>

  <div class="responder-form-row">
	<div class="responder-form-field">
	  <label for="pojo_onexisting" class="label"><?php esc_html_e( 'אם הנמען קיים ברשימה', 'responder' ); ?></label>
	  <select id="pojo_onexisting" name="action_on_existing" class="res-select-generator-page">
		<option value="update" selected><?php esc_html_e( 'שמור ותק ועדכן פרטים', 'responder' ); ?></option>
		<option value="resubscribe"><?php esc_html_e( 'איפוס ותק והרשמה מחדש', 'responder' ); ?></option>
	  </select>
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
		  <th valign="top" colspan="2"></th>
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
		</tr>
	  </tfoot>
	</table>

	<p style="color: red">
	  <?php esc_html_e( 'שימו לב: טפסי pojo אינם תומכים בשדה מסוג תאריך', 'responder' ); ?>
	</p>
  </div>

  <div class="responder-form-row">
	<h2><?php esc_html_e( 'כתובת עמוד תודה (רשות)', 'responder' ); ?></h2>
	<div class="responder-form-field mbottom_10">
	  <input id="f_ty_pojo" class="ltr" type="text" name="thank_you_url" placeholder="https://example.co.il/thankyou" style="padding-left: 8px" />
	</div>

	<i class="form-info">
	  <?php esc_html_e( 'אם תשאירו את השדה ריק, הנמענים שלכם יישארו באותו דף לאחר שליחת טופס ההרשמה.', 'responder' ); ?>
	</i>
  </div>

  <div class="responder-form-row">
	<h2><?php esc_html_e( 'טקסט כפתור', 'responder' ); ?></h2>
	<div class="responder-form-field">
	  <input type="text" name="submit_text" value="<?php esc_html_e( 'שליחה', 'responder' ); ?>"
		placeholder="<?php esc_html_e( 'שליחה', 'responder' ); ?>" />
	</div>
  </div>

  <div class="responder-form-row">
	<div class="responder-form-field">
	  <button
		id="pojo-create-form"
		class="button-primary"
		data-save_text=" <?php esc_html_e( 'שומר...', 'responder' ); ?>"
		data-success_text="<?php esc_html_e( 'הטופס נוצר בהצלחה', 'responder' ); ?>"
		data-text="<?php esc_html_e( 'שמירה ומעבר ל-Pojo Forms', 'responder' ); ?>"
		disabled
	  >
		<?php esc_html_e( 'שמירה ומעבר ל-Pojo Forms', 'responder' ); ?>
	  </button>
	</div>
  </div>
</form>
