<?php
  $advanced_tab_link             = RavMesser\Plugin\SettingsPage::getUrl( '&showadvanced=true#plugin_config-advanced' );
  $rav_messer_blog_link          = 'https://www.responder.co.il/blog/';
  $responder_live_enable_url     = RavMesser\Plugin\SettingsPage::getUrl( '#plugin_config-2' );
  $responder_live_is_not_enabled = ! RavMesser\Plugin\API::isResponderLiveEnabled();
?>

<h1>לפני שמתחילים</h1>

<div class="responder-instructions-text">

  <p><span>תוסף רב מסר לוורדפרס מאפשר לכם חיבור פשוט וקל בין טופסי האתר לרשימות ברב מסר. הוא עובד עם כמה תוספים פופולריים:</span></p>

  <ul>
	<li><span>Contact Form 7</span></li>
	<li><span>Pojo Forms</span></li>
	<li><span>אלמנטור – הגרסה החינמית</span></li>
	<li><span>אלמנטור פרו</span></li>
  </ul>

  <h2><b>איך זה עובד?</b></h2>

  <p><span>תוסף רב מסר מעביר את הנמענים בטפסים באתר שלכם לרשימות לבחירתכם ברב מסר.</span></p>

  <h2><b>לפני הכול:</b></h2>

  <ol>
	<li><span>ודאו שהתוספים מותקנים אצלכם באתר. לדוגמה, אם תרצו לחבר בין אלמנטור לרב מסר, ודאו קודם כול שכבר התקנתם את אלמנטור באתר שלכם.</span></li>
	<li><span>בקשו מצוות התמיכה של רב מסר אסימונים (טוקנים) שיאפשרו את החיבור המאובטח בין אתר הוורדפרס לבין מערכת רב מסר. אתם מוזמנים לפנות במייל או בטלפון – הפרטים ושעות הפעילות מופיעים כאן למטה.</span></li>
  </ol>

  <p><span>לאחר שתקבלו את פרטי האסימונים (קוד מפתח וקוד סוד) הכניסו אותם ב</span><b>לשונית חיבור לרב מסר </b><span>בתוסף.</span></p>
  <p><span>חשבון הרב מסר שלכם מחובר? מיד ייפתחו גם הלשוניות Pojo Forms, Contact form 7, טפסים לאלמנטור ונמענים.</span></p>
  <p><span>ב</span><b>לשונית Contact Form 7 ו-Pojo Forms </b><span>תוכלו ליצור טפסים לתוספים ולבחור לאיזו רשימה ברב מסר לקשר אותם. אחר כך תגדירו את פרטי הטופס ולבסוף תועברו לממשק תוסף הטפסים שבחרתם, שם תשלימו את ההגדרות. מכאן והלאה כל שינוי בטופס קיים יתבצע מתוסף הטפסים עצמו, ולא מתוסף רב מסר.</span></p>
  <p><span>ב</span><b>לשונית טפסים לאלמנטור</b><span> תוכלו ליצור טפסים לשימוש בתוסף אלמנטור- הגרסה החינמית. לאחר יצירת הטופס פה תוכלו למצוא את הטופס בתפריט הראשי של אלמנטור למטה באיזור שנקרא- רב מסר.</span></p>
  <p><span>ב</span><b>לשונית נמענים</b><span> תוכלו לצפות בכל פרטי הנמענים שלכם ברשימות רב מסר.</span></p>
  <p><span>החיבור</span><b> לאלמנטור פרו</b><span> מתבצע בתוך הגדרות הטופס של אלמנטור, הוסיפו את Responder להגדרות של Actions After Submit בטופס הרלוונטי.</span></p>
  <p><span>מידע מפורט על אופן השימוש בתוסף רב מסר לוורדפרס תוכלו למצוא </span><a href="<?php echo esc_url( $rav_messer_blog_link ); ?>" target="_blank"><span>בבלוג של רב מסר</span></a></p>
  <p><span>צוות התמיכה של רב מסר ישמח לסייע לכם בכל שאלה, ומאחל לכם הצלחה בשימוש בתוסף של רב מסר!</span></p>
  <p><span>למעבר ללשונית הגדרות מתקדמות </span><a href="<?php echo esc_url( $advanced_tab_link ); ?>">לחצו כאן</a></p>

  <?php if ( $responder_live_is_not_enabled ) : ?>
	<form id="rmp-responder-live-enable-form" method="post" action="<?php echo esc_url( $responder_live_enable_url ); ?>">
		<?php settings_fields( RAV_MESSER_OPTIONS_GROUP ); ?>
	  <input type="hidden" name="rmp_action" value="connection_settings" />
	  <input type="hidden" name="responder[responder_live_enabled]" value="true" />

	  <p>
		<?php esc_html_e( 'חיבור התוסף למערכת החדשה בלחיצה', 'responder' ); ?><button type="submit">
		  <?php esc_html_e( 'כאן', 'responder' ); ?>
		</button>
	  </p>
	</form>
  <?php endif ?>
</div>
