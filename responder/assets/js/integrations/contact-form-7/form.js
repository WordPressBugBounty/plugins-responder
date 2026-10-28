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
        fields          = toFieldList(event.detail.inputs);

    handleSuccessfulForm(urlRedirect, openUrlInNewTab, passParam, fields);
  }

  function onPostMailSent(formData) {
    var urlRedirect     = formData.responder_url_redirect,
        openUrlInNewTab = formData.responder_url_open_new_tab,
        passParam       = formData.responder_pass_params,
        fields          = toFieldList(formData.inputs);

    handleSuccessfulForm(urlRedirect, openUrlInNewTab, passParam, fields);
  }

  function handleSuccessfulForm(urlRedirect, openUrlInNewTab, passParam, fields) {
    if (!urlRedirect || !urlRedirect.length) {
      return;
    }

    if (passParam === 'on') {
      urlRedirect = buildThankYouUrl(urlRedirect, fields);
    }

    if (openUrlInNewTab === 'on') {
      window.open(urlRedirect);
    } else {
      location.href = urlRedirect;
    }
  }
  // [{name, value}] from CF7's AJAX inputs (array) or the no-AJAX posted data (object) (PR1-I9).
  function toFieldList(inputs) {
    var fields = [];

    if ($.isArray(inputs)) {
      return inputs;
    }

    $.each(inputs || {}, function (name, value) {
      if ($.isArray(value)) {
        $.each(value, function (index, item) {
          fields.push({ name: name, value: item });
        });
      } else {
        fields.push({ name: name, value: value });
      }
    });

    return fields;
  }

  // Adds the form fields to the thank-you URL, each value encoded exactly once, with the right
  // "?" / "&" separator. Fields without a name or value and CF7's internal fields are skipped (PR1-I9).
  // PR1-I26: WordPress reserves some query vars (e.g. "name" = post slug), so a page on this
  // site opened with ?name=... is a 404. On the same site the alias is used; on another site
  // the original parameter is kept, so existing external thank-you pages keep working.
  var RESERVED_URI_PARAM_ALIASES = { name: 'full_name' };

  function isSameSite(url) {
    var strip = function (host) { return String(host || '').toLowerCase().replace(/^www\./, ''); };

    return strip(url.hostname) === strip(window.location.hostname);
  }

  function safeUriParam(name, sameSite) {
    var match = /^(.*?)(\[\])?$/.exec(String(name)),
        base  = match[1],
        alias = sameSite && Object.prototype.hasOwnProperty.call(RESERVED_URI_PARAM_ALIASES, base)
          ? RESERVED_URI_PARAM_ALIASES[base] : null;

    return alias ? alias + (match[2] || '') : name;
  }

  function buildThankYouUrl(baseUrl, fields) {
    var url, sameSite;

    try {
      url = new URL(baseUrl, window.location.href);
    } catch (error) {
      return baseUrl;
    }

    sameSite = isSameSite(url);

    $.each(fields || [], function (index, field) {
      var name  = field && field.name,
          value = field && field.value;

      if (!name || /^_wpcf7/.test(name) || value === undefined || value === null || String(value).trim() === '') {
        return;
      }

      url.searchParams.append(safeUriParam(name, sameSite), value);
    });

    return url.toString();
  }
}(jQuery));
