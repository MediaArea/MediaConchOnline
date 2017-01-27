var statusCell = (function() {
    var result;
    var init = function(table) {
        result = table;
    };

    var success = function(fileId) {
        var nodeStatus = $(result.cell('#result-' + fileId, 5).node());
        nodeStatus.removeClass('info danger checkInProgress').addClass('success');
        nodeStatus.find('.status-text').html('<span class="glyphicon glyphicon-ok text-success" aria-hidden="true"></span> Analyzed');
        nodeStatus.find('.result-reload').removeClass('hidden');
    };

    var inProgress = function(fileId, status) {
        var nodeStatus = $(result.cell('#result-' + fileId, 5).node());
        nodeStatus.addClass('checkInProgress');
        if (undefined == status.tool || 2 != status.tool || 100 == status.percent) {
            nodeStatus.find('.status-text').html('<span class="spinner-status"></span>');
        }
        else {
            nodeStatus.find('.status-text').html('<span class="spinner-status"></span>&nbsp;' + Math.round(status.percent) + '%');
        }
    };

    var error = function(fileId) {
        var nodeStatus = $(result.cell('#result-' + fileId, 5).node());
        nodeStatus.removeClass('info danger checkInProgress').addClass('danger');
        nodeStatus.find('.status-text').html('<span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span> Error');
        nodeStatus.find('.result-reload').removeClass('hidden');
    };

    var reset = function(fileId) {
        var nodeStatus = $(result.cell('#result-' + fileId, 5).node());
        nodeStatus.removeClass().addClass('statusCell info');
        nodeStatus.find('.status-text').html('In queue');
        nodeStatus.find('.result-reload').addClass('hidden');
    };

    return {
        init: init,
        success: success,
        inProgress: inProgress,
        error: error,
        reset: reset,
    };
})();
