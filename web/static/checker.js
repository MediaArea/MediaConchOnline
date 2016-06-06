$(document).ready(function() {
    result = $('#result-table').DataTable({
        'order': [],
        'autoWidth': false,
        'fixedHeader': true,
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
        formValues = getDataFromForm($(this));

        // Check file size
        if ( window.File )
        {
            uploadFiles = $(this).find(':file');

            if (uploadFiles[0].files[0].size > humanSizeToBytes(uploadFiles.data('file-max-size'))) {
                errorMessage('The file is too big (max ' + uploadFiles.data('file-max-size')  + ')');
                return;
            }
        }

        $.ajax({
            type: $(this).attr('method'),
            url: Routing.generate('app_checker_checkerajaxform'),
            data: new FormData($(this)[0]),
            processData: false,
            contentType: false
        })
        .done(function (data) {
            if (data.success) {
                updateFileOrAddFile(data.filename, data.transactionId, formValues);
                successMessage('File added successfuly');
            };
        })
        .fail(function (jqXHR) {
            failResponse(jqXHR, 'file');
        })
    });

    // Online form
    $('#url form').on('submit', function (e) {
        e.preventDefault();
        formValues = getDataFromForm($(this));
        $.ajax({
            type: $(this).attr('method'),
            url: Routing.generate('app_checker_checkerajaxform'),
            data: new FormData($(this)[0]),
            processData: false,
            contentType: false
        })
        .done(function (data) {
            if (data.success) {
                updateFileOrAddFile(data.filename, data.transactionId, formValues);
                successMessage('File added successfuly');
            };
        })
        .fail(function (jqXHR) {
            failResponse(jqXHR, 'url');
        })
    });

    // Repository form
    $('#repository form').on('submit', function (e) {
        e.preventDefault();
        formValues = getDataFromForm($(this));
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
                    updateFileOrAddFile(value.filename, value.transactionId, formValues);
                };
            });
            successMessage('Files added successfuly');
        })
        .fail(function (jqXHR) {
            failResponse(jqXHR, 'repository');
        })
    });

    function getDataFromForm(form) {
        formValues = {policy:form.find('.policyList').val(),
            policyText:form.find('.policyList option:selected').text(),
            display:form.find('.displayList').val(),
            verbosity:form.find('.verbosityList').val()
        };

        return formValues;
    }

    function updateFileOrAddFile(fileName, fileId, formValues) {
        if (!result.$('tr.fileId-' + fileId).length) {
            addFile(fileName, fileId, formValues)
        }
        else {
            updateFile(fileId, formValues)
        }
    }

    function updateFile(fileId, formValues) {
        node = result.$('#result-' + fileId);

        // Update policy if it has changed
        if (node.data('policy') != formValues.policy && (2 == node.data('tool') || undefined == node.data('tool'))) {
            node.data('policy', formValues.policy);
            node.data('policyName', formValues.policyText);

            updatePolicyCell(fileId, node.data('policy'));
        }

        // Update display if it has changed
        if (node.data('display') != formValues.display && (2 == node.data('tool') || undefined == node.data('tool'))) {
            node.data('display', formValues.display);

            removeImplemModalIfExists(fileId);
            removePolicyModalIfExists(fileId);
        }

        // Update verbosity if it has changed
        if (node.data('verbosity') != formValues.verbosity && (2 == node.data('tool') || undefined == node.data('tool'))) {
            node.data('verbosity', formValues.verbosity);

            removeImplemModalIfExists(fileId);
        }
    }

    function addFile(fileName, fileId, formValues) {
        node = result.row.add( [ '<span title="' + fileName + '">' + truncateString(fileName.split('/').pop(), 28) + '</span>', '', '', '', '', '<span class="status-text">In queue</span><button type="button" class="btn btn-link result-close" title="Close result"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button><button type="button" class="btn btn-link hidden" title="Reload result"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></button>' ] ).draw(false).node();

        // Add id
        resultId = 'result-' + fileId;
        $(node).prop('id', resultId);
        $(node).addClass('fileId-' + fileId);
        $(node).data('fileId', fileId);

        // Add policy, display and verbosity
        $(node).data('policy', formValues.policy);
        $(node).data('policyName', formValues.policyText);
        $(node).data('display', formValues.display);
        $(node).data('verbosity', formValues.verbosity);

        // Change status class
        $(result.cell(node, 5).node()).addClass('info');

        // Close button
        $(node).find('.result-close').click(node, function (e) {
            result.row(e.data).remove().draw(false);

            // Remove close all button
            if (1 == $('table.checker-results tbody tr').length && $('table.checker-results tbody tr .dataTables_empty').length) {
                $('#checkerResultTitle .close').addClass('hidden');
                $('#checkerApplyAll').addClass('hidden');
            };
        });

        // Waiting loop for analyze
        (function theLoop(resultId, time, i) {
            setTimeout(function () {
                if (!$(result.cell('#' + resultId, 5).node()).hasClass('success')) {
                    $.get(Routing.generate('app_checker_checkerstatus', { id: fileId }), function (data) {
                        processCheckerStatusRequest(data, resultId, fileId)
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
            $('#checkerResultTitle .close').removeClass('hidden');
            $('#checkerApplyAll').removeClass('hidden');
        }
    };

    function processCheckerStatusRequest(data, resultId, fileId) {
        if (data.finish) {
            node = result.$('#' + resultId);
            // Report type
            node.data('tool', data.tool);

            // Implementation
            addSpinnerToCell(result.cell(node, 1));
            $.get(Routing.generate('app_checker_checkerreportstatus', { id: fileId, reportType: data.tool }), function(data) {
                implementationCell(data, resultId, fileId);
            });

            // Policy
            if (2 == data.tool) {
                if (node.data('policy')) {
                    addSpinnerToCell(result.cell(node, 2));
                    $.get(Routing.generate('app_checker_checkerpolicystatus', { id: fileId, policy: node.data('policy') }), function(data) {
                        policyCell(data, resultId, fileId)
                    });
                }
                else {
                    policyCellEmptyWithModal(resultId, fileId)
                }
            }
            else {
                policyCellEmptyWithoutModal(resultId)
            }

            // MediaInfo
            mediaInfoCell(resultId, fileId);

            // MediaTrace
            mediaTraceCell(resultId, fileId);

            // Status
            statusCell(resultId, fileId);

            //stop timer
            //i = 0;
        }
        else if (data.percent > 0) {
            $(result.cell('#' + resultId, 5).node()).find('.status-text').html('Analyzing <span class="spinner-status"></span>');
        }
    }

    function statusCell(resultId, fileId) {
        nodeStatus = $(result.cell('#' + resultId, 5).node());
        nodeStatus.removeClass().addClass('success');
        nodeStatus.find('.status-text').text('Analyzed');
    };

    function implementationCell(data, resultId, fileId) {
        nodeCell = result.$('#' + resultId);
        nodeImplem = $(result.cell(nodeCell, 1).node());
        if (data.valid) {
            nodeImplem.addClass('success');
            implemResultText = '<span class="glyphicon glyphicon-ok text-success" aria-hidden="true"></span> Valid'
        }
        else {
            nodeImplem.addClass('danger');
            implemResultText = '<span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span> Not valid';
        }

        result.cell(nodeCell, 1).data(implemResultText + '<p class="pull-right"><a href="#" data-toggle="modal" data-target="#modalConformance' + resultId + '" title="View implementation report"><span class="glyphicon glyphicon-eye-open implem-view" aria-hidden="true"></span></a><a href="#" class="implem-dld" data-target="#modalConformance' + resultId + '" title="Download implementation report"><span class="glyphicon glyphicon-download" aria-hidden="true"></span></a></p>');

        nodeImplem.find('.implem-view').on('click', function(e) {
            e.preventDefault();
            nodeModal = result.$('#' + resultId);
            if (!$('#modalConformance' + resultId).length) {
                $('.result-container').append(' \
                <div id="modalConformance' + resultId + '" \ class="modal fade"> \
                    <div class="modal-dialog modal-lg"> \
                        <div class="modal-content"> \
                            <div class="modal-header"> \
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button> \
                                <h4 class="modal-title">Implementation report</h4> \
                            </div> \
                            <div class="modal-header form-horizontal"> \
                                <div class="col-md-6"> \
                                    <div class="form-group"><label class="col-sm-2 control-label">Display</label><div class="col-sm-10"><select id="modalConformanceDisplay' + resultId + '"></select></div></div> \
                                </div> \
                                <div class="col-md-6"> \
                                    <div class="form-group"><label class="col-sm-3 control-label">Verbosity</label><div class="col-sm-9"><select id="modalConformanceVerbosity' + resultId + '"></select></div></div> \
                                </div> \
                            </div> \
                            <div class="modal-body"></div> \
                            <div class="modal-footer"> \
                                <button type="button" class="btn btn-primary implem-dld" data-target="#modalConformance' + resultId + '">Download implementation report</button> \
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button> \
                            </div> \
                        </div> \
                    </div> \
                </div>');

                addSpinnerToModal('#modalConformance' + resultId);
                $.get(Routing.generate('app_checker_checkerreport', { id: fileId, reportType: nodeModal.data('tool'),  displayName: 'html', display: nodeModal.data('display'), verbosity: nodeModal.data('verbosity')}), function(data) {
                    displayReport('#modalConformance' + resultId, data);
                });

                $('#modalConformance' + resultId + ' .implem-dld').on('click', function(e) {
                    e.preventDefault();
                    modalDisplay = $('#modalConformanceDisplay' + resultId).val();
                    modalVerbosity = $('#modalConformanceVerbosity' + resultId).val();
                    window.location = Routing.generate('app_checker_checkerdownloadreport', { id: fileId, reportType: nodeModal.data('tool'),  displayName: 'html', display: modalDisplay, verbosity: modalVerbosity});
                });

                // Update report when display is changed
                displayList = $('.tab-content .active .displayList').clone();
                displayList.attr('id', 'modalConformanceDisplay' + resultId);
                displayList.find("option[value = '" + nodeModal.data('display') + "']").attr('selected', 'selected');
                $('#modalConformanceDisplay' + resultId).replaceWith(displayList);
                $('#modalConformanceDisplay' + resultId).on('change', function(e) {
                    modalDisplay = $('#modalConformanceDisplay' + resultId).val();
                    modalVerbosity = $('#modalConformanceVerbosity' + resultId).val();
                    addSpinnerToModal('#modalConformance' + resultId);
                    $.get(Routing.generate('app_checker_checkerreport', { id: fileId, reportType: nodeModal.data('tool'),  displayName: 'html', display: modalDisplay, verbosity: modalVerbosity}), function(data) {
                        displayReport('#modalConformance' + resultId, data);
                    });
                });

                // Update report when verbosity is changed
                verbosityList = $('.tab-content .active .verbosityList').clone();
                verbosityList.attr('id', 'modalConformanceVerbosity' + resultId);
                verbosityList.find("option[value = '" + nodeModal.data('verbosity') + "']").attr('selected', 'selected');
                $('#modalConformanceVerbosity' + resultId).replaceWith(verbosityList);
                $('#modalConformanceVerbosity' + resultId).on('change', function(e) {
                    modalDisplay = $('#modalConformanceDisplay' + resultId).val();
                    modalVerbosity = $('#modalConformanceVerbosity' + resultId).val();
                    addSpinnerToModal('#modalConformance' + resultId);
                    $.get(Routing.generate('app_checker_checkerreport', { id: fileId, reportType: nodeModal.data('tool'),  displayName: 'html', display: modalDisplay, verbosity: modalVerbosity}), function(data) {
                        displayReport('#modalConformance' + resultId, data);
                    });
                });

                if (2 != nodeModal.data('tool')) {
                    $('#modalConformance' + resultId + ' .modal-header.form-horizontal').hide();
                }
            }
        });

        nodeImplem.find('.implem-dld').on('click', function(e) {
            e.preventDefault();
            nodeDld = result.$('#' + resultId);
            window.location = Routing.generate('app_checker_checkerdownloadreport', { id: fileId, reportType: nodeDld.data('tool'),  displayName: 'html', display: nodeDld.data('display'), verbosity: nodeDld.data('verbosity')});
        });
    }

    function policyCell(data, resultId, fileId) {
        nodeCell = result.$('#' + resultId);
        nodePolicy = $(result.cell(nodeCell, 2).node());
        policyResultText = '<span class="policyResult">';
        if (data.valid) {
            nodePolicy.removeClass().addClass('success');
            policyResultText += '<span class="glyphicon glyphicon-ok text-success" aria-hidden="true"></span> '
        }
        else {
            nodePolicy.removeClass().addClass('danger');
            policyResultText += '<span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span> ';
        }

        policyResultText += '<span title="' + nodeCell.data('policyName') + '">' + truncateString(nodeCell.data('policyName'), 16) + '</span>';
        policyResultText += '</span>';

        result.cell(nodeCell, 2).data(policyResultText + '<p class="pull-right"><a href="#" data-toggle="modal" data-target="#modalPolicy' + resultId + '" title="View policy report"><span class="glyphicon glyphicon-eye-open policy-view" aria-hidden="true"></span></a><a href="#" class="policy-dld" data-target="#modalPolicy' + resultId + '" title="Download policy report"><span class="glyphicon glyphicon-download" aria-hidden="true"></span></a></p>');

        policyModal(resultId, fileId);
    }

    function policyCellEmptyWithModal(resultId, fileId) {
        nodePolicy = $(result.cell('#' + resultId, 2).node());
        nodePolicy.removeClass().addClass('info');
        result.cell('#' + resultId, 2).data('<span class="policyResult">N/A</span><p class="pull-right"><a href="#" data-toggle="modal" data-target="#modalPolicy' + resultId + '" title="View policy report"><span class="glyphicon glyphicon-eye-open policy-view" aria-hidden="true"></span></a></p>');

        policyModal(resultId, fileId);
    }

    function policyCellEmptyWithoutModal(resultId) {
        nodePolicy = $(result.cell('#' + resultId, 2).node());
        nodePolicy.removeClass().addClass('info');
        result.cell('#' + resultId, 2).data('N/A');
    }

    function policyModal(resultId, fileId) {
        nodePolicy = $(result.cell('#' + resultId, 2).node());
        nodePolicy.find('.policy-view').on('click', function(e) {
            e.preventDefault();
            nodeModal = result.$('#' + resultId);
            if (!$('#modalPolicy' + resultId).length) {
                $('.result-container').append(' \
                <div id="modalPolicy' + resultId + '" \ class="modal fade"> \
                    <div class="modal-dialog modal-lg"> \
                        <div class="modal-content"> \
                            <div class="modal-header"> \
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button> \
                                <h4 class="modal-title">Policy report</h4> \
                            </div> \
                            <div class="modal-header form-horizontal"> \
                                <div class="col-md-6"> \
                                    <div class="form-group"><label class="col-sm-2 control-label">Policy</label><div class="col-sm-10"><select id="modalPolicyPolicy' + resultId + '"></select></div></div> \
                                </div> \
                                <div class="col-md-6"> \
                                    <div class="form-group"><label class="col-sm-2 control-label">Display</label><div class="col-sm-10"><select id="modalPolicyDisplay' + resultId + '"></select></div></div> \
                                </div> \
                            </div> \
                            <div class="modal-body"></div> \
                            <div class="modal-footer"> \
                                <button type="button" class="btn btn-primary policy-dld" data-target="#modalPolicy' + resultId + '">Download policy report</button> \
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button> \
                            </div> \
                        </div> \
                    </div> \
                </div>');

                if (nodeModal.data('policy')) {
                    addSpinnerToModal('#modalPolicy' + resultId);
                    $.get(Routing.generate('app_checker_checkerreport', { id: fileId, reportType: 'policy',  displayName: 'html', policy: nodeModal.data('policy'), display: nodeModal.data('display')}), function(data) {
                        displayReport('#modalPolicy' + resultId, data);
                    });
                }

                $('#modalPolicy' + resultId + ' .policy-dld').on('click', function(e) {
                    e.preventDefault();
                    modalDisplay = $('#modalPolicyDisplay' + resultId).val();
                    modalPolicy = $('#modalPolicyPolicy' + resultId).val();
                    if (modalPolicy) {
                        window.location = Routing.generate('app_checker_checkerdownloadreport', { id: fileId, reportType: 'policy',  displayName: 'html', policy: modalPolicy, display: modalDisplay});
                    }
                });

                // Update report when display is changed
                displayList = $('.tab-content .active .displayList').clone();
                displayList.attr('id', 'modalPolicyDisplay' + resultId);
                displayList.find("option[value = '" + nodeModal.data('display') + "']").attr('selected', 'selected');
                $('#modalPolicyDisplay' + resultId).replaceWith(displayList);
                $('#modalPolicyDisplay' + resultId).on('change', function(e) {
                    modalDisplay = $('#modalPolicyDisplay' + resultId).val();
                    modalPolicy = $('#modalPolicyPolicy' + resultId).val();
                    if (modalPolicy) {
                        addSpinnerToModal('#modalPolicy' + resultId);
                        $.get(Routing.generate('app_checker_checkerreport', { id: fileId, reportType: 'policy',  displayName: 'html', policy: modalPolicy, display: modalDisplay}), function(data) {
                        displayReport('#modalPolicy' + resultId, data);
                        });
                    }
                    else {
                        $('#modalPolicy' + resultId + ' .modal-body').empty('');
                    }
                });

                // Update report when policy is changed
                policyList = $('.tab-content .active .policyList').clone();
                policyList.attr('id', 'modalPolicyPolicy' + resultId);
                policyList.find("option[value = '" + nodeModal.data('policy') + "']").attr('selected', 'selected');
                $('#modalPolicyPolicy' + resultId).replaceWith(policyList);
                $('#modalPolicyPolicy' + resultId).on('change', function(e) {
                    modalDisplay = $('#modalPolicyDisplay' + resultId).val();
                    modalPolicy = $('#modalPolicyPolicy' + resultId).val();
                    if (modalPolicy) {
                        addSpinnerToModal('#modalPolicy' + resultId);
                        $.get(Routing.generate('app_checker_checkerreport', { id: fileId, reportType: 'policy',  displayName: 'html', policy: modalPolicy, display: modalDisplay}), function(data) {
                            displayReport('#modalPolicy' + resultId, data);
                        });
                    }
                    else {
                        $('#modalPolicy' + resultId + ' .modal-body').empty('');
                    }
                });
            }
        });

        nodePolicy.find('.policy-dld').on('click', function(e) {
            e.preventDefault();
            nodeDld = result.$('#' + resultId);
            window.location = Routing.generate('app_checker_checkerdownloadreport', { id: fileId, reportType: 'policy',  displayName: 'html', policy: nodeDld.data('policy'), display: nodeDld.data('display')});
        });
    }

    function removePolicyModalIfExists(fileId) {
        if ($('#modalPolicyresult-' + fileId).length) {
            $('#modalPolicyresult-' + fileId).remove();
        }
    }

    function removeImplemModalIfExists(fileId) {
        if ($('#modalConformanceresult-' + fileId).length) {
            $('#modalConformanceresult-' + fileId).remove();
        }
    }

    function updatePolicyCell(fileId, policyId) {
        removePolicyModalIfExists(fileId);

        // Update cell if analysis of file is succeeded
        if ($(result.cell('#result-' + fileId, 5).node()).hasClass('success')) {
            if (policyId) {
                resetPolicyCell(fileId);
                addSpinnerToCell(result.cell('#result-' + fileId, 2));
                $.get(Routing.generate('app_checker_checkerpolicystatus', { id: fileId, policy: policyId }), function (data) {
                    policyCell(data, 'result-' + fileId, fileId);
                });
            }
            else {
                policyCellEmptyWithModal('result-' + fileId, fileId)
            }
        }
    }

    function resetPolicyCell(fileId) {
        $(result.cell('#result-' + fileId, 2).node()).removeClass();
        $(result.cell('#result-' + fileId, 2).node()).empty();
    }

    function mediaInfoCell(resultId, fileId) {
        nodeMI = $(result.cell('#' + resultId, 3).node());
        nodeMI.addClass('text-center');
        result.cell('#' + resultId, 3).data('<a href="#" class="mi-view" data-toggle="modal" data-target="#modalInfo' + resultId + '" title="View MediaInfo report"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></a><a href="#" class="mi-dld" data-target="#infoXml' + resultId + '" title="Download MediaInfo report"><span class="glyphicon glyphicon-download" aria-hidden="true"></span></a>');

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
                                <button type="button" class="btn btn-warning mi-create-report">Create policy from MediaInfo report</button> \
                                <button type="button" class="btn btn-primary mi-dld">Download MediaInfo report</button> \
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

                $('#modalInfo' + resultId + ' .mi-create-report').on('click', function(e) {
                    e.preventDefault();
                    $.get(Routing.generate('app_checker_checkercreatepolicy', { id: fileId }), function (data) {
                        mediaInfoCreatePolicy(data, 'result-' + fileId, fileId);
                    });
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

    function mediaInfoCreatePolicy(createPolicy, resultId, fileId) {
        if (createPolicy.result) {
            $('#modalInfo' + resultId + ' .mi-create-report').fadeOut(200).replaceWith('<div class="alert alert-success alert-modal-create-policy" role="alert"><span class="glyphicon glyphicon-ok text-success" aria-hidden="true"></span> <a href="' + Routing.generate('app_xslpolicy_xslpolicyruleedit', { id: createPolicy.policyId }) + '" target="_blank" title="View the new policy" class="alert-link">Policy</a> successfuly created</div>');

            // Add new policy to all select lists
            $('.policyList').each(function () {
                if ('User policies' == $(this).children('optgroup:first').attr('label')) {
                    $(this).children('optgroup:first').append('<option value="' + createPolicy.policyId + '">' + createPolicy.policyName + '</option>');
                }
                else {
                    $(this).append('<optgroup label="User policies"><option value="' + createPolicy.policyId + '">' + createPolicy.policyName + '</option></optgroup>')
                }
            });
        }
        else {
            $('#modalInfo' + resultId + ' .mi-create-report').fadeOut(200).replaceWith('<div class="alert alert-danger alert-modal-create-policy" role="alert"><span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span> Error policy not created</div>');
        }
    }

    function mediaTraceCell(resultId, fileId) {
        nodeMT = $(result.cell('#' + resultId, 4).node());
        nodeMT.addClass('text-center');
        result.cell('#' + resultId, 4).data('<a href="#" class="mt-view" data-toggle="modal" data-target="#modalTrace' + resultId + '" title="View MediaTrace report"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></a><a href="#" class="mt-dld" data-target="#traceXml' + resultId + '" title="Download MediaTrace report"><span class="glyphicon glyphicon-download" aria-hidden="true"></span></a>');

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
                                <button type="button" class="btn btn-primary mt-dld" data-target="#modalTrace' + resultId + '">Download MediaTrace report</button> \
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

    // Apply policy to all
    $('#checkerApplyAll').html('<div class="applyAll form-horizontal"></div>');
    $('#checkerApplyAll').addClass('tab-pane');

    // Duplicate policy list
    policyList = $('.tab-content .active .policyList').clone();
    policyList.attr('id', 'applyAllPolicy');
    policyList.children('option:first').text('Choose a new policy to apply');
    $('#checkerApplyAll div.applyAll').append('<div class="col-md-12"><div class="form-group"><label class="pull-left control-label">Apply a policy to all results</label><div class="col-sm-4 policy">')
    $('#checkerApplyAll div.applyAll div.policy').html(policyList);
    resetSelectList('applyAllPolicy');

    $('#applyAllPolicy').on('change', function(e) {
        applyPolictyToAll();
        resetSelectList('applyAllPolicy');
    });

    function applyPolictyToAll() {
        result.$('tr').each(function () {
            node = result.$('#' + $(this).prop('id'));

            if (2 == node.data('tool') || undefined == node.data('tool')) {
                if (node.data('policy') != $('#applyAllPolicy').val()) {
                    // Update policy
                    node.data('policy', $('#applyAllPolicy').val());
                    node.data('policyName', $('#applyAllPolicy option:selected').text());

                    updatePolicyCell(node.data('fileId'), node.data('policy'));
                }
            }
        });
    }

    function resetSelectList(listId) {
        $('#' + listId + ' option').removeAttr('selected');
    }

    function addSpinnerToCell(cell) {
        cell.data('<span class="spinner-cell"></span>');
    }

    function addSpinnerToModal(modal) {
        $(modal + ' .modal-body').html('<span class="spinner-modal"></span>');
    }

    function truncateString(str, length) {
        return str.length > length ? str.substring(0, length) + '&hellip;' : str
    }

    // Display report in the modal
    function displayReport(elemId, dataReport) {
        if (dataReport.isHtmlReport) {
            $(elemId + ' .modal-body').html(dataReport.report);
        }
        else {
            $(elemId + ' .modal-body').html('<pre class="report-content">');
            $(elemId + ' .report-content').text(dataReport.report);
        }
    }

    // Remove all results blocks
    $('#checkerResultTitle .close').click(function () {
        result.clear().draw();

        // Remove close all button
        $(this).addClass('hidden');

        // Remove apply to all
        $('#checkerApplyAll').addClass('hidden');
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

    // Display success message
    function successMessage(message) {
        $('#checkerInfo div').replaceWith('<div class="alert alert-success">' + message + '</div>');
        $('#checkerInfo div').delay(5000).fadeOut();
    }

    // Display error message
    function errorMessage(message) {
        $('#checkerInfo div').replaceWith('<div class="alert alert-danger">' + message + '</div>')
        $('#checkerInfo div').delay(10000).fadeOut();
    }

    // Handle fail ajax response
    function failResponse(jqXHR, formType) {
        if (typeof jqXHR.responseJSON !== 'undefined') {
            if (jqXHR.responseJSON.hasOwnProperty('quota')) {
                $('#' + formType).html(jqXHR.responseJSON.quota);
            }
            else if ('file' == formType) {
                uploadFiles = $('#file form :file');
                errorMessage('The file is too big (max ' + uploadFiles.data('file-max-size')  + ')');
            }
            else if (jqXHR.responseJSON.hasOwnProperty('message')) {
                errorMessage(jqXHR.responseJSON.message);
            }
            else {
                errorMessage('An error has occured, please try again later');
            }
        }
        else {
            if ('file' == formType && 400 == jqXHR.status) {
                uploadFiles = $('#file form :file');
                errorMessage('The file is too big (max ' + uploadFiles.data('file-max-size')  + ')');
            }
            else {
                errorMessage('An error has occured, please try again later');
            }
        }
    }

    // Convert human readable size to bytes
    function humanSizeToBytes(size) {
        var powers = {'k': 1, 'm': 2, 'g': 3, 't': 4};
        var regex = /(\d+(?:\.\d+)?)\s?(k|m|g|t)?b?/i;

        var res = regex.exec(size);

        if (res[2] !== undefined) {
            return res[1] * Math.pow(1024, powers[res[2].toLowerCase()]);
        }
        else {
            return size;
        }
    }
});
