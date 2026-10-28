<?php
/**
 * Plugin settings page footer – same content and layout as the רב מסר app footer (PR1-I29):
 * logo, phone, email, WhatsApp support and support hours on one line.
 *
 * @package Responder
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="plugin_footer rmp-footer" dir="rtl">
	<span class="rmp-footer-logo" aria-label="<?php esc_attr_e( 'רב מסר', 'responder' ); ?>">
		<span class="rmp-footer-logo-rav"><?php esc_html_e( 'רב', 'responder' ); ?></span><span class="rmp-footer-logo-meser"><?php esc_html_e( 'מסר', 'responder' ); ?></span>
	</span>

	<span class="rmp-footer-item">
		<i class="fa fa-phone fa-flip-horizontal" aria-hidden="true"></i>
		<a href="tel:037177777"><bdi dir="ltr"><?php esc_html_e( '03-717-7777', 'responder' ); ?></bdi></a>
	</span>

	<span class="rmp-footer-item">
		<i class="fa fa-envelope" aria-hidden="true"></i>
		<a href="mailto:support@responder.co.il"><?php esc_html_e( 'support@responder.co.il', 'responder' ); ?></a>
	</span>

	<span class="rmp-footer-item">
		<i class="fa fa-whatsapp" aria-hidden="true"></i>
		<a href="https://wa.me/972037177777" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'תמיכה טכנית בוואטסאפ', 'responder' ); ?></a>
	</span>

	<span class="rmp-footer-item rmp-footer-hours">
		<i class="fa fa-clock-o" aria-hidden="true"></i>
		<span>
			<?php esc_html_e( 'תמיכה טכנית: ימים א׳-ה׳', 'responder' ); ?>
			<bdi dir="ltr"><?php esc_html_e( '09:00 - 18:00', 'responder' ); ?></bdi>
			|
			<?php esc_html_e( 'ו׳ וערבי חג', 'responder' ); ?>
			<bdi dir="ltr"><?php esc_html_e( '08:00 - 12:00', 'responder' ); ?></bdi>
		</span>
	</span>
</div>
