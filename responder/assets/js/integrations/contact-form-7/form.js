(function ($) {
  if (typeof rmpCF7MailSentData !== 'undefined') {
    onPostMailSent(rmpCF7MailSentData);
  } else {
    $(function () {
      $('.wpcf7').on("wpcf7mailsent", onAjaxMailSent);
    });
  }

  function onAjaxMailSent(event) {
    var urlRedirect     = event.detail.apiResponse.responder_url_redirect,
        openUrlInNewTab = event.detail.apiResponse.responder_url_open_new_tab,
        passParam       = event.detail.apiResponse.responder_pass_params,
        searchQuery     = $.param(event.detail.inputs);

    handleSuccessfulForm(urlRedirect, openUrlInNewTab, passParam, searchQuery);
  }

  function onPostMailSent(formData) {
    var urlRedirect     = formData.responder_url_redirect,
        openUrlInNewTab = formData.responder_url_open_new_tab,
        passParam       = formData.responder_pass_params,
        searchQuery     = $.param(formData.inputs);

    handleSuccessfulForm(urlRedirect, openUrlInNewTab, passParam, searchQuery);
  }

  function handleSuccessfulForm(urlRedirect, openUrlInNewTab, passParam, searchQuery) {
    var querySeparator  = '?';

    if (!urlRedirect.length) {
      return;
    }

    if (passParam === 'on') {
      if (urlRedirect.indexOf(querySeparator) !== -1) {
        querySeparator = '&';
      }

      urlRedirect = encodeURI(urlRedirect + querySeparator + searchQuery);
    }

    if (openUrlInNewTab === 'on') {
      window.open(urlRedirect);
    } else {
      location.href = urlRedirect;
    }
  }
}(jQuery));
