(function ($) {
  var fetchedTags = [];

  $(function () {
    var $cf7Panel            = $('#cf7-responder-panel'),
        $cf7PanelTabLink     = $('#Responder-Extension-tab'),
        $subscribersList     = $cf7Panel.find('#responder-subscribers-list'),
        $tagsList            = $cf7Panel.find('#responder-tags'),
        $chosenSystem        = $cf7Panel.find('#responder-chosen-system'),
        $customFieldsList    = $cf7Panel.find('#responder-custom-fields'),
        $addCustomField      = $cf7Panel.find('#add-custom-field'),
        $postSaveButton      = $('[name="wpcf7-save"]'),

        activeTabUriParam    = 'active-tab=' + $cf7PanelTabLink.index();

    $cf7Panel.each(function() {
      $chosenSystem.on('change', onChoosingSystem);
      $tagsList.on('change', onTagsChange);
      $subscribersList.on('change', onSubscibersListChange);
      $addCustomField.on('click', onAddCustomField);
      $customFieldsList.on('change', '.custom-field .field-map select', onUpdateCustomField);

      if (location.search.indexOf(activeTabUriParam) !== -1) {
        initPanel();
      } else {
        $cf7PanelTabLink.one('click', initPanel);
      }
    });

    function initPanel() {
      onChoosingSystem();

      $cf7Panel.find('[name="responder[save_changes]"]').val(true);
    }

    function onChoosingSystem() {
      var chosenSystem = $chosenSystem.val();

      $subscribersList.attr('disabled', true);
      $tagsList.attr('disabled', true);

      switch(chosenSystem) {
        case 'responder':
          $cf7Panel
            .find('[name="responder[tags][]"], [name="responder[first]"], [name="responder[last]"]')
              .prop('value', '').parents('.mail-field').addClass('hidden').end().end()
            .find('[name="responder[onexisting_rejoin]"], [name="responder[onexisting_joindate]"]')
              .prop('checked', false).parents('.mail-field').addClass('hidden').end().end()
            .find('[name="responder[onexisting]"]')
              .parents('.mail-field').removeClass('hidden').end().end();
          break;

        case 'responder_live':
          $cf7Panel
            .find('[name="responder[onexisting]"]')
              .prop('selectedIndex', 0).parents('.mail-field').addClass('hidden').end().end()
            .find('[name="responder[tags][]"], [name="responder[onexisting_rejoin]"], [name="responder[onexisting_joindate]"], [name="responder[first]"], [name="responder[last]"]')
              .parents('.mail-field').removeClass('hidden').end().end();

          $.RMP_AJAX('getSubscribersTags')
            .done(function (tags) {
              fetchedTags = [].concat(tags);
              renderTags(fetchedTags, $tagsList, $tagsList.data('value'));
            });
          break;
      }

      $postSaveButton.attr('disabled', true);

      $.RMP_AJAX('getListsBySystemName', { system_name: chosenSystem })
        .done(function (lists) {
          renderSubscribersLists(lists, $subscribersList);
        });

      $(document).ajaxStop(function () {
        $postSaveButton.removeAttr('disabled');
      });
    }

    function onAddCustomField() {
      toggleAddCustomFieldButton('off');

      $postSaveButton.attr('disabled', true);
      $.RMP_AJAX('getPersonalFieldsByListId', {
        list_id: $subscribersList.val(),
        system_name: $chosenSystem.val()
      })
      .done(renderCustomField)
      .always(function() {
        toggleAddCustomFieldButton('on')
        $postSaveButton.removeAttr('disabled');
      });
    }

    function onTagsChange(event) {
      var $selectBox = $(event.currentTarget),
          currentTagsIds = [],
          fetchedTagsIds = _.pluck(fetchedTags, 'id'),
          selectedTagsIds = [],
          newTagsIds = [];

      _.each($selectBox.find('option'), function (option) {
        var tagId           = option.value,
            selectBoxTagIds = $selectBox.val() || [],
            newTagName      = '';

        if (!isNaN(tagId) && _.contains(selectBoxTagIds, tagId)) {
          selectedTagsIds.push(tagId);
        }

        currentTagsIds.push(tagId);
      });

      newTagsIds = _.difference(currentTagsIds, fetchedTagsIds);

      if (newTagsIds.length) {
        newTagName = newTagsIds.pop();

        $selectBox.attr('disabled', true);
        $.RMP_AJAX('createSubscribersTag', { tag_name: newTagName })
          .done(function (newTag) {
            fetchedTags.push(newTag);
            selectedTagsIds.push(newTag.id);
          })
          .always(function() {
            renderTags(fetchedTags, $selectBox, selectedTagsIds);
            $selectBox.attr('disabled', false);
          });
      }
    }

    function onSubscibersListChange(event) {
      toggleAddCustomFieldButton('off');
      $postSaveButton.attr('disabled', true);
      $customFieldsList.empty();

      $.RMP_AJAX('getPersonalFieldsByListId', {
        list_id: $subscribersList.val(),
        system_name: $chosenSystem.val()
      })
      .done(function(personalFields) {
        var haveCustomFields = _.some(personalFields, function(personalField) { return !isNaN(personalField.id) });

        if (haveCustomFields) {
          toggleCustomFieldsWrapper('on');
          renderCustomFields(personalFields);
        } else {
          toggleCustomFieldsWrapper('off');
        }
      })
      .always(function() {
        $postSaveButton.removeAttr('disabled');
      });
    }

    function onUpdateCustomField(event) {
      var $customField = $(event.currentTarget).parents('.custom-field');
          selectedPersonalFieldId = parseInt(event.currentTarget.value),
          customField = {
            'key': selectedPersonalFieldId,
            'value': $customField.find('.field-map input').val()
          };

      $.RMP_AJAX('getPersonalFieldsByListId', {
        list_id: $subscribersList.val(),
        system_name: $chosenSystem.val()
      })
        .done(function (personalFields) {
          var selectedPersonalField = _.findWhere(personalFields, { id: selectedPersonalFieldId }),
              customFieldTemplate   = generateCustomField(personalFields, selectedPersonalField, customField);

          $customField.replaceWith(customFieldTemplate);
        });
    }

    function renderTags(tags, $tagsList, selectedTagsIds) {
      var optionTags = '';

      selectedTagsIds = selectedTagsIds || [];

      _.each(tags, function (tag) {
        var selected = '';

        if (_.contains(selectedTagsIds, tag.id)) {
          selected = 'selected';
        }

        optionTags += '<option value="' + tag.id + '" ' + selected + '>' + tag.name + '</option>';
      });

      $tagsList
        .empty()
        .append(optionTags)
        .removeAttr('disabled')
        .select2({
          dir: RMP_AJAX_LOCALS.direction,
          minimumResultsForSearch: 5,
          tags: true,
          width: '280px'
        });
    }

    function renderSubscribersLists(lists, $subscribersList) {
      var optionTags = '',
          listId = $subscribersList.data('value'),
          selectedId = '';

      _.each(lists, function(option) {
        if (listId && listId == option.id) {
          selectedId = option.id;
        }

        optionTags += '<option value="' + option.id + '">' + option.name + '</option>';
      });

      $subscribersList
        .find('option')
          .remove()
        .end()
        .append(optionTags)
        .find('option[value="' + selectedId + '"]')
          .attr('selected', true)
        .end()
        .removeAttr('disabled')
        .data('value', '')
        .select2({
          dir: RMP_AJAX_LOCALS.direction,
          minimumResultsForSearch: 5,
          width: '280px'
        })
        .trigger('change');
    }

    function renderCustomFields(personalFields) {
      var customFields = $customFieldsList.data('value');

      if (customFields) {
        _.each(customFields, function(customField) {
          var selectedPersonalField = _.findWhere(personalFields, { id: customField.key });

          if (selectedPersonalField) {
            renderCustomField(personalFields, selectedPersonalField, customField);
          }
        });

        $customFieldsList.data('value', '');
      }

      toggleAddCustomFieldButton('on');
    }

    function renderCustomField(personalFields, selectedPersonalField, customField) {
      $customFieldsList.append(
        generateCustomField(personalFields, selectedPersonalField, customField)
      );
    }

    function generateCustomField(personalFields, selectedPersonalField, customField) {
      var compiledCustomFieldTemplate = wp.template('custom-field-template');

      return compiledCustomFieldTemplate({
        fieldIndex: $customFieldsList.find('.custom-field').length + 1,
        customField: customField || {},
        personalFields: _.filter(personalFields, function (personalField) { return !isNaN(personalField.id); }),
        selectedPersonalField: selectedPersonalField || {},
      });
    }

    function toggleAddCustomFieldButton(state) {
      state = state || 'on';

      switch(state) {
        case 'on':
          $addCustomField
            .removeAttr('disabled')
            .html($addCustomField.data('button-text'));
          break;
        case 'off':
          $addCustomField
            .attr('disabled', true)
            .html($addCustomField.data('loading-text'));
          break;
      }
    }

    function toggleCustomFieldsWrapper(state) {
      state = state || 'on';

      var $customFieldsWrapper = $('#responder-custom-fields-wrapper')

      switch(state) {
        case 'on':
          $customFieldsWrapper.show();
          break;
        case 'off':
          $customFieldsWrapper.hide();
          break;
      }
    }

  });
}(jQuery));
