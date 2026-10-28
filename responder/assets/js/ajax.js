(function ($) {
  var cachedResponses = [],
      callsStack = [];

  $.RMP_AJAX = function (actionName, requestData, dontCache) {
    var actionMethod = 'POST',
        cachedData = {},
        cacheKey = generateCacheKey(actionMethod, actionName, requestData),
        ajaxConfig = {
          url: RMP_AJAX_LOCALS.ajaxUrl,
          type: actionMethod,
          data: _.extend({}, {
            action: 'RMP_' + actionName,
            _nonce: RMP_AJAX_LOCALS._nonce
          }, requestData)
        };

    if (isResponseCached(cacheKey) && !dontCache) {
      cachedData = cachedResponses[cacheKey];

      return $.Deferred()[cachedData['type']](cachedData['data']).promise();
    }

    // PR1-I28: calls that read a system's data reject (instead of resolving with the error object) when the
    // system is not connected, so callers do not render the error as a list; .fail gets
    // (xhr, 'rmp_system_not_connected', { code, system, message }).
    if (_.contains(SYSTEM_ACTIONS, actionName)) {
      var deferred = $.Deferred();

      $.ajax(ajaxConfig)
        .done(function(responseData, textStatus, xhr) {
          if (isSystemNotConnected(responseData)) {
            deferred.reject(xhr, NOT_CONNECTED_CODE, responseData.data);
            return;
          }

          cacheResponse(cacheKey, { 'data': responseData, 'type': 'resolve' });
          deferred.resolve(responseData, textStatus, xhr);
        })
        .fail(function(xhr, textStatus, errorThrown) {
          deferred.reject(xhr, textStatus, errorThrown);
        });

      return deferred.promise();
    }

    return $.ajax(ajaxConfig).always(function(responseData, textStatus) {
      var cacheData = {
        'data': responseData,
        'type': 'resolve'
      };

      if (textStatus === 'error') {
        cacheData['type'] = 'reject';
      }

      return cacheResponse.apply(null, [cacheKey, cacheData]);
    });
  };

  var NOT_CONNECTED_CODE = 'rmp_system_not_connected',
      SYSTEM_ACTIONS     = ['getListsBySystemName', 'getPersonalFieldsByListId', 'getSubscribersTags', 'createSubscribersTag'];

  function isSystemNotConnected(responseData) {
    return !!responseData && responseData.success === false && !!responseData.data && responseData.data.code === NOT_CONNECTED_CODE;
  }

  $.RMP_AJAX.isSystemNotConnected = isSystemNotConnected;

  // PR1-I28: a red inline message right after the field that could not be loaded (once per field).
  $.RMP_AJAX.showNotConnected = function($field, status, errorData) {
    var message = errorData && errorData.message,
        $anchor = $field.next('.select2').length ? $field.next('.select2') : $field;

    if (status !== NOT_CONNECTED_CODE || !message) {
      return false;
    }

    $anchor.nextAll('.rmp-system-not-connected').remove();
    $('<p class="rmp-system-not-connected notice notice-error inline"></p>').text(message).insertAfter($anchor);

    return true;
  };

  $.RMP_AJAX.hideNotConnected = function($field) {
    var $anchor = $field.next('.select2').length ? $field.next('.select2') : $field;

    $anchor.nextAll('.rmp-system-not-connected').remove();
  };

  function generateCacheKey(actionMethod, actionName, requestData) {
    return actionMethod + '_' + actionName + '_' + JSON.stringify(requestData);
  }

  function isResponseCached(cacheKey) {
    return ! _.isUndefined(cachedResponses[cacheKey]);
  }

  function cacheResponse(cacheKey, responseData) {
    if (! isResponseCached(cacheKey)) {
      cachedResponses[cacheKey] = responseData;
    }

    return responseData;
  }

})(jQuery);
