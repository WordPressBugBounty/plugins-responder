jQuery(window).on('elementor/frontend/init', function () {
  var $ = jQuery,
      formWidgetSelector = '.responder-form-main-wrapper form';

  if (elementorFrontend.isEditMode()) {
    return false;
  }

  $(document).on('submit', formWidgetSelector, onFormSubmit);

  function onFormSubmit(event) {
    var $currentForm = $(event.currentTarget);

    beforeSubmit($currentForm);

    if (validateForm($currentForm)) {
      sendForm($currentForm, true)
        .done(function (response) {
          onFormSent($currentForm)
        })
        .fail(function (xhr) {
          onFormFailure($currentForm, xhr);
        })
        .always(function () {
          afterSubmit($currentForm);
        });
    } else {
      afterSubmit($currentForm);
    }

    event.preventDefault();
  }

  // PR1-I14: the server accepts the form only with a valid nonce. A page served from cache can hold an
  // expired one - then get a fresh nonce and send the form once more.
  function sendForm($form, retryOnInvalidNonce) {
    var result = $.Deferred();

    $.RMP_AJAX('submitElementorForm', getFormData($form), true)
      .done(function (response) {
        result.resolve(response);
      })
      .fail(function (xhr) {
        var data = (xhr && xhr.responseJSON && xhr.responseJSON.data) || {};

        if (retryOnInvalidNonce && xhr && xhr.status === 403 && data.code === 'rmp_invalid_nonce') {
          $.RMP_AJAX('getFormNonce', {}, true)
            .done(function (nonceResponse) {
              if (nonceResponse && nonceResponse._nonce) {
                RMP_AJAX_LOCALS._nonce = nonceResponse._nonce;
              }

              sendForm($form, false).done(result.resolve).fail(result.reject);
            })
            .fail(function () {
              result.reject(xhr);
            });

          return;
        }

        result.reject(xhr);
      });

    return result.promise();
  }

  function getFormData($form) {
    var formData = {
      fields: $form.serializeArray(),
      uriFields: [],
      form_id: $form.data('form_id')
    };

    // Serialize bool unchecked checkboxes
    $form
      .find('[data-field-type="bool"] input[type="checkbox"]:not(:checked)')
      .each(function (index, element) {
        formData['fields'].push({ name: element.getAttribute('name'), value: 'off' });
      });

    // Set URI params and remove []
    _.each(formData.fields, function (field) {
      formData.uriFields.push({
        name:  $form.find('[name="' + field.name + '"]').data('uri_param'),
        value: field.value
      });
    });

    return formData;
  }

  function onFormSent($form, response) {
    var actionAfterSubmit = $form.data('action_after_submit'),
        urlThankYou       = $form.data('urlthankyou'),
        openNew           = $form.data('urlthankyou-opennew') === 'yes',
        addParams         = $form.data('urlthankyou-addparams') === 'yes',
        uriFields         = getFormData($form).uriFields;

    $form
      .find('.fields-wrapper').hide().end()
      .find('.responder-message-sent').show().end();

    if (actionAfterSubmit === 'redirect_to_thankyou_page') {
      if (addParams) {
        urlThankYou = buildThankYouUrl(urlThankYou, uriFields);
      }

      if (openNew) {
        window.open(urlThankYou)
      } else {
        window.location.href = urlThankYou;
      }
    }
  }

  // Adds the form fields to the thank-you URL, each value encoded exactly once, with the right
  // "?" / "&" separator. Fields without a URL parameter name or without a value are skipped (PR1-I9).
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

    _.each(fields || [], function (field) {
      var name  = field && field.name,
          value = field && field.value;

      if (!name || value === undefined || value === null || String(value).trim() === '') {
        return;
      }

      url.searchParams.append(safeUriParam(name, sameSite), value);
    });

    return url.toString();
  }

  // PR1-I15: a deleted form gets the server's own message ("no longer active") - "try again" would never work.
  function onFormFailure($form, xhr) {
    var data = (xhr && xhr.responseJSON && xhr.responseJSON.data) || {};

    // PR1-I15 / PR1-I10: the server's own message (the lead did not reach רב מסר, or the form no longer exists).
    if ((data.code === 'rmp_form_not_found' || data.code === 'rmp_submission_failed') && data.message) {
      showErrorMessages($form, [$('<span>').text(data.message).html()]);
      return;
    }

    showErrorMessages($form, [
      getErrorMessage('server_error')
    ]);
  }

  function beforeSubmit($form) {
    var $errorMessages = $form.find('.responder-message-form-error'),
      $submitButton = $form.find('.res-button-submit');

    $errorMessages.empty().hide();
    $submitButton
      .attr('disabled', true)
      .val($submitButton.data('submitting_text'));
  }

  function afterSubmit($form) {
    var $submitButton = $form.find('.res-button-submit');

    $submitButton
      .val($submitButton.data('original_text'))
      .attr('disabled', false);
  }

  function validateForm($form) {
    var $fields = $form.find('.res-form-field-input'),
      errorMessages = [],
      isFormValid = true;

    _.each($fields, function (field) {
      var $field = $(field),
          errorMessage = validateField($field);

      if (errorMessage.length) {
        errorMessages.push(errorMessage);
      }
    });

    if (errorMessages.length) {
      showErrorMessages($form, errorMessages);
      isFormValid = false;
    }

    return isFormValid;
  }

  function validateField($field) {
    var fieldType    = $field.data('field-type'),
        fieldLabel   = $field.find('.elementor-field-label').text().trim(),
        $fieldInputs = $field.find('input, select, textarea'),
        errorMessage = '',
        regexFilter  = '';

    switch (fieldType) {
      case 'number':
        regexFilter = /^-?\d+$/;

        if (!validateFieldIfRequired($fieldInputs, fieldType)) {
          errorMessage = getErrorMessage('required', fieldLabel);
        } else if (!validateFieldByFilter($fieldInputs, regexFilter)) {
          errorMessage = getErrorMessage('invalid_number');
        }
        break;

      case 'date':
        regexFilter = /^\d{4}[./-]\d{2}[./-]\d{2}$/;

        if (!validateFieldIfRequired($fieldInputs, fieldType)) {
          errorMessage = getErrorMessage('required', fieldLabel);
        } else if (!validateFieldByFilter($fieldInputs, regexFilter)) {
          errorMessage = getErrorMessage('invalid_date');
        }
        break;

      case 'phone':
        if (!validateFieldIfRequired($fieldInputs, fieldType)) {
          errorMessage = getErrorMessage('required', fieldLabel);
        } else if (!validateIsraeliPhone($fieldInputs)) {
          errorMessage = getErrorMessage('invalid_phone');
        }
        break;

      case 'email':
        regexFilter = /\S+@\S+\.\S+/;

        if (!validateFieldIfRequired($fieldInputs, fieldType)) {
          errorMessage = getErrorMessage('required', fieldLabel);
        } else if (!validateFieldByFilter($fieldInputs, regexFilter)) {
          errorMessage = getErrorMessage('invalid_email');
        }
        break;

      default:
        if (!validateFieldIfRequired($fieldInputs, fieldType)) {
          errorMessage = getErrorMessage('required', fieldLabel);
        }
    }

    return errorMessage;
  }

  function validateFieldByFilter($fieldInputs, filter) {
    var value = $fieldInputs.val().trim();

    if (!value.length) {
      return true;
    }

    return filter.test(value);
  }

  // Same rule as PHP Helpers::normalizePhone / isValidIsraeliPhone (PR1-I8):
  // digits only, +972 / 00972 -> 0, then mobile 050-059, 07X (10 digits) or landline 02/03/04/08/09 (9 digits).
  function normalizePhone(value) {
    var digits = String(value || '').replace(/[^\d]/g, '');

    if (digits.indexOf('00972') === 0) {
      digits = digits.substring(2);
    }

    if (digits.indexOf('972') === 0 && digits.length >= 11) {
      digits = digits.substring(3);

      if (digits.indexOf('0') !== 0) {
        digits = '0' + digits;
      }
    }

    return digits;
  }

  function validateIsraeliPhone($fieldInputs) {
    var value = $fieldInputs.val().trim();

    if (!value.length) {
      return true;
    }

    if (/[^\d\s\-+().]/.test(value)) {
      return false;
    }

    return /^0(?:5\d|7\d|[23489])\d{7}$/.test(normalizePhone(value));
  }

  function validateFieldIfRequired($fieldInputs, fieldType) {
    var isRequired = $fieldInputs.parents('.res-form-field').hasClass('elementor-mark-required');

    if (!isRequired) {
      return true;
    }

    switch(fieldType) {
      case 'multichoice':
      case 'bool':
        isRequired = $fieldInputs.is(':checked');
        break;
      default:
        isRequired = $fieldInputs.val().trim().length;
    }

    return isRequired;
  }

  function getErrorMessage(errorType, fieldLabel) {
    var formErrors = $(formWidgetSelector).data('form_errors'),
        message = '';

    fieldLabel = fieldLabel || '';

    if (errorType in formErrors) {
      message = formErrors[errorType] + ' <strong>' + fieldLabel + '</strong>';
    }

    return message;
  }

  function showErrorMessages($form, messages) {
    var errorsBoxSelector = '.responder-message-form-error',
        errorMessagesHtml = [];

    _.each(messages, function(message) {
      errorMessagesHtml.push(message + '<br/>');
    });

    $form.find(errorsBoxSelector)
      .append(errorMessagesHtml)
      .show();
  }

});
