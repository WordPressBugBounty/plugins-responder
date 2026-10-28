(function ($) {
  $(function () {
    var $tabs          = $('#plugin_config_tabs'),
        activeTabIndex = 0,
        activeTabId    = window.location.hash;

    if (activeTabId.length) {
      activeTabIndex = $tabs.find('a[href="' + activeTabId + '"]').parent().index();

      if (activeTabIndex === -1) {
        activeTabIndex = 0;
      }
    }

    $tabs
      .tabs({ active: activeTabIndex })
      .fadeIn(500);

    // Credential fields ("חיבור לרב מסר"): masked by default, eye = show / hide, copy = clipboard.
    $(document).on('click', '.rmp-secret-toggle', function () {
      var $button = $(this),
          $input  = $('#' + $button.data('target')),
          show    = $input.attr('type') === 'password';

      $input.attr('type', show ? 'text' : 'password');
      $button.attr('aria-pressed', show ? 'true' : 'false');
      $button.find('i').toggleClass('fa-eye', !show).toggleClass('fa-eye-slash', show);
    });

    $(document).on('click', '.rmp-secret-copy', function () {
      var $button = $(this),
          $input  = $('#' + $button.data('target')),
          $status = $button.siblings('.rmp-secret-copied'),
          value   = $input.val();

      function done(ok) {
        $status.text(ok ? 'הועתק' : 'ההעתקה נכשלה');
        setTimeout(function () { $status.text(''); }, 2000);
      }

      if (!value) {
        return;
      }

      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(value).then(function () { done(true); }, function () { done(false); });
        return;
      }

      // Fallback for non-HTTPS admin pages.
      var $tmp = $('<textarea readonly></textarea>').val(value).css({ position: 'fixed', top: '-1000px' }).appendTo('body');
      $tmp[0].select();
      var ok = false;
      try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
      $tmp.remove();
      done(ok);
    });
  });
})(jQuery);
