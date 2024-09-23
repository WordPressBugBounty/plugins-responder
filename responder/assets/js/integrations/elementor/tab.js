(function ($) { $(function () {
  var $elementorForms = $('#plugin_config-elementor'),
      $formTemplate   = $elementorForms.find('.unite-repeater-template'),
      $formsSettings  = $elementorForms.find('.unite-settings-repeater'),
      $formsList      = $elementorForms.find('.unite-repeater-items'),
      addFormButtonSelector    = '.unite-repeater-buttonadd',
      deleteFormButtonSelector = '.unite-repeater-buttondelete',
      toggleFormButtonSelector = '.unite-repeater-item-head',
      settingsButtonWrapper    = '.elementor-settings-button-wrapper',
      emptyFormsTextSelector   = '.unite-repeater-emptytext',
      fetchedTags              = [];

  var defaultFormItemValue = {
    'action_on_existing_unite_selected_text': '',
    'form_defaults_type_unite_selected_text': '',
    'is_email_optional': '',

    'action_on_existing': 'update',
    'chosen_system': 'responder',
    'form_defaults_type': 'vert',
    'generated_id': '',
    'list_custom_fields': [],
    'list_id': '',
    'list_id_unite_selected_text': '',
    'onexisting_joindate': '',
    'onexisting_rejoin': '',
    'tags': [],
    'title': ''
  };

  $elementorForms.each(function() {
    $elementorForms
      .on('click', addFormButtonSelector, onAddNewForm)
      .on('click', deleteFormButtonSelector, onDeleteForm)
      .on('click', toggleFormButtonSelector, onToggleForm)
      .on('click', settingsButtonWrapper + ' #res_button_save_elementor_settings', onSaveForm)
      .on('input', onFormChanges);

    renderForms();
  });

  function getRandomString(numChars) {
    var text = '';
    var possible = 'abcdefghijklmnopqrstuvwxyz0123456789';

    if (!numChars) {
      numChars = 8;
    }

    for (var index = 0; index < numChars; index++) {
      text += possible.charAt(Math.floor(Math.random() * possible.length));
    }

    return text;
  }

  function onAddNewForm(event) {
    // Create and Add new form
    var $newForm = $formTemplate.clone().removeClass('unite-repeater-template'),
        newFormItemValue = _.extend({}, defaultFormItemValue);

    // Name & Title
    var formTitle = $newForm.find('.unite-repeater-title').text().trim() + ' ' + ($formsList.children().length + 1);
    $newForm
      .find('.unite-repeater-title')
        .text(formTitle)
      .end()
      .find('[name="title"]')
        .val(formTitle);
    newFormItemValue['title'] = formTitle;

    // Generate Id
    newFormItemValue['generated_id'] = getRandomString(7);

    // Set chosen system
    newFormItemValue['chosen_system'] = $newForm.find('[name="chosen_system"]').val();

    // Add form options as a data attribute
    $newForm.data('itemvalue', newFormItemValue);

    // Hide empty forms text
    $(emptyFormsTextSelector).hide();

    // Form adjustments to match supported fields by system
    adjustFormToSystem(
      newFormItemValue, $newForm
    );

    // Collapse all forms and append the new form
    $formsList
      .children()
        .addClass('unite-item-closed')
      .end()
      .prepend($newForm);

    event.preventDefault();
  }

  function onDeleteForm(event) {
    $(event.currentTarget).parents('.unite-repeater-item').remove();

    // Show empty forms text
    if (!$('.unite-repeater-items').children().length) {
      $(emptyFormsTextSelector).show();
    }

    event.preventDefault();
  }

  function onToggleForm(event) {
    $(event.currentTarget).parents('.unite-repeater-item').toggleClass('unite-item-closed');
    event.preventDefault();
  }

  function onFormChanges(event) {
    var $target = $(event.target),
        $item   = $target.parents('.unite-repeater-item'),
        itemValue = $item.data('itemvalue'),
        name    = $target.attr('name'),
        value   = $target.val();

    // Change form title while typing new title
    if ($target.is('[name="title"]')) {
      $item.find('.unite-repeater-title').html(_.escape(value));
    }

    // Reset value if checkbox is unchecked
    if ($target.is('[type="checkbox"]') && !$target.is(':checked')) {
      value = '';
    }

    // Set value
    itemValue[name] = value;

    // Create and add a new tag if needed
    if ($target.is('[name="tags"]')) {
      var fetchedTagsIds = _.pluck(fetchedTags, 'id'),
          newTagsIds     = _.difference(itemValue.tags, fetchedTagsIds),
          newTagName     = '';

      if (newTagsIds.length) {
        newTagName = newTagsIds.pop();

        $item
          .find('[name="tags"]')
          .attr('disabled', true);

        $.RMP_AJAX('createSubscribersTag', { tag_name: newTagName })
          .done(function(newTag) {
            fetchedTags.push(newTag);

            itemValue.tags.pop();
            itemValue.tags.push(newTag.id);
          })
          .always(function() {
            adjustFormToSystem(itemValue, $item);
          });
      }
    }

    // Reset fields on system change
    if ($target.is('[name="chosen_system"]')) {
      itemValue = _.extend({}, itemValue, {
        'action_on_existing': 'update',
        'form_defaults_type': 'vert',
        'is_email_optional': '',
        'list_custom_fields': [],
        'list_id_unite_selected_text': '',
        'list_id': '',
        'onexisting_joindate': '',
        'onexisting_rejoin': '',
        'tags': [],
      });

      adjustFormToSystem(itemValue, $item);
    }

    // Save value
    $item.data('itemvalue', itemValue);
  }

  function onSaveForm(event) {
    var itemValues      = [],
        $buttonWrapper  = $(settingsButtonWrapper),
        $saveButton     = $buttonWrapper.find('#res_button_save_elementor_settings'),
        $loadingText    = $buttonWrapper.find('#res_saving_text'),
        $successMessage = $buttonWrapper.find('#res_message_saved'),
        $errorMessage   = $buttonWrapper.find('#res_message_error');

    $formsList.children().each(function(index, form) {
      var $form = $(form),
          dataset = $form.data('itemvalue'),
          selectedFormName = $form.find('[name="list_id"] option:not(:first-child):selected').html();

      // Set list name
      if (selectedFormName) {
        dataset['list_id_unite_selected_text'] = selectedFormName.trim();
      }

      // Clear personal fields
      dataset['list_custom_fields'] = [];

      itemValues.push(dataset);
    });

    $saveButton.hide();
    $loadingText.show();

    $.RMP_AJAX('saveElementorFormsSettings', { 'forms_data': itemValues }, true)
      .done(function() {
        $loadingText.hide();
        $successMessage.show(0).delay(700).hide(0);
        $saveButton.delay(700).show(0);
      })
      .fail(function() {
        $loadingText.hide();
        $errorMessage.show(0).delay(700).hide(0);
        $saveButton.delay(700).show(0);
      });
  }

  function renderForms() {
    var itemValues = $formsSettings.data('itemvalues');

    if (typeof itemValues !== 'string' && itemValues.length) {
      _.each(itemValues, function(itemValue) {
        var $formTemplateClone = $formTemplate.clone()
            itemValueClone = _.extend({}, defaultFormItemValue, itemValue);

        $formTemplateClone
          // Include form data
          .data('itemvalue', itemValueClone)

          // Remove template class and add body close class
          .removeClass('unite-repeater-template')
          .addClass('unite-item-closed')

          // Set chosen system
          .find('[name="chosen_system"]')
            .val(itemValueClone.chosen_system)
            .children('option[value="' + itemValueClone.chosen_system + '"]')
              .prop('selected', true)
            .end()
          .end()

          // Set form title
          .find('.unite-repeater-title').html(itemValueClone.title).end()
          .find('[name="title"]').val(itemValueClone.title).end()

          // Set form type
          .find('[name="form_defaults_type"] option[value="' + itemValueClone.form_defaults_type + '"]')
            .prop('selected', true)
          .end()

        // Form adjustments to match supported fields by system
        adjustFormToSystem(
          itemValueClone, $formTemplateClone
        );

        // Append cloned form template to forms list
        $formTemplateClone
          .appendTo($formsList);
      });

      // Hide empty forms text
      $(emptyFormsTextSelector).hide();

      $formsList.children().first().removeClass('unite-item-closed');
    }
  }

  function adjustFormToSystem(itemValue, $form) {

    // Fields toggle by system
    switch(itemValue.chosen_system) {
      case 'responder':
        $form
          .find('[name="tags"], [name="onexisting_rejoin"], [name="onexisting_joindate"]')
            .parents('tr').addClass('hidden').end().end()
          .find('[name="action_on_existing"]')
            .parents('tr').removeClass('hidden').end().end();
        break;

      case 'responder_live':
        $form
          .find('[name="action_on_existing"]')
            .parents('tr').addClass('hidden').end().end()
          .find('[name="tags"], [name="onexisting_rejoin"], [name="onexisting_joindate"]')
            .parents('tr').removeClass('hidden').end().end();

        // Set tags
        var $tagsList = $form.find('[name="tags"]').attr('disabled', true);
        if (!fetchedTags.length) {
          $.RMP_AJAX('getSubscribersTags').done(function(tags) {
            fetchedTags = [].concat(tags);
            renderTags(itemValue.tags, fetchedTags, $tagsList);
          });
        } else {
          renderTags(itemValue.tags, fetchedTags, $tagsList);
        }
        break;
      }

      // Set what to do on subscriber existance
      $form
        .find('[name="action_on_existing"] option[value="' + itemValue.action_on_existing + '"]')
          .prop('selected', true)
        .end()
        .find('[name="onexisting_rejoin"]')
          .prop('checked', itemValue.onexisting_rejoin.length > 0)
        .end()
        .find('[name="onexisting_joindate"]')
          .prop('checked', itemValue.onexisting_joindate.length > 0)
        .end()

      // Set subscription lists
      var $subscriptionLists = $form.find('[name="list_id"]').attr('disabled', true);
      $.RMP_AJAX('getListsBySystemName', { system_name: itemValue.chosen_system })
        .done(function(lists) {
          renderSubscriptionList(itemValue.list_id, lists, $subscriptionLists);
        });
  }

  function renderSubscriptionList(listId, lists, $subscriptionLists) {
    var optionTags = '',
        selectedId = '';

    _.each(lists, function(option) {
      if (listId == option.id) {
        selectedId = option.id;
      }

      optionTags += '<option value="' + option.id + '">' + option.name + '</option>';
    });

    $subscriptionLists
      .find('option:not(:first-child)')
        .remove()
      .end()
      .append(optionTags)
      .find('option[value="' + selectedId + '"]')
        .attr('selected', true)
      .end()
      .removeAttr('disabled')
      .select2({
        dir: 'rtl',
        minimumResultsForSearch: 5
      });
  }

  function renderTags(selectedTagsIds, tags, $tagsList) {
    var optionTags = '';

    _.each(tags, function (tag) {
      var selected = '';

      if (_.contains(selectedTagsIds, tag.id)) {
        selected = 'selected';
      }

      optionTags += '<option ' + selected + ' value="' + tag.id + '">' + tag.name + '</option>';
    });

    $tagsList
      .empty()
      .append(optionTags)
      .removeAttr('disabled')
      .select2({
        dir: 'rtl',
        minimumResultsForSearch: 5,
        tags: true
      });
  }
}); }(jQuery));
