(function ($) {
  $(function () {
    var $tabs           = $('#plugin_config_tabs'),
        debuggerTagHash = '#plugin_config-advanced',
        activeTabIndex  = 0,
        activeTabId     = window.location.hash;

    if (activeTabId.length) {
      activeTabIndex = $tabs.find('a[href="' + activeTabId + '"]').parent().index();

      if (activeTabIndex === -1) {
        activeTabIndex = 0;
      }
    }

    if (activeTabId === debuggerTagHash) {
      $('a[href="' + debuggerTagHash + '"]').parent().removeClass('hidden');
      $(debuggerTagHash).removeClass('hidden');
    }

    $tabs
      .tabs({ active: activeTabIndex })
      .fadeIn(500);
  });
})(jQuery);
