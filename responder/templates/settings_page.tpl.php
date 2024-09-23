<div>
  <h1 class="responder-settings-title">
	<?php echo esc_html( RavMesser\Plugin\Settings::config( 'page_title' ) ); ?>
  </h1>
</div>

<div class="plugin_config">
  <div id="plugin_config_tabs">

	<ul>

	  <li>
		<a href="#plugin_config-5">
		  <?php esc_html_e( 'לפני שמתחילים', 'responder' ); ?>
		</a>
	  </li>

	  <li>
		<a href="#plugin_config-2">
		  <?php esc_html_e( 'חיבור לרב מסר', 'responder' ); ?>
		</a>
	  </li>

	  <?php if ( RavMesser\Plugin\API::run( 'responder' )->isValid() || RavMesser\Plugin\API::run( 'responder_live' )->isValid() ) : ?>

			<?php if ( RavMesser\Plugin\SettingsPage::isContactForm7Active() ) : ?>
		  <li>
			<a href="#plugin_config-cf7">
				<?php esc_html_e( 'טפסים ל-Contact Form 7', 'responder' ); ?>
			</a>
		  </li>
		<?php endif ?>

			<?php if ( RavMesser\Plugin\SettingsPage::isPojoFormsActive() && RavMesser\Plugin\API::run( 'responder' )->isValid() ) : ?>
		  <li>
			<a href="#plugin_config-pojo">
				<?php esc_html_e( 'טפסים ל-Pojo Form', 'responder' ); ?>
			</a>
		  </li>
		<?php endif ?>

			<?php if ( RavMesser\Plugin\SettingsPage::isElementorActive() ) : ?>
		  <li>
			<a href="#plugin_config-elementor">
				<?php esc_html_e( 'טפסים לאלמנטור', 'responder' ); ?>
			</a>
		  </li>
		<?php endif ?>

		<li>
		  <a href="#plugin_config-3">
			<?php esc_html_e( 'נמענים', 'responder' ); ?>
		  </a>
		</li>

	  <?php endif ?>

	  <li class="<?php echo esc_attr( RavMesser\Plugin\SettingsPage::isDebuggerActive() ? '' : 'hidden' ); ?>">
		<a href="#plugin_config-advanced">
		  <?php esc_html_e( 'הגדרות מתקדמות', 'responder' ); ?>
		</a>
	  </li>

	</ul>

	<?php if ( RavMesser\Plugin\API::run( 'responder' )->isValid() || RavMesser\Plugin\API::run( 'responder_live' )->isValid() ) : ?>

	  <div id="plugin_config-3">
		<?php include_once RAV_MESSER_TEMPLATES_DIR . '/tabs/lists.tpl.php'; ?>
	  </div>

		<?php if ( RavMesser\Plugin\SettingsPage::isContactForm7Active() ) : ?>
		<div id="plugin_config-cf7">
			<?php include_once RAV_MESSER_TEMPLATES_DIR . '/integrations/contact_form_7/tab.tpl.php'; ?>
		</div>
	  <?php endif ?>

		<?php if ( RavMesser\Plugin\SettingsPage::isPojoFormsActive() && RavMesser\Plugin\API::run( 'responder' )->isValid() ) : ?>
		<div id="plugin_config-pojo">
			<?php include_once RAV_MESSER_TEMPLATES_DIR . '/integrations/pojo_forms/tab.tpl.php'; ?>
		</div>
	  <?php endif ?>

		<?php if ( RavMesser\Plugin\SettingsPage::isElementorActive() ) : ?>
		<div id="plugin_config-elementor">
			<?php include_once RAV_MESSER_TEMPLATES_DIR . '/integrations/elementor/tab/index.tpl.php'; ?>
		</div>
	  <?php endif ?>

	<?php endif ?>

	<div id="plugin_config-5">
	  <?php require_once RAV_MESSER_TEMPLATES_DIR . '/tabs/getting_started.tpl.php'; ?>
	</div>

	<div id="plugin_config-2">
	  <?php require_once RAV_MESSER_TEMPLATES_DIR . '/tabs/connection.tpl.php'; ?>
	</div>

	<div id="plugin_config-advanced" class="<?php echo esc_attr( RavMesser\Plugin\SettingsPage::isDebuggerActive() ? '' : 'hidden' ); ?>">
	  <?php require_once RAV_MESSER_TEMPLATES_DIR . '/tabs/debugger.tpl.php'; ?>
	</div>

	<?php require_once RAV_MESSER_TEMPLATES_DIR . '/footer.tpl.php'; ?>

  </div>
</div>
