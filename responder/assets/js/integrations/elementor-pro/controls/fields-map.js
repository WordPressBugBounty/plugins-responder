jQuery(window).on('elementor:init', function () {
  var fieldsMapCache = {},
      currentFormWidgetModel = {},
      UNDERSCORE_TEMPLATE_SETTINGS = {
        variable: 'data',
        evaluate: /<%([\s\S]+?)%>/g,
        interpolate: /<%=([\s\S]+?)%>/g,
        escape: /<%-([\s\S]+?)%>/g
      };

  elementor.hooks.addAction('panel/open_editor/widget/form', function (panel, model, view) {
    currentFormWidgetModel = model;
  });

  var fieldsMapItemView = elementor.modules.controls.BaseData.extend({
    getValue: function (value) {
      var controlValue = JSON.parse(this.getControlValue());

      if (typeof value === 'string') {
        if (value === 'chosenSystem') {
          controlValue = jQuery(this.model.get('systemChoiceSelector')).val();
        } else if (typeof controlValue[value] !== 'undefined') {
          controlValue = controlValue[value];
        } else {
          controlValue = '';
        }
      }

      return controlValue;
    },

    saveValue: function (name, value) {
      var controlValue = JSON.parse(this.getControlValue());

      if (typeof name === 'string' && typeof value !== 'undefined' && typeof controlValue[name] !== 'undefined') {
        controlValue[name] = value;
        this.setValue(JSON.stringify(controlValue));
      } else if (typeof name === 'string' && typeof value === 'undefined') {
        this.setValue(name);
      } else {
        this.setValue(JSON.stringify(name));
      }
    },

    resetValue: function () {
      this.saveValue(this.model.get('default'));
    },

    getFieldId: function (id) {
      return this.model.get('fieldIdPrefix') + id;
    },

    convertFormFieldType: function(formField) {
      var chosenSystem   = this.getValue('chosenSystem'),
          fieldTypesMap  = this.model.get('fieldTypesMap'),
          formFieldTypes = [];

      if (formField.allow_multiple.length) {
        formFieldTypes.push('multichoice');
      }

      _.each(fieldTypesMap[chosenSystem], function(fieldTypes, fieldType) {
        if (_.contains(fieldTypes, formField.field_type)) {
          formFieldTypes.push(fieldType);
        }
      });

      return formFieldTypes;
    },

    convertFormFieldOptions: function(fieldOptions) {
      if (!fieldOptions.length) {
        return {};
      }

      var parsedFieldOptions = [],
          options = fieldOptions.split(/[\r\n]/gm);

      _.each(options, function(option) {
        option = option.split('|');

        var name = option[0],
            value = option[0];

        if (option.length === 2) {
          value = option[1];
        }

        parsedFieldOptions.push({
          'name': name,
          'value': value
        });
      });

      return parsedFieldOptions;
    },

    getFormFields: function () {
      var formFields = currentFormWidgetModel.getSetting('form_fields').toJSON(),
          filterdFormFields = [],
          allowedFieldTypes = this.model.get('allowedFieldTypes');

      _.each(formFields, function(formField, formFieldIndex) {
        var filterdFormField = {};

        if (_.contains(allowedFieldTypes, formField.field_type)) {
          filterdFormField = {
            'id': formField.custom_id,
            'name': formField.field_label,
            'type': this.convertFormFieldType(formField)
          };

          if (!formField.field_label.length) {
            filterdFormField.name = 'Item #' + (formFieldIndex + 1);
          }

          if (formField.field_options.length) {
            filterdFormField['options'] = this.convertFormFieldOptions(formField.field_options);
          }

          filterdFormFields.push(filterdFormField);
        }
      }, this);

      return filterdFormFields;
    },

    createFieldsMap: function(formFields, personalFields) {
      var fieldsMap   = [],
          savedFields = this.getValue('fields'),
          selectedIds = [];

      _.each(personalFields, function(personalField) {
        var fieldMap = {},
            savedFieldId = this.getFieldId(personalField.id),
            isPersonalFieldSaved = !_.isUndefined(savedFields[savedFieldId]);

        fieldMap['personalField'] = JSON.parse(JSON.stringify(personalField));
        fieldMap['formFields']    = [];
        fieldMap['fieldOptions']  = [];

        _.each(formFields, function(formField) {
          var mappedFormField = JSON.parse(JSON.stringify(formField)),
              isFieldSelected = savedFields[savedFieldId] === formField.id;

          if (formField.type.indexOf(personalField.type) !== -1 || isFieldSelected ) {
            if (isPersonalFieldSaved && isFieldSelected) {
              mappedFormField['selected'] = true;
              selectedIds.push(formField.id);

              if (personalField.options && formField.options) {
                _.each(personalField.options, function(personalFieldOption) {
                  var mappedFieldOptions = {
                        personalFieldOption: {
                          id: personalField.id + '_' + personalFieldOption.id,
                          name: personalFieldOption.name
                        },
                        formFieldOptions: JSON.parse(JSON.stringify(formField.options)),
                        formFieldId: formField.id
                      },
                      savedOptionId = this.getFieldId(mappedFieldOptions.personalFieldOption.id),
                      isPersonalFieldOptionSaved = !_.isUndefined(savedFields[savedOptionId]);

                  if (isPersonalFieldOptionSaved) {
                    _.each(mappedFieldOptions.formFieldOptions, function(formFieldOption, formFieldOptionIndex) {
                      if (savedFields[savedOptionId] === formFieldOption.value) {
                        mappedFieldOptions.formFieldOptions[formFieldOptionIndex]['selected'] = true;
                        selectedIds.push(formField.id + '_' + formFieldOption.value);
                      }
                    });
                  }

                  fieldMap['fieldOptions'].push(mappedFieldOptions);
                }, this);
              }
            }

            fieldMap['formFields'].push(mappedFormField);
          }
        }, this);

        fieldsMap.push(fieldMap);
      }, this);

      // Make sure selected fields are disabled every where else.
      _.each(fieldsMap, function(fieldMap, fieldMapIndex) {
        var savedFieldId = this.getFieldId(fieldMap.personalField.id);

        _.each(fieldMap.formFields, function(formField, formFieldIndex) {
          if (savedFields[savedFieldId] !== formField.id && _.contains(selectedIds, formField.id)) {
            fieldsMap[fieldMapIndex]['formFields'][formFieldIndex]['disabled'] = true;
          }
        }, this);

        _.each(fieldMap.fieldOptions, function(fieldOption, fieldOptionIndex) {
          var savedFieldOptionId = this.getFieldId(fieldOption.personalFieldOption.id);

          _.each(fieldOption.formFieldOptions, function(formFieldOption, formFieldOptionIndex) {
            var selectedFormFieldOptionId = fieldOption.formFieldId + '_' + formFieldOption.value;

            if (savedFields[savedFieldOptionId] !== formFieldOption.value && _.contains(selectedIds, selectedFormFieldOptionId)) {
              fieldsMap[fieldMapIndex]['fieldOptions'][fieldOptionIndex]['formFieldOptions'][formFieldOptionIndex]['disabled'] = true;
            }
          });

        }, this);
      }, this);

      return fieldsMap;
    },

    onReady: function () {
      // Initiate list
      this.$el.find('.subscribers-list-box select')
        .select2(this.model.get('select2options'))
        .on('change', this.onListChange.bind(this));

      // Listen to field map change
      this.$el.on(
        'change', '.fields-map-box select', this.onFieldMapChange.bind(this)
      );

      // Listen to System Choice control change
      jQuery('#elementor-panel').on(
        'change',
        this.model.get('systemChoiceSelector'),
        this.onSystemChoiceChange.bind(this)
      );

      // Delay API calls so other DOM controls to be ready.
      setTimeout(this.getLists.bind(this), 10);
      setTimeout(this.getPersonalFields.bind(this), 10);
    },

    onListChange: function (event) {
      this.resetValue();
      this.saveValue('list_id', event.currentTarget.value);
      this.getPersonalFields();
    },

    onFieldMapChange: function(event) {
      var fieldId    = event.currentTarget.id.replace('rmp-', ''),
          fieldValue = event.currentTarget.value.replace('rmp-', ''),
          fields     = this.getValue('fields'),
          fieldKey   = this.getFieldId(fieldId);

      if (fieldValue.length) {
        fields[fieldKey] = fieldValue;
      } else {
        delete fields[fieldKey];
      }

      this.saveValue('fields', fields);
      this.getPersonalFields();
    },

    onSystemChoiceChange: function () {
      this.resetValue();
      this.hideFieldsMap();
      this.getLists();
    },

    getLists: function() {
      var chosenSystem     = this.getValue('chosenSystem'),
          actionName       = 'RMP_getListsBySystemName',
          cachedActionName = actionName + '_' + chosenSystem;

      if (typeof fieldsMapCache[cachedActionName] !== 'undefined') {
        this.renderLists(fieldsMapCache[cachedActionName]);
      } else {
        this.addControlSpinner('.subscribers-list-box');

        jQuery.ajax({
          url: this.model.get('adminUrl'),
          type: 'post',
          data: {
            action: actionName,
            system_name: chosenSystem,
            _nonce: this.model.get('_nonuce')
          },
          success: function (lists) {
            fieldsMapCache[cachedActionName] = lists;
            this.renderLists(lists);
            this.removeControlSpinner('.subscribers-list-box');
          }.bind(this)
        });
      }
    },

    renderLists: function(lists) {
      var $selectTag = this.$el.find('.subscribers-list-box select'),
        optionTags = '',
        listId = this.getValue('list_id');

      _.each(lists, function (option) {
        optionTags += '<option value="' + option.id + '">' + option.name + '</option>';
      });

      $selectTag
        .find('option')
          .not(':first').remove().end()
        .end()
        .prop('selectedIndex', 0)
        .append(optionTags)
        .find('option')
          .filter('[value="' + listId + '"]').attr('selected', true).end()
        .end()
        .select2(this.model.get('select2options'));
    },

    getPersonalFields: function() {
      var chosenSystem     = this.getValue('chosenSystem'),
          listId           = this.getValue('list_id'),
          actionName       = 'RMP_getPersonalFieldsByListId',
          cachedActionName = actionName + '_' + listId;

      if (!listId.length) {
        return false;
      } else if (typeof fieldsMapCache[cachedActionName] !== 'undefined') {
        fieldsMap = this.createFieldsMap(
          this.getFormFields(),
          fieldsMapCache[cachedActionName]
        );

        this.showFieldsMap();
        this.renderFieldsMap(fieldsMap);
      } else {
        this.showFieldsMap();
        this.addControlSpinner('.fields-map-box');

        jQuery.ajax({
          url: this.model.get('adminUrl'),
          type: 'post',
          data: {
            action: actionName,
            system_name: chosenSystem,
            list_id: listId,
            _nonce: this.model.get('_nonuce')
          },
          success: function (personalFields) {
            var fieldsMap = [];
            fieldsMapCache[cachedActionName] = personalFields;

            fieldsMap = this.createFieldsMap(
              this.getFormFields(),
              personalFields
            );

            this.renderFieldsMap(fieldsMap);
            this.removeControlSpinner('.fields-map-box');
          }.bind(this)
        });
      }
    },

    renderFieldsMap: function(fieldsMap) {
      var fieldsMapTemplate = [],
          compiledFieldTemplate = _.template(
            this.$el.find('#field-map-template').html(),
            UNDERSCORE_TEMPLATE_SETTINGS
          );

      _.each(fieldsMap, function(fieldMap) {
        fieldsMapTemplate.push(compiledFieldTemplate(fieldMap));
      });

      this.$el
        .find('.fields-map-box .elementor-control-content')
        .html(fieldsMapTemplate);
    },

    showFieldsMap: function () {
      this.$el
        .find('.fields-map-box')
          .css('display', 'block')
          .find('.elementor-control-content')
            .empty();
    },

    hideFieldsMap: function () {
      this.$el.find('.fields-map-box')
        .css('display', 'none')
        .find('.elementor-control-content')
          .empty();
    },

    addControlSpinner: function (selector) {
      var $element = this.$el.find(selector),
          $input = $element.find(':input'),
          $controlTitle = $element.find('> .elementor-control-title, > label .elementor-control-title');

      if ($input.attr('disabled')) {
        return;
      }

      $input.attr('disabled', true);

      $controlTitle.after(
        '<span class="elementor-control-spinner"><i class="eicon-spinner eicon-animation-spin"></i>&nbsp;</span>'
      );
    },

    removeControlSpinner: function (selector) {
      this.$el.find(selector)
        .find(':input').attr('disabled', false).end()
        .find('.elementor-control-spinner').remove();
    }
  });

  elementor.addControlView('responder_fields_map', fieldsMapItemView);
});
