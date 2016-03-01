$(document).ready(function() {
    nbResults = 0;
    result = $('#result-table').DataTable({
        'order': [],
        'autoWidth': false,
        'columnDefs': [
            { 'orderable': true, targets: 0 },
            { 'orderable': true, 'searchable': false, targets: [1, 2, 5] },
            { 'orderable': false, 'searchable': false, 'width': '10%', targets: [3, 4] },
            { 'width': '15%', targets: [1, 5] },
            { 'width': '25%', targets: [0, 2] },
        ]
    });

    // Upload form
    $('#file form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            type: $(this).attr('method'),
                url: Routing.generate('app_checker_checkerajaxform'),
                data: new FormData($(this)[0]),
                processData: false,
                contentType: false
        })
        .done(function (data) {
            if (data.success) {
                addFile(data.filename, data.transactionId);
            };
        })
    });

    // Online form
    $('#url form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            type: $(this).attr('method'),
                url: Routing.generate('app_checker_checkerajaxform'),
                data: new FormData($(this)[0]),
                processData: false,
                contentType: false
        })
        .done(function (data) {
            if (data.success) {
                addFile(data.filename, data.transactionId);
            };
        })
    });

    // Repository form
    $('#repository form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            type: $(this).attr('method'),
                url: Routing.generate('app_checker_checkerajaxform'),
                data: new FormData($(this)[0]),
                processData: false,
                contentType: false
        })
        .done(function (data) {
            $.each(data, function( index, value ) {
                if (value.success) {
                    addFile(value.filename, value.transactionId);
                };
            });
        })
    });

    function addFile(fileName, fileId) {
        if (findDuplicateRow(fileId)) {
            return;
        }

        node = result.row.add( [ '<span title="' + fileName + '">' + truncateString(fileName, 35) + '</span>', '', '', '', '', '<span class="status-text">In queue</span><button type="button" class="btn btn-link result-close" title="Close result"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button><button type="button" class="btn btn-link hidden" title="Reload result"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></button>' ] ).draw(false).node();

        // Add id
        resultId = 'result-' + nbResults++;
        $(node).prop('id', resultId);
        $(node).addClass('fileId-' + fileId);

        // Add policy and display
        $(node).data('policy', $('.tab-content .active .policyList').val());
        $(node).data('policyName', $('.tab-content .active .policyList option:selected').text());
        $(node).data('display', $('.tab-content .active .displayList').val());

        // Change status class
        $(result.cell(node, 5).node()).addClass('info');

        // Close button
        result.$('#' + resultId).find('.result-close').click(node, function (e) {
            result.row(e.data).remove().draw(false);

            // Remove close all button
            if (1 == $('table.checker-results tbody tr').length && $('table.checker-results tbody tr .dataTables_empty').length) {
                $('#checkerResultTitle .close').addClass('hidden');
            };
        });

        // Waiting loop for analyze
        (function theLoop(resultId, time, i) {
            setTimeout(function () {
                if (!$(result.cell('#' + resultId, 5).node()).hasClass('success')) {
                    $.get(Routing.generate('app_checker_checkerstatus', { id: fileId }), function(data) {
                        if (data.finish) {
                            // Status
                            statusCell(resultId, fileId);

                            // Implementation
                            implementationCell(resultId, fileId);

                            // Policy
                            policyCell(resultId, fileId);


                            // MediaInfo
                            mediaInfoCell(resultId, fileId);

                            // MediaTrace
                            mediaTraceCell(resultId, fileId);

                            //stop timer
                            //i = 0;
                        }
                        else if (data.percent > 0) {
                            $(result.cell('#' + resultId, 5).node()).find('.status-text').text('Analyzing');
                        }
                    });
                }
                // If i > 0, keep going
                if ($(result.cell('#' + resultId, 5).node()).hasClass('info') && --i > 0) {
                    // Call the loop again
                    theLoop(resultId, time * 2, i);
                }
            }, time);
        })(resultId, 100, 15);

        if ($('#checkerResultTitle .close').hasClass('hidden')) {
            $('#checkerResultTitle .close').removeClass('hidden')
        }
    };

    function statusCell(resultId, fileId) {
        nodeStatus = $(result.cell('#' + resultId, 5).node());
        nodeStatus.removeClass().addClass('success');
        nodeStatus.find('.status-text').text('Analyzed');
    };

    function implementationCell(resultId, fileId) {
        $.get(Routing.generate('app_checker_checkerimplemstatus', { id: fileId }), function(data) {
            nodeImplem = $(result.cell('#' + resultId, 1).node());
            if (data.valid) {
                nodeImplem.addClass('success');
                implemResultText = '<span class="glyphicon glyphicon-ok text-success" aria-hidden="true"></span> Valid'
            }
            else {
                nodeImplem.addClass('danger');
                implemResultText = '<span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span> Not valid';
            }

            result.cell('#' + resultId, 1).data(implemResultText + '<p class="pull-right"><a href="#" data-toggle="modal" data-target="#modalConformance' + resultId + '" title="View implementation report"><span class="glyphicon glyphicon-eye-open implem-view" aria-hidden="true"></span></a><a href="#" class="implem-dld" data-target="#modalConformance' + resultId + '" data-save-name="' + resultId + '_ImplementationReport.txt" title="Download implementation report"><span class="glyphicon glyphicon-download" aria-hidden="true"></span></a></p>');

            nodeImplem.find('.implem-view').on('click', function(e) {
                e.preventDefault();
                if (!$('#modalConformance' + resultId).length) {
                    $('.result-container').append(' \
                    <div id="modalConformance' + resultId + '" \ class="modal fade"> \
                        <div class="modal-dialog modal-lg"> \
                            <div class="modal-content"> \
                                <div class="modal-header"> \
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button> \
                                    <h4 class="modal-title">Implementation report</h4> \
                                </div> \
                                <div class="modal-body"></div> \
                                <div class="modal-footer"> \
                                    <button type="button" class="btn btn-primary implem-dld" data-target="#modalConformance' + resultId + '" data-save-name="' + resultId + '_ImplementationReport.txt">Download implementation report</button> \
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button> \
                                </div> \
                            </div> \
                        </div> \
                    </div>');

                    $.get(Routing.generate('app_checker_checkerreport', { id: fileId, reportType: 'implem',  displayName: 'html', display: $(node).data('display')}), function(data) {
                        $('#modalConformance' + resultId + ' .modal-body').html(data.report);
                    });

                    $('#modalConformance' + resultId + ' .implem-dld').on('click', function(e) {
                        e.preventDefault();
                        window.location = Routing.generate('app_checker_checkerdownloadreport', { id: fileId, reportType: 'implem',  displayName: 'html'});
                    });
                }
            });

            nodeImplem.find('.implem-dld').on('click', function(e) {
                e.preventDefault();
                window.location = Routing.generate('app_checker_checkerdownloadreport', { id: fileId, reportType: 'implem',  displayName: 'html'});
            });

        });
    }

    function policyCell(resultId, fileId) {
        if ($(node).data('policy')) {
            $.get(Routing.generate('app_checker_checkerpolicystatus', { id: fileId, policy: $(node).data('policy') }), function(data) {
                nodePolicy = $(result.cell('#' + resultId, 2).node());
                if (data.valid) {
                    nodePolicy.addClass('success');
                    policyResultText = '<span class="glyphicon glyphicon-ok text-success" aria-hidden="true"></span> '
                }
                else {
                    nodePolicy.addClass('danger');
                    policyResultText = '<span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span> ';
                }
                policyResultText += '<span title="' + $(node).data('policyName') + '">' + truncateString($(node).data('policyName'), 25) + '</span>';

                result.cell('#' + resultId, 2).data(policyResultText + '<p class="pull-right"><a href="#" data-toggle="modal" data-target="#modalPolicy' + resultId + '" title="View policy report"><span class="glyphicon glyphicon-eye-open policy-view" aria-hidden="true"></span></a><a href="#" class="policy-dld" data-target="#modalPolicy' + resultId + '" data-save-name="' + resultId + '_PolicyReport.txt" title="Download policy report"><span class="glyphicon glyphicon-download" aria-hidden="true"></span></a></p>');

                policyModal(resultId, fileId, nodePolicy);
            });
        }
        else {
            nodePolicy = $(result.cell('#' + resultId, 2).node());
            nodePolicy.addClass('info');
            result.cell('#' + resultId, 2).data('N/A');
        }
    }

    function policyModal(resultId, fileId, nodePolicy) {
        nodePolicy.find('.policy-view').on('click', function(e) {
            e.preventDefault();
            if (!$('#modalPolicy' + resultId).length) {
                $('.result-container').append(' \
                <div id="modalPolicy' + resultId + '" \ class="modal fade"> \
                    <div class="modal-dialog modal-lg"> \
                        <div class="modal-content"> \
                            <div class="modal-header"> \
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button> \
                                <h4 class="modal-title">Policy report</h4> \
                            </div> \
                            <div class="modal-body"></div> \
                            <div class="modal-footer"> \
                                <button type="button" class="btn btn-primary policy-dld" data-target="#modalPolicy' + resultId + '" data-save-name="' + resultId + '_PolicyReport.txt">Download policy report</button> \
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button> \
                            </div> \
                        </div> \
                    </div> \
                </div>');

                if ($(node).data('policy')) {
                    $.get(Routing.generate('app_checker_checkerreport', { id: fileId, reportType: 'policy',  displayName: 'html', policy: $(node).data('policy'), display: $(node).data('display')}), function(data) {
                        $('#modalPolicy' + resultId + ' .modal-body').html(data.report);
                    });
                }

                $('#modalPolicy' + resultId + ' .policy-dld').on('click', function(e) {
                    e.preventDefault();
                    window.location = Routing.generate('app_checker_checkerdownloadreport', { id: fileId, reportType: 'policy',  displayName: 'html', policy: $(node).data('policy'), display: $(node).data('display')});
                });
            }
        });

        nodePolicy.find('.policy-dld').on('click', function(e) {
            e.preventDefault();
            window.location = Routing.generate('app_checker_checkerdownloadreport', { id: fileId, reportType: 'policy',  displayName: 'html', policy: $(node).data('policy'), display: $(node).data('display')});
        });
    }

    function mediaInfoCell(resultId, fileId) {
        nodeMI = $(result.cell('#' + resultId, 3).node());
        nodeMI.addClass('text-center');
        result.cell('#' + resultId, 3).data('<a href="#" class="mi-view" data-toggle="modal" data-target="#modalInfo' + resultId + '" title="View MediaInfo report"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></a><a href="#" class="mi-dld" data-target="#infoXml' + resultId + '" data-save-name="' + resultId + '_MediaInfo.xml" title="Download MediaInfo report"><span class="glyphicon glyphicon-download" aria-hidden="true"></span></a>');

        nodeMI.find('.mi-view').on('click', function(e) {
            e.preventDefault();
            if (!$('#modalInfo' + resultId).length) {
                $('.result-container').append(' \
                <div id="modalInfo' + resultId + '" \ class="modal fade"> \
                    <div class="modal-dialog modal-lg"> \
                        <div class="modal-content"> \
                        <div class="modal-header"> \
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button> \
                            <h4 class="modal-title">MediaInfo report</h4> \
                        </div> \
                        <div class="modal-body"> \
                            <div class="row"> \
                                <div class="col-md-6"> \
                                    <i class="glyphicon glyphicon-search"></i><input type="text" value="" class="jstreeSearch" id="infoSearch' + resultId + '" placeholder="Search" /> \
                                </div> \
                                <div class="col-md-12"> \
                                    <div id="info' + resultId + '"></div> \
                                </div> \
                            </div> \
                        </div> \
                            <div class="modal-footer"> \
                                <button type="button" class="btn btn-primary mi-dld" data-target="#modalInfo' + resultId + '" data-save-name="' + resultId + '_MediaInfoReport.txt">Download MediaInfo report</button> \
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button> \
                            </div> \
                        </div> \
                    </div> \
                </div>');

                mediaInfoTree(resultId, fileId);

                $('#modalInfo' + resultId + ' .mi-dld').on('click', function(e) {
                    e.preventDefault();
                    window.location = Routing.generate('app_checker_checkerdownloadreport', { id: fileId, reportType: 'mi',  displayName: 'xml'});
                });
            }
        });

        nodeMI.find('.mi-dld').on('click', function(e) {
            e.preventDefault();
            window.location = Routing.generate('app_checker_checkerdownloadreport', { id: fileId, reportType: 'mi',  displayName: 'xml'});
        });
    };

    function mediaInfoTree(resultId, fileId) {
        $('#info' + resultId).jstree({
            'core' : {
                'check_callback' : function (operation, node, parent, position, more) {
                    if (operation === 'copy_node' || operation === 'move_node') {
                        return false; // disable copy and move
                    }
                    else {
                        return true;
                    }
                },
                'multiple' : false,
                'dblclick_toggle' : false,
                'data' : {'url' : Routing.generate('app_checker_checkerreport', { id: fileId, reportType: 'mi',  displayName: 'jstree'}), 'dataType' : 'json'}
            },
            "plugins" : ['search', 'types', 'grid'],
            'types' : {
                'default' : {'icon' : 'glyphicon glyphicon-folder-open'},
                'block' : {'icon' : 'glyphicon glyphicon-folder-open'},
                'data' : {'icon' : 'glyphicon glyphicon-file'},
            },
            grid: {
                columns: [
                {header: "Key", tree: true},
                {header: "Value", value: "dataValue"},
                ],
                resizable: true,
            },
        });

        $('#info' + resultId).on('ready.jstree', function () {
            $(function () {
                var to = false;
                $('#infoSearch' + resultId).keyup(function () {
                    if(to) { clearTimeout(to); }
                    to = setTimeout(function () {
                        var v = $('#infoSearch' + resultId).val();
                        $('#info' + resultId).jstree(true).search(v);
                    }, 250);
                });
            });
        });

        $('#info' + resultId).on('loaded.jstree', function (e, data) {
            data.instance.open_all();
        });

        $('#info' + resultId).on('select_node.jstree', function (e, data) {
            data.instance.toggle_node(data.node);
        });
    }


    function mediaTraceCell(resultId, fileId) {
        nodeMT = $(result.cell('#' + resultId, 4).node());
        nodeMT.addClass('text-center');
        result.cell('#' + resultId, 4).data('<a href="#" class="mt-view" data-toggle="modal" data-target="#modalTrace' + resultId + '" title="View MediaTrace report"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></a><a href="#" class="mt-dld" data-target="#traceXml' + resultId + '" data-save-name="' + resultId + '_MediaTrace.xml" title="Download MediaTrace report"><span class="glyphicon glyphicon-download" aria-hidden="true"></span></a>');

        nodeMT.find('.mt-view').on('click', function(e) {
            e.preventDefault();
            if (!$('#modalTrace' + resultId).length) {
                $('.result-container').append(' \
                <div id="modalTrace' + resultId + '" \ class="modal fade"> \
                    <div class="modal-dialog modal-lg"> \
                        <div class="modal-content"> \
                            <div class="modal-header"> \
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button> \
                                <h4 class="modal-title">MediaTrace report</h4> \
                            </div> \
                            <div class="modal-body"> \
                                <div class="row"> \
                                    <div class="col-md-6"> \
                                        <i class="glyphicon glyphicon-search"></i><input type="text" value="" class="jstreeSearch" id="traceSearch' + resultId + '" placeholder="Search" /> \
                                    </div> \
                                    <div class="col-md-12"> \
                                        <div id="trace' + resultId + '"></div> \
                                    </div> \
                                </div> \
                            </div> \
                            <div class="modal-footer"> \
                                <button type="button" class="btn btn-primary mt-dld" data-target="#modalTrace' + resultId + '" data-save-name="' + resultId + '_MediaTraceReport.txt">Download MediaTrace report</button> \
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button> \
                            </div> \
                        </div> \
                    </div> \
                </div>');

                mediaTraceTree(resultId, fileId);

                $('#modalTrace' + resultId + ' .mt-dld').on('click', function(e) {
                    e.preventDefault();
                    window.location = Routing.generate('app_checker_checkerdownloadreport', { id: fileId, reportType: 'mt',  displayName: 'xml'});
                });
            }
        });

        nodeMT.find('.mt-dld').on('click', function(e) {
            e.preventDefault();
            window.location = Routing.generate('app_checker_checkerdownloadreport', { id: fileId, reportType: 'mt',  displayName: 'xml'});
        });
    };

    function mediaTraceTree(resultId, fileId) {
        $('#trace' + resultId).jstree({
            'core' : {
                'check_callback' : function (operation, node, parent, position, more) {
                    if (operation === 'copy_node' || operation === 'move_node') {
                        return false; // disable copy and move
                    }
                    else {
                        return true;
                    }
                },
                'multiple' : false,
                'dblclick_toggle' : false,
                'data' : {'url' : Routing.generate('app_checker_checkerreport', { id: fileId, reportType: 'mt',  displayName: 'jstree'}), 'dataType' : 'json'}
            },
            "plugins" : ['search', 'types', 'grid'],
            'types' : {
                'default' : {'icon' : 'glyphicon glyphicon-folder-open'},
                'block' : {'icon' : 'glyphicon glyphicon-folder-open'},
                'data' : {'icon' : 'glyphicon glyphicon-file'},
            },
            grid: {
                columns: [
                {header: "Offset", value: "offset"},
                {header: "Key", tree: true},
                {header: "Value", value: "dataValue"},
                ],
                resizable: true,
            },
        });

        $('#trace' + resultId).on('ready.jstree', function () {
            $(function () {
                var to = false;
                $('#traceSearch' + resultId).keyup(function () {
                    if(to) { clearTimeout(to); }
                    to = setTimeout(function () {
                        var v = $('#traceSearch' + resultId).val();
                        $('#trace' + resultId).jstree(true).search(v);
                    }, 250);
                });
            });
        });

        $('#trace' + resultId).on('loaded.jstree', function (e, data) {
            data.instance.get_container().find('li').each(function () {
                data.instance.open_node($(this));
            })
        });

        $('#trace' + resultId).on('select_node.jstree', function (e, data) {
            data.instance.toggle_node(data.node);
        });
    }

    function truncateString(str, length) {
        return str.length > length ? str.substring(0, length) + '&hellip;' : str
    }


    function findDuplicateRow(fileId) {
        findRows = 0;

        result.$('tr.fileId-' + fileId).each(function () {
            if ($(this).data('policy') == $('.tab-content .active .policyList').val()) {
                findRows++;
            }
        });

        return findRows;
    }

    // Remove all results blocks
    $('#checkerResultTitle .close').click(function () {
        result.clear().draw();

        // Remove close all button
        $(this).addClass('hidden');
    });

    // Alert user when they leave checker page with results
    $('.nav.navbar a').each(function() {
        // Exclude some links
        if (!$(this).hasClass('no-close-alert')) {
            $(this).click(function (e) {
                // Check if there is close all button
                if (0 < $('table.checker-results tbody tr').length && !$('table.checker-results tbody tr .dataTables_empty').length) {
                    choice = confirm('Are you sure ?\nAll analysis results will be discarded!');
                    if (choice == false) {
                        e.preventDefault();
                    }
                };
            });
        };
    });
});
