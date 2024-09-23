<h1><?php esc_html_e( 'טפסים לאלמנטור', 'responder' ); ?></h1>
<?php //phpcs:disable ?>
<p>
	<?php esc_html_e(
		'יצירת טפסים לגרסת אלמנטור החינמית וחיבורם לרשימות ברב מסר. לאחר יצירת הטופס תוכלו לגרור אותו לכל עמוד באתר מתוך הממשק של אלמנטור',
		'responder'
	); ?>

	<br>

	<a href="https://www.youtube.com/watch?v=sqIMNKLB9Cs" target="_blank">
		<?php esc_html_e( 'צפייה בסרטון הדרכה', 'responder' ); ?>
	</a>
</p>
<?php //phpcs:enable ?>
<h2>
	<?php esc_html_e( 'הטפסים שלי', 'responder' ); ?>
</h2>

<?php require_once RAV_MESSER_TEMPLATES_DIR . '/integrations/elementor/tab/forms.tpl.php'; ?>

<div class="elementor-settings-button-wrapper">

  <a id="res_button_save_elementor_settings" class="res-button-primary">
	<?php esc_html_e( 'שמירת הטפסים', 'responder' ); ?>
  </a>

  <div id="res_saving_text" class="loader_text_rtl" style="display:none">
	<?php esc_html_e( 'שומר...', 'responder' ); ?>
  </div>

  <div id="res_message_saved" class="unite-color-green" style="display:none">
	<?php esc_html_e( 'ההגדרות נשמרו בהצלחה', 'responder' ); ?>
  </div>

  <div id="res_message_error" class="unite-color-red" style="display:none">
	<?php esc_html_e( 'ההגדרות לא נשמרו', 'responder' ); ?>
  </div>

  <div id="res_message_error" class="unite_error_message" style="display:none">
	<?php esc_html_e( 'ההגדרות נשמרו בהצלחה', 'responder' ); ?>
  </div>

  <br>

  <div id="res_addnew_elementor_message" class="res-addnew-elementor-message" style="display:none">
	<?php esc_html_e( 'הטופס מוכן לשימוש ונמצא בתפריט האלמנטים באלמנטור', 'responder' ); ?>
  </div>

  <div class="res-text-comment">
	<p>
	  <?php esc_html_e( '* אם בעתיד תכניסו שינויים ברשימות שלכם ברב מסר או תוסיפו שדות מותאמים,', 'responder' ); ?>
	</p>
	<p style="padding-right:8px;">
	  <?php esc_html_e( 'תוכלו לעדכן את זה בקלות גם בתוסף:', 'responder' ); ?>
	</p>
	<p style="padding-right:8px;">
	  <?php esc_html_e( 'כל מה שצריך לעשות זה ללחוץ שוב על כפתור השמירה. הטפסים באלמנטור יתרעננו ויתעדכנו.', 'responder' ); ?>
	</p>
  </div>

</div>
