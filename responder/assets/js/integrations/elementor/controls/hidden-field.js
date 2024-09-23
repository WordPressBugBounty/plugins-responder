jQuery(window).on('elementor:init', function () {
  var HiddenFieldItemView = elementor.modules.controls.BaseData.extend({
    onReady: function () {
      var inputType = this.model.get('input_type');

      if (inputType === 'date') {
        this.formatDateValue();
      }
    },

    formatDateValue() {
      var dateValue = Date.parse(this.getControlValue()),
          formattedDate = '';

      if (dateValue) {
        dateValue = (new Date(dateValue))
          .toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' })
          .split('/');

        formattedDate = [dateValue[2], dateValue[0], dateValue[1]].join('-');
        this.$el.find('input[type="date"]').val(formattedDate);
      }
    }
  });

  elementor.addControlView('responder_hidden_field', HiddenFieldItemView);
});
