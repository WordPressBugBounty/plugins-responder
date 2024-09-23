jQuery(window).on('elementor:init', function () {
  var cachedTags = [];
  var tagsItemView = elementor.modules.controls.BaseData.extend({
    saveNewTag: function(tag) {
      var tags    = this.getControlValue(),
          newTags = [].concat(tags, [tag]);

      this.setValue(newTags);
    },

    saveTags: function (tags) {
      var updatedTags = [].concat(tags);
      this.setValue(updatedTags);
    },

    resetValue: function () {
      this.setValue(this.model.get('default'));
    },

    isDarkModeEnabled: function() {
      var $elementorDarkCssLink = jQuery('#elementor-editor-dark-mode-css');

      if ($elementorDarkCssLink.length) {
        return true;
      }

      return false;
    },

    onReady: function () {
      // Loads tags
      this.getTags();

      // Initial tagify variable
      this.tagify = null;

      // Check for dark mode and add a dark class
      if (this.isDarkModeEnabled()) {
        this.$el.addClass('dark-tags');
      }

      // Listen to tag changes
      this.$el.on('change', 'input[type="tagify"]', this.onTagsChange.bind(this));

      // Reset control's value to default
      // When those select boxes are change value
      jQuery('#elementor-panel').on(
        'change',
        'select[data-setting="responder_system_choice"], .subscribers-list-box select',
        this.onChangeReset.bind(this)
      );
    },

    onTagsChange: function(event) {
      if (event.originalEvent instanceof CustomEvent && event.type === 'change') {
        var selectedTags = event.currentTarget.value.length ? JSON.parse(event.currentTarget.value) : [],
            newTags      = _.filter(selectedTags, function(tag) { return typeof tag.id === 'undefined'; }),
            updateTags   = _.filter(selectedTags, function(tag) { return typeof tag.id !== 'undefined'; });

        this.saveTags(updateTags);

        if (newTags.length) {
          this.createTag(newTags.pop()['name']);
        }

        this.tagify.dropdown.hide.call(this.tagify);
      }
    },

    onChangeReset: function() {
      this.resetValue();
      this.renderTags(cachedTags);
    },

    createTag: function(tagName) {
      var tagNames = _.pluck(cachedTags, 'name');

      if (_.contains(tagNames, tagName)) {
        this.renderTags(cachedTags);
      } else {
        this.addControlSpinner();
        jQuery.ajax({
          url: this.model.get('adminUrl'),
          type: 'post',
          data: {
            action: 'RMP_createSubscribersTag',
            tag_name: tagName,
            _nonce: this.model.get('_nonuce')
          },
          success: function (newTag) {
            if (newTag.id.length) {
              newTag['value'] = newTag.id;
              this.saveNewTag(newTag);
              cachedTags.push(newTag);
            }

            this.renderTags(cachedTags);
            this.removeControlSpinner();
          }.bind(this)
        });
      }
    },

    getTags: function() {
      this.addControlSpinner();

      jQuery.ajax({
        url: this.model.get('adminUrl'),
        type: 'post',
        data: {
          action: 'RMP_getSubscribersTags',
          _nonce: this.model.get('_nonuce')
        },
        success: function (tags) {
          _.each(tags, function (tag, index) {
            tags[index]['value'] = tag.id;
          });

          cachedTags = [].concat(tags);

          this.renderTags(cachedTags);
          this.removeControlSpinner();
        }.bind(this)
      });
    },

    renderTags: function(tags) {
      var tagifyElement = this.$el.find('input[type="tagify"]')[0],
          tagifyDropdownClassName = 'rmp-elementor-pro-tags-dropdown',
          selectedTags = JSON.stringify(
            this.getControlValue()
          );

      if (this.tagify) {
        this.tagify.destroy();
        this.tagify = null;
      }

      if (this.isDarkModeEnabled()) {
        tagifyDropdownClassName = 'dark-tags ' + tagifyDropdownClassName;
      }

      tagifyElement.value = selectedTags;
      this.tagify = new Tagify(tagifyElement, {
        'tagTextProp': 'name',
        'duplicates': false,
        'whitelist': tags,
        'editTags': false,
        'skipInvalid': true,
        'autoComplete': {
          'enabled': true,
          'rightKey': true
        },
        'dropdown': {
          'appendTarget': this.$el.find('#rmp-elementor-pro-tags-dropdown-placement')[0],
          'classname': tagifyDropdownClassName,
          'closeOnSelect': true,
          'enabled': 0,
          'mapValueTo': 'name',
          'maxItems': 0,
          'placeAbove': false,
          'searchKeys': ['name']
        }
      });
    },

    addControlSpinner: function () {
      var $element = this.$el,
          $input = $element.find(':input'),
          $controlTitle = $element.find('.elementor-control-title');

      if (this.tagify) {
        this.tagify.setReadonly(true);
      }

      if ($input.attr('disabled')) {
        return;
      }

      $input.attr('disabled', true);

      $controlTitle.after(
        '<span class="elementor-control-spinner"><i class="eicon-spinner eicon-animation-spin"></i>&nbsp;</span>'
      );
    },

    removeControlSpinner: function () {
      if (this.tagify) {
        this.tagify.setReadonly(false);
        this.tagify.setDisabled(false);
      }

      this.$el
        .find(':input').attr('disabled', false).end()
        .find('.elementor-control-spinner').remove();
    }
  });

  elementor.addControlView('responder_live_tags', tagsItemView);
});
