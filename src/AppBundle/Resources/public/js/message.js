var mcoMessage = (function() {
    var node;

    var init = function(displayNode) {
        node = displayNode;
    }

    // Display success message
    var success = function(message) {
        $(node).html('<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + message + '</div>');
    }

    // Display error message
    var error = function(message) {
        $(node).html('<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' + message + '</div>');
    }

    // Handle fail ajax response
    var fail = function(jqXHR, destNode) {
        if (503 == jqXHR.status) {
            return error('An error has occured, please try again later');
        }
        else if (410 == jqXHR.status) {
            return error('An error has occured<br />Please <a href="" class="alert-link reload-page">reload</a> the page');
        }

        if (typeof jqXHR.responseJSON !== 'undefined') {
            if (jqXHR.responseJSON.hasOwnProperty('quota')) {
                if (undefined !== destNode) {
                    return $(destNode).html(jqXHR.responseJSON.quota);
                }
                else {
                    return $(node).html(jqXHR.responseJSON.quota);
                }
            }
            else if (jqXHR.responseJSON.hasOwnProperty('message')) {
                return error(jqXHR.responseJSON.message);
            }
        }

        return error('An error has occured, please try again later');
    }

    // Close message
    var close = function() {
        $(node).alert('close');
    }

    return {
        init: init,
        success: success,
        error: error,
        fail: fail,
        close: close,
    }
})();
