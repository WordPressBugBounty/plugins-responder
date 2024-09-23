(function ($) {
  $(function () {
    var $subscribersLists = $('.responder-settings-wrapper .subscribers-lists'),
        $fieldsMap        = $('.responder-settings-wrapper .fields-map');
        fieldMapTemplate  = $('.responder-settings-wrapper .field-map').html();

    $subscribersLists
      .on('change', onChoosingList)
      .select2({
        dir: RMP_AJAX_LOCALS.direction,
        minimumResultsForSearch: 5,
      });

    function onChoosingList(event) {
      var listId = event.currentTarget.value;

      $subscribersLists.attr('disabled', true);
      $fieldsMap.empty();

      $.RMP_AJAX('getPersonalFieldsByListId', {
        list_id: listId,
        system_name: 'responder'
      })
      .done(renderFieldsMap)
      .always(function() {
        $subscribersLists.removeAttr('disabled');
      });
    }

    function renderFieldsMap(personalFields) {
      var fieldsMap = [];

      _.each(personalFields, function(personalField) {
        var fieldMap = fieldMapTemplate
          .replace(/{{ fieldId }}/g, personalField.id)
          .replace(/{{ fieldName }}/g, personalField.name)

        fieldsMap.push(fieldMap);
      });

      $fieldsMap.append(fieldsMap);
    }
  });
}(jQuery));
