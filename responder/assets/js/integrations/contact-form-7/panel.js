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

        activeTabUriParam    = 'active-tab=' + $cf7PanelTabLink.index(),

        // PR1-I20: what is saved on the form, and what the admin picked per system in this visit.
        savedSystem          = $subscribersList.data('saved-system') || '',
        savedListId          = String($subscribersList.data('value') || ''),
        savedCustomFields    = $customFieldsList.data('value'),
        listBySystem         = {},
        currentSystem        = '',
        stashedLiveSettings  = null,  // tags + "אם הנמען קיים" options while the old system is selected
        listsRendering       = false,

        // PR1-I22: CF7 asks "Leave site?" when any option's selected state differs from its default (the
        // state the page was loaded with). The lists and tags are built after the page loads, so their
        // options must carry the saved state as their default - otherwise an untouched form looks changed.
        savedTagIds          = _.filter(_.map([].concat($tagsList.data('value') || []), String), Boolean),
        pageSystem           = getPageSystem();

    if (savedSystem && savedListId) {
      listBySystem[savedSystem] = savedListId;
    }

    $cf7Panel.each(function() {
      $chosenSystem.on('change', onChoosingSystem);
      $tagsList.on('change', onTagsChange);
      $subscribersList.on('change', onSubscibersListChange);
      $addCustomField.on('click', onAddCustomField);
      $customFieldsList.on('change', '.custom-field .field-map select', onUpdateCustomField);

      // PR1-I22: CF7 6 reopens the editor on the panel with "active-tab=<panel id>" (Responder-Extension) after
      // saving, not with the tab index - load the panel right away whenever it is the active tab.
      if (isPanelActiveOnLoad()) {
        initPanel();
      } else {
        $cf7PanelTabLink.one('click', initPanel);
      }
    });

    // The system the form was loaded with: the saved one, or the pre-selected default of a form that was
    // never connected (PR1-I5).
    function getPageSystem() {
      var field = $chosenSystem.get(0);

      if (!field) {
        return '';
      }

      if (field.tagName === 'SELECT') {
        var defaultOption = _.find(field.options, function(option) { return option.defaultSelected; });

        return defaultOption ? defaultOption.value : (field.options.length ? field.options[0].value : '');
      }

      return field.defaultValue || field.value || '';
    }

    function isPanelActiveOnLoad() {
      var panelKey  = String($cf7PanelTabLink.attr('id') || '').replace(/-tab$/, '') || 'Responder-Extension',
          activeTab = '';

      try {
        activeTab = new URLSearchParams(location.search).get('active-tab') || '';
      } catch (e) {
        activeTab = '';
      }

      return location.search.indexOf(activeTabUriParam) !== -1
        || (activeTab !== '' && (activeTab === panelKey || activeTab === String($cf7PanelTabLink.index())))
        || $('[name="active-tab"]').val() === panelKey;
    }

    function initPanel() {
      onChoosingSystem();

      $cf7Panel.find('[name="responder[save_changes]"]').val(true);

      // PR1-I20: never save the panel with "בחירת רשימה" - the form would silently lose its list.
      $postSaveButton.closest('form').on('submit.rmpCf7', onFormSubmit);
    }

    function onFormSubmit(event) {
      var $required = $cf7Panel.find('.rmp-cf7-list-required');

      // Only a form that is connected to a list is stopped; a form that was never connected stays unconnected.
      if (!savedListId || $cf7Panel.find('[name="responder[save_changes]"]').val() !== 'true' || $subscribersList.val()) {
        $required.attr('hidden', true);
        return;
      }

      event.preventDefault();
      event.stopImmediatePropagation();

      $required.removeAttr('hidden');
      $cf7PanelTabLink.trigger('click');
      try { $subscribersList.select2('open'); } catch (e) {}
    }

    function onChoosingSystem() {
      var chosenSystem = $chosenSystem.val(),
          $liveOptions = $cf7Panel.find('[name="responder[onexisting_rejoin]"], [name="responder[onexisting_joindate]"], [name="responder[onexisting_sms]"]');

      // PR1-I20: keep the list picked in the system we leave, to show it again when the admin comes back.
      if (currentSystem && currentSystem !== chosenSystem && $subscribersList.find('option').length) {
        listBySystem[currentSystem] = String($subscribersList.val() || '');
      }

      $subscribersList.attr('disabled', true);
      $tagsList.attr('disabled', true);

      switch(chosenSystem) {
        case 'responder':
          // Tags and the "אם הנמען קיים" options exist only in רב מסר: kept aside (not sent on save) and
          // put back when the admin switches back before saving. First / last name stay - the old system
          // gets them as the name when "שם מלא" is empty.
          if (currentSystem === 'responder_live') {
            stashedLiveSettings = {
              tags: $tagsList.val() || [],
              options: $liveOptions.map(function() { return this.checked; }).get()
            };
          }

          $cf7Panel
            .find('[name="responder[tags][]"]')
              .find('option').prop('selected', false).end()
              .parents('.mail-field').addClass('hidden').end().end()
            .find('[name="responder[onexisting_rejoin]"], [name="responder[onexisting_joindate]"], [name="responder[onexisting_sms]"]')
              .prop('checked', false).parents('.mail-field').addClass('hidden').end().end()
            .find('[name="responder[onexisting]"]')
              .parents('.mail-field').removeClass('hidden').end().end()
            .find('.rmp-cf7-old-system-name').removeAttr('hidden');

          $cf7Panel.find('.rmp-cf7-dropped-note').attr('hidden', !hasStashedLiveSettings());
          break;

        case 'responder_live':
          $cf7Panel
            .find('[name="responder[onexisting]"]')
              .each(function() {
                // Not used by רב מסר. Reset only when the admin switches to רב מסר in this visit - on load the
                // saved value stays, so an untouched form is not seen as changed (PR1-I22).
                if (currentSystem) {
                  this.selectedIndex = 0;
                }
              })
              .parents('.mail-field').addClass('hidden').end().end()
            .find('[name="responder[tags][]"], [name="responder[onexisting_rejoin]"], [name="responder[onexisting_joindate]"], [name="responder[onexisting_sms]"], [name="responder[first]"], [name="responder[last]"]')
              .parents('.mail-field').removeClass('hidden').end().end()
            .find('.rmp-cf7-old-system-name, .rmp-cf7-dropped-note').attr('hidden', true);

          var selectedTags = $tagsList.data('value');

          if (stashedLiveSettings) {
            selectedTags = stashedLiveSettings.tags;
            $liveOptions.each(function(index) { this.checked = !!stashedLiveSettings.options[index]; });
            stashedLiveSettings = null;
          }

          $.RMP_AJAX('getSubscribersTags')
            .done(function (tags) {
              fetchedTags = [].concat(tags);
              renderTags(fetchedTags, $tagsList, selectedTags);
            });
          break;
      }

      // First / last name are shown for both systems (PR1-I20).
      $cf7Panel.find('[name="responder[first]"], [name="responder[last]"]').parents('.mail-field').removeClass('hidden');

      currentSystem = chosenSystem;
      $postSaveButton.attr('disabled', true);

      $.RMP_AJAX.hideNotConnected($subscribersList);

      $.RMP_AJAX('getListsBySystemName', { system_name: chosenSystem })
        .done(function (lists) {
          renderSubscribersLists(lists, $subscribersList, listBySystem[chosenSystem] || '', chosenSystem);
        })
        .fail(function (xhr, status, errorData) {
          // PR1-I28: the saved list stays in the select (saving keeps it); the message says why nothing loaded.
          $.RMP_AJAX.showNotConnected($subscribersList, status, errorData);
          $postSaveButton.removeAttr('disabled');
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

    function hasStashedLiveSettings() {
      return !!stashedLiveSettings && (stashedLiveSettings.tags.length > 0 || _.contains(stashedLiveSettings.options, true));
    }

    function onSubscibersListChange(event) {
      var listId = String($subscribersList.val() || '');

      if (!listsRendering) {
        listBySystem[currentSystem] = listId;
      }

      if (listId) {
        $cf7Panel.find('.rmp-cf7-list-required').attr('hidden', true);
      }

      $cf7Panel.find('.rmp-cf7-list-missing').attr('hidden', !$subscribersList.find('option:selected').data('missing'));

      toggleAddCustomFieldButton('off');
      $postSaveButton.attr('disabled', true);
      $customFieldsList.empty();

      // PR1-I20: "בחירת רשימה" - no list, no fields to match.
      if (!listId) {
        toggleCustomFieldsWrapper('off');
        $postSaveButton.removeAttr('disabled');
        return;
      }

      // The saved field matching comes back with the saved system + list.
      $customFieldsList.data('value', currentSystem === savedSystem && listId === savedListId ? savedCustomFields : '');

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
      const $cf = $(event.currentTarget).closest('.custom-field');
      const selectedId = parseInt(event.currentTarget.value);
      const personalFieldsPromise = $.RMP_AJAX('getPersonalFieldsByListId', {
        list_id: $subscribersList.val(),
          system_name: $chosenSystem.val()
      });

      personalFieldsPromise.done(function (personalFields) {
        const selected = personalFields.find(pf => pf.id == selectedId);
        const customField = {
          key: selectedId,
          value: $cf.find('.field-map input').val(),
            options: []
        };

        $cf.replaceWith(
          generateCustomField(personalFields, selected, customField)
        );
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
        .append(optionTags);

      // PR1-I22: the saved tags are the default state; the ones shown now (saved, kept from a system switch,
      // or just created) are the current state.
      _.each($tagsList.get(0) ? $tagsList.get(0).options : [], function(option) {
        option.defaultSelected = _.contains(savedTagIds, String(option.value));
      });
      _.each($tagsList.get(0) ? $tagsList.get(0).options : [], function(option) {
        option.selected = _.contains(_.map(selectedTagsIds, String), String(option.value));
      });

      $tagsList
        .removeAttr('disabled')
        .select2({
          dir: RMP_AJAX_LOCALS.direction,
          minimumResultsForSearch: 5,
          tags: true,
          width: '280px'
        });
    }

    // PR1-I20: starts with "בחירת רשימה" and selects only listId (the saved list, or the one picked in this
    // system before) - never the first list. A saved list that is no longer in the account stays selected
    // (so saving does not move the form) and is marked as missing.
    function renderSubscribersLists(lists, $subscribersList, listId, system) {
      var $placeholder = $('<option value=""></option>').text($subscribersList.data('placeholder') || '---'),
          $options     = [$placeholder],
          found        = false;

      listId = String(listId || '');

      _.each(lists, function(option) {
        var $option = $('<option></option>').attr('value', option.id).text(option.name);

        if (listId && listId === String(option.id)) {
          $option.prop('selected', true);
          found = true;
        }

        $options.push($option);
      });

      if (listId && !found) {
        $options.splice(1, 0,
          $('<option></option>')
            .attr('value', listId)
            .text(String($subscribersList.data('missing-text') || '%s').replace('%s', listId))
            .data('missing', true)
            .prop('selected', true)
        );
      } else if (!listId) {
        $placeholder.prop('selected', true);
      }

      listsRendering = true;

      $subscribersList
        .find('option')
          .remove()
        .end()
        .append($options);

      // PR1-I22: the list saved on the form (in the system the page was loaded with) is the default state, so
      // CF7 does not ask "Leave site?" for an untouched form. Another list / system stays a change.
      var defaultListId = system && system === (savedSystem || pageSystem) ? savedListId : null,
          selectedValue = String($subscribersList.val() || '');

      _.each($subscribersList.get(0) ? $subscribersList.get(0).options : [], function(option) {
        option.defaultSelected = defaultListId !== null && String(option.value) === defaultListId;
      });
      _.each($subscribersList.get(0) ? $subscribersList.get(0).options : [], function(option) {
        option.selected = String(option.value) === selectedValue;
      });

      $subscribersList
        .removeAttr('disabled')
        .data('value', '')
        .select2({
          dir: RMP_AJAX_LOCALS.direction,
          minimumResultsForSearch: 5,
          width: '280px'
        })
        .trigger('change');

      listsRendering = false;
    }

    function renderCustomFields(personalFields) {
      const customFields = $customFieldsList.data('value');

      if (customFields) {
        customFields.forEach(function (customField) {
          const selected = personalFields.find(
            pf => pf.id == customField.key
          );
          if (selected) {
              renderCustomField(personalFields, selected, customField);
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
      const index = $customFieldsList.find('.custom-field').length + 1;

      const key = customField?.key || '';
      const value = customField?.value || '';

      let html = `
        <div class="custom-field">

          <div class="field-map">

            <p>
              <label for="responder-custom-value-${index}" data-count="${index}">
                Field Name as shown in the form:
              </label>

              <input type="text"
                    name="responder[CustomValue${index}]"
                    id="responder-custom-value-${index}"
                    placeholder="[field-123]"
                    value="${value}">
            </p>

            <p>
              <label for="responder-custom-key-${index}">
                Match custom field from the Responder list:
              </label>

              <select id="responder-custom-key-${index}"
                      name="responder[CustomKey${index}]">
                <option disabled ${key ? '' : 'selected'}>---</option>
      `;

      personalFields.forEach(function (pf) {
          if (isNaN(pf.id)) return;
          const selected = pf.id == key ? 'selected' : '';
          html += `<option value="${pf.id}" ${selected}>${pf.name}</option>`;
      });

      html += `
              </select>
            </p>

          </div>
      `;

      // ---- OPTIONS section ----
      const selected = selectedPersonalField || {};
      const pfOptions = selected.options || [];

      if (pfOptions.length) {
        html += `
          <div class="field-options-map">
            <span class="description">
              Match options of the field
              <span style="text-decoration: underline">${selected.name}</span>
            </span>
        `;

        pfOptions.forEach((opt, optIndex) => {
          const optValue =
              customField?.options?.[optIndex]?.value || '';
          const optSelectedKey =
              customField?.options?.[optIndex]?.key || '';

          html += `
            <div class="field-option-map">

              <p>
                <input type="text"
                      name="responder[CustomValue${index}_${optIndex + 1}]"
                      placeholder="[field-option-123]"
                      value="${optValue}">
              </p>

              <p>
                <select name="responder[CustomKey${index}_${optIndex + 1}]">
                  <option disabled ${optSelectedKey ? '' : 'selected'}>---</option>
          `;

          pfOptions.forEach(function (pfo) {
              const sel = pfo.id == optSelectedKey ? 'selected' : '';
              html += `<option value="${pfo.id}" ${sel}>${pfo.name}</option>`;
          });

          html += `
                </select>
              </p>

            </div>
          `;
        });

        html += `</div>`;
      }

      html += `</div>`;

      return html;
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
