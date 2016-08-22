var policyTree = (function() {
    var instance;

    var init = function() {
        $('#policiesTree').jstree({
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
                'dblclick_toggle' : true,
            },
            "plugins" : ['search', 'types'],
            'types' : {
                'default' : {'icon' : 'glyphicon glyphicon-folder-open'},
                'a' : {'icon' : 'glyphicon glyphicon-folder-open'},
                'u' : {'icon' : 'glyphicon glyphicon-folder-open'},
                's' : {'icon' : 'glyphicon glyphicon-folder-open'},
                'up' : {'icon' : 'glyphicon glyphicon-folder-open'},
                'sp' : {'icon' : 'glyphicon glyphicon-folder-open'},
                'r' : {'icon' : 'glyphicon glyphicon-file'},
            },
        });

        instance = $('#policiesTree').jstree(true);
        searchBinding();
        rightPanelBinding();
    }

    // Search
    var searchBinding = function() {
        instance.get_container().on('ready.jstree', function () {
            var to = false;
            $('#policiesTreeSearch').keyup(function () {
                if(to) { clearTimeout(to); }
                to = setTimeout(function () {
                    var v = $('#policiesTreeSearch').val();
                    instance.search(v);
                }, 250);
            });
        });
    }

    // Right panel update when a node is selected
    var rightPanelBinding = function () {
        instance.get_container().on('select_node.jstree', function (e, data) {
            instance.open_node(data.node);
            mcoMessage.close();
            switch (data.node.type) {
                case 'r':
                    if ('u' == instance.get_node(data.node.parent).type) {
                        policyTreeRules.display(data.node, false);
                    }
                    else {
                        policyTreeRules.display(data.node, true);
                    }
                    break;
                case 'up':
                case 'sp':
                    policyTreePolicies.manage(data.node);
                    break;
                case 'u':
                case 's':
                    policyTreePolicies.edit(data.node);
                    break;
            }

            $('#policyFix').affix('checkPosition');
        });
    }

    var getInstance = function() {
        return instance;
    }

    var setData = function(treeData) {
        instance.settings.core.data = treeData;
        instance.refresh(false, true);
    };

    var getSelectedNode = function() {
        var selected = instance.get_selected(true);

        return selected[0];
    }

    var getTopLevelPolicyId = function() {
        var selected = getSelectedNode();
        var policyId;

        if ('u' == selected.type || ('r' == selected.type && 'u' == instance.get_node(selected.parent).type)) {
            do {
                policyId = selected.data.policyId;
                selected = instance.get_node(selected.parent);
            }
            while (selected.id != 'u_p');
        }
        else if ('s' == selected.type || ('r' == selected.type && 's' == instance.get_node(selected.parent).type)) {
            do {
                policyId = selected.data.policyId;
                selected = instance.get_node(selected.parent);
            }
            while (selected.id != 's_p');
        }
        else {
            policyId = -1;
        }

        return policyId;
    }

    var policyImport = function(policy) {
        var policyNodeId = instance.create_node('u_p', policy);
        instance.deselect_node(instance.get_selected(), true);
        instance.select_node(policyNodeId);
        mcoMessage.success('Policy imported successfuly');
    }

    var policyCreate = function(policy, selectedPolicy) {
        if ('s_p' == selectedPolicy.id) {
            var policyNodeId = instance.create_node('u_p', policy);
        }
        else {
            var policyNodeId = instance.create_node(selectedPolicy, policy);
        }
        instance.deselect_node(instance.get_selected(), true);
        instance.select_node(policyNodeId);
        mcoMessage.success('Policy created successfuly');
    }

    var policyEdit = function(policy, selectedPolicy) {
        instance.rename_node(selectedPolicy, policy.policyName + ' (' + policy.policyType + ')');
        selectedPolicy.data.description = policy.policyDescription;
        selectedPolicy.data.type = policy.policyType;
        mcoMessage.success('Policy info changed successfuly');
    }

    var policyDelete = function(selectedPolicy) {
        var parent = instance.get_parent(selectedPolicy);
        instance.delete_node(selectedPolicy.id);
        instance.select_node(parent, true);
        mcoMessage.success('Policy successfuly removed');
    }

    var policyDuplicate = function(policy, selectedPolicy) {
        var policyNodeId = instance.create_node('u_p', policy);
        instance.select_node(policyNodeId);
        instance.deselect_node(selectedPolicy.id, true);
        mcoMessage.success('Policy successfuly duplicated');
    }

    var ruleCreate = function(rule, selectedPolicy) {
        var ruleNodeId = instance.create_node(selectedPolicy, dataRuleToJstree(rule));
        instance.deselect_node(selectedPolicy, true);
        instance.select_node(ruleNodeId);
        mcoMessage.success('Policy rule successfully created');
    }

    var ruleEdit = function(rule, selectedRule) {
        instance.rename_node(selectedRule, null == rule.title ? '' : rule.title);
        selectedRule.data.tracktype = rule.trackType;
        selectedRule.data.field = rule.field;
        selectedRule.data.occurrence = rule.occurrence;
        selectedRule.data.ope = rule.validator;
        selectedRule.data.value = rule.value;
        mcoMessage.success('Rule successfuly edited');
    }

    var ruleDelete = function(selectedRule) {
        var parent = instance.get_node(selectedRule.parent);
        instance.deselect_node(selectedRule, true);
        instance.select_node(parent, true);
        instance.delete_node(selectedRule);
        mcoMessage.success('Policy rule successfully deleted');
    }

    var ruleDuplicate = function(rule, selectedRule) {
        var parent = instance.get_node(selectedRule.parent);
        var ruleNodeId = instance.create_node(parent, dataRuleToJstree(rule));
        instance.deselect_node(selectedRule, true);
        instance.select_node(ruleNodeId);
        mcoMessage.success('Policy rule successfully duplicated');
    }

    var dataRuleToJstree = function(rule) {
        return {
            text: rule.name,
            type: 'r',
            data: {
                ruleId: rule.id,
                tracktype: rule.tracktype,
                field: rule.field,
                occurrence: rule.occurrence,
                ope: rule.ope,
                value: rule.value
            }
        }
    }

    return {
        init: init,
        getInstance: getInstance,
        getSelectedNode: getSelectedNode,
        getTopLevelPolicyId: getTopLevelPolicyId,
        setData: setData,
        policyImport: policyImport,
        policyCreate: policyCreate,
        policyEdit: policyEdit,
        policyDelete: policyDelete,
        policyDuplicate: policyDuplicate,
        ruleCreate: ruleCreate,
        ruleEdit: ruleEdit,
        ruleDelete: ruleDelete,
        ruleDuplicate: ruleDuplicate,
    };
})();

var policyTreePolicies = (function() {
    var manage = function(node) {
        $('.policyManage').removeClass('hidden');
        $('.policyEdit').addClass('hidden');
        $('.policyEditRule').addClass('hidden');
    }

    var edit = function(node) {
        $('#xslPolicyInfo_policyName').val(getName(node));
        $('#xslPolicyInfo_policyDescription').val(node.data.description);
        $('#xslPolicyInfo_policyType option[value="' + node.data.type + '"]').prop('selected', true);

        if ('s' == node.type || ('u' == node.type && !node.data.isEditable) ) {
            $('#policyDelete').removeClass('hidden');
            $('#policyRuleCreateContainer').addClass('hidden');
            $('#xslPolicyInfo_policyName').prop('disabled', true);
            $('#xslPolicyInfo_policyDescription').prop('disabled', true);
            $('#xslPolicyInfo_policyType').prop('disabled', true);
            $('#xslPolicyInfo_SavePolicyInfo').addClass('hidden');
            $('.policyEditActions.policyEditUser').addClass('hidden');
        }
        else {
            $('#policyDelete').removeClass('hidden');
            $('#policyRuleCreateContainer').removeClass('hidden');
            $('#xslPolicyInfo_policyName').prop('disabled', false);
            $('#xslPolicyInfo_policyDescription').prop('disabled', false);
            $('#xslPolicyInfo_policyType').prop('disabled', false);
            $('#xslPolicyInfo_SavePolicyInfo').removeClass('hidden');
            $('.policyEditActions.policyEditUser').removeClass('hidden');
        }

        if ('s' == node.type) {
            $('#policyDelete').addClass('hidden');
        }

        $('.policyManage').addClass('hidden');
        $('.policyEdit').removeClass('hidden');
        $('.policyEditRule').addClass('hidden');
    }

    // Remove (or|and) from policy name
    var getName = function(node) {
        if ('XSLT' == node.data.kind) {
            var regex = /(.*) \((or|and)\)$/i;

            if (null != regex.exec(node.text)) {
                return regex.exec(node.text)[1];
            }
        }

        return node.text;
    }

    return {
        manage: manage,
        edit: edit,
    }
})();

var policyTreeRules = (function() {
    function display(node, system) {
        $('#xslPolicyRule_title').val(node.text);

        $('#xslPolicyRule_field option').remove();
        $('#xslPolicyRule_field').append('<option value="' + node.data.field + '" selected>' + node.data.field + '</option>');
        $('#xslPolicyRule_value option').remove();
        if (null != node.data.value) {
            $('#xslPolicyRule_value').append('<option value="' + node.data.value + '" selected>' + node.data.value + '</option>');
        }

        $('#xslPolicyRule_trackType option[value="' + node.data.tracktype + '"]').prop('selected', true);
        $('#xslPolicyRule_trackType').trigger('change');
        $('#xslPolicyRule_occurrence').val(-1 == node.data.occurrence ? '*' : node.data.occurrence);
        $('#xslPolicyRule_validator option[value="' + node.data.ope + '"]').prop('selected', true);
        $('#xslPolicyRule_validator').trigger('change');

        if (system) {
            $('#xslPolicyRule_title').prop('disabled', true);
            $('#xslPolicyRule_trackType').prop('disabled', true);
            $('#xslPolicyRule_field').prop('disabled', true);
            $('#xslPolicyRule_occurrence').prop('disabled', true);
            $('#xslPolicyRule_validator').prop('disabled', true);
            $('#xslPolicyRule_value').prop('disabled', true);
            $('#xslPolicyRule_SaveRule').addClass('hidden');
            $('#xslPolicyRule_DuplicateRule').addClass('hidden');
            $('#xslPolicyRule_DeleteRule').addClass('hidden');
        }
        else {
            $('#xslPolicyRule_title').prop('disabled', false);
            $('#xslPolicyRule_trackType').prop('disabled', false);
            $('#xslPolicyRule_field').prop('disabled', false);
            displayOccurenceField($('#xslPolicyRule_trackType').val());
            $('#xslPolicyRule_validator').prop('disabled', false);
            $('#xslPolicyRule_value').prop('disabled', false);
            $('#xslPolicyRule_SaveRule').removeClass('hidden');
            $('#xslPolicyRule_DuplicateRule').removeClass('hidden');
            $('#xslPolicyRule_DeleteRule').removeClass('hidden');
        }

        $('.policyManage').addClass('hidden');
        $('.policyEdit').addClass('hidden');
        $('.policyEditRule').removeClass('hidden');
    }

    function loadFieldsList(trackType, field) {
        policyTreeAjax.getFieldsList(trackType, field);

        if (field) {
            $('#xslPolicyRule_field').trigger('change');
        }
    }

    function fieldsListOk(fields, field) {
        $('#xslPolicyRule_field option').remove();
        $('#xslPolicyRule_field').append('<option value="">Choose a field</option>');
        $.each(fields, function(k, v) {
            $('#xslPolicyRule_field').append('<option value="' + k + '">' + v + '</option>');
        });

        if (field) {
            $('#xslPolicyRule_field option[value="' + field + '"]').prop('selected', true);
        }
    }

    function fieldsListError(field) {
        $('#xslPolicyRule_field').html('');
        $('#xslPolicyRule_field').append('<option value="">Choose a field</option>');

        if (field) {
            $('#xslPolicyRule_field').append('<option value="' + field + '" selected>' + field + '</option>');
        }
    }

    function loadValuesList(trackType, field, value) {
        if (trackType && field) {
            policyTreeAjax.getValuesList(trackType, field, value);

            $('#xslPolicyRule_value').trigger('change');
        }
    }

    function valuesListOk(values, value) {
        $('#xslPolicyRule_value option').remove();
        $.each(values, function(k, v) {
            $('#xslPolicyRule_value').append('<option value="' + v + '">' + v + '</option>');
        });

        if (value) {
            $('#xslPolicyRule_value option[value="' + value + '"]').prop('selected', true);
        }
        else {
            $('#xslPolicyRule_value').prepend('<option value="" selected></option>');
        }
    }

    function valuesListError(value) {
        $('#xslPolicyRule_value option').remove();
        if (value) {
            $('#xslPolicyRule_value').append('<option value="' + value + '" selected>' + value + '</option>');
        }
    }

    function displayValueField(validator) {
        if ('exists' == validator || 'does_not_exist' == validator) {
            $('#xslPolicyRule_value').parent().addClass('hidden');
        }
        else {
            $('#xslPolicyRule_value').parent().removeClass('hidden');
        }
    }

    function displayOccurenceField(trackType) {
        if ('General' == trackType) {
            $('#xslPolicyRule_occurrence').prop('disabled', true);
            $('#xslPolicyRule_occurrence').val('*');
        }
        else {
            $('#xslPolicyRule_occurrence').prop('disabled', false);
        }
    }

    $('#xslPolicyRule_validator').on('change', function() {
        displayValueField($('#xslPolicyRule_validator').val());
    })

    $('#xslPolicyRule_trackType').on('change', function() {
        loadFieldsList($('#xslPolicyRule_trackType').val(), $('#xslPolicyRule_field').val());
        displayOccurenceField($('#xslPolicyRule_trackType').val());
    });

    $('#xslPolicyRule_field').on('change', function() {
        loadValuesList($('#xslPolicyRule_trackType').val(), $('#xslPolicyRule_field').val(), $('#xslPolicyRule_value').val());
    });

    return {
        display: display,
        fieldsListOk: fieldsListOk,
        fieldsListError: fieldsListError,
        valuesListOk: valuesListOk,
        valuesListError: valuesListError,
    }
})();

var rightPanelAffix = (function () {
    var node = $('#policyFix');

    var init = function() {
        // Right panel affix
        $('div.content').css('min-height', function () {
            return $('.policyRightCol').outerHeight(true);
        })
        node.css('width', function () {
            return $('.policyRightCol').outerWidth(true);
        })

        node.affix({
            offset: {
                top: function () {
                    return $('#collapse-1').outerHeight(true)
                },
                bottom: function () {
                    return ($('footer').outerHeight(true))
                }
            }
        })

        bindings();
    }

    var bindings = function() {
        node.on('affixed.bs.affix', function() {
            $('.affix').css('position', 'fixed');
            node.css('margin-top', '-100px');
        })

        node.on('affixed-top.bs.affix', function() {
            $('.affix-top').css('position', 'relative');
            node.css('margin-top', 0);
        })

        node.on('affixed-bottom.bs.affix', function() {
            $('.affix-bottom').css('position', 'relative');
        })
    }

    return {
        init: init,
    }
})();

function initPage() {
    mcoMessage.init('#policyInfo div');
    policyTree.init();

    // Make buttons in policy rule form display inline
    // Duplicate button
    $('#xslPolicyRule_DuplicateRule').parent().addClass('xslPolicyRuleDuplicateButton');
    // Save button
    $('#xslPolicyRule_SaveRule').parent().addClass('xslPolicyRuleSaveButton');
    // Delete button
    $('#xslPolicyRule_DeleteRule').parent().addClass('xslPolicyRuleDeleteButton');

    rightPanelAffix.init();
    formBindings();
    buttonBindings();
    setSelect2Plugin();
}

function setSelect2Plugin() {
    // Use select2 jquery plugin
    $('#xslPolicyRule_trackType').select2({
        theme: 'bootstrap',
        width: '100%',
        minimumResultsForSearch: Infinity
    });
    $('#xslPolicyRule_validator').select2({
        theme: 'bootstrap',
        width: '100%',
        minimumResultsForSearch: Infinity
    });
    $('#xslPolicyRule_field').select2({
        tags: true,
        theme: 'bootstrap',
        width: '100%'
    });
    // Replace input text by select
    $('#xslPolicyRule_value').replaceWith('<select id="' + $('#xslPolicyRule_value').prop('id') + '"  name="' + $('#xslPolicyRule_value').prop('name') + '"class="' + $('#xslPolicyRule_value').prop('class') + '">')
    $('#xslPolicyRule_value').select2({
        tags: true,
        theme: 'bootstrap',
        width: '100%'
    });
}

function formBindings() {
    // Import policy form
    $('form[name="xslPolicyImport"]').on('submit', function (e) {
        e.preventDefault();

        policyTreeAjax.policyImport($(this));
    });

    // Policy edit form
    $('form[name="xslPolicyInfo"]').on('submit', function (e) {
        e.preventDefault();

        policyTreeAjax.policyEdit($(this), policyTree.getSelectedNode(), policyTree.getTopLevelPolicyId());
    });

    // Policy rule edit form
    $('form[name="xslPolicyRule"]').on('submit', function (e) {
        e.preventDefault();
        var parentId = policyTree.getInstance().get_node(policyTree.getSelectedNode().parent).data.policyId;

        // Duplicate
        if ('xslPolicyRule_DuplicateRule' == $('button[type=submit][clicked=true]').prop('id')) {
            policyTreeAjax.ruleDuplicate(parentId, policyTree.getSelectedNode(), policyTree.getTopLevelPolicyId());
        }
        // Delete
        else if ('xslPolicyRule_DeleteRule' == $('button[type=submit][clicked=true]').prop('id')) {
            policyTreeAjax.ruleDelete(parentId, policyTree.getSelectedNode(), policyTree.getTopLevelPolicyId());
        }
        // Edit
        else {
            policyTreeAjax.ruleEdit($(this), parentId, policyTree.getSelectedNode(), policyTree.getTopLevelPolicyId());
        }
    });

    // Multiple form button click
    $('form[name="xslPolicyRule"] button[type=submit]').on('click', function() {
        $('form[name="xslPolicyRule"] button[type=submit]').removeAttr('clicked');
        $(this).attr('clicked', true);
    });
}

function buttonBindings() {
    $('#policyDuplicate').on('click', function() {
        policyTreeAjax.policyDuplicate(policyTree.getSelectedNode());
    })

    $('#policyExport').on('click', function() {
        policyTreeAjax.policyExport(policyTree.getSelectedNode());
    })

    $('#policyDelete').on('click', function() {
        policyTreeAjax.policyDelete(policyTree.getSelectedNode());
    })

    $('#policyRuleCreate').on('click', function() {
        policyTreeAjax.ruleCreate(policyTree.getSelectedNode(), policyTree.getTopLevelPolicyId());
    })

    // Create policy
    $('.policyCreate').on('click', function () {
        var policyNode = policyTree.getSelectedNode();
        var parentId = -1;
        if (typeof policyNode != 'undefined' && null != policyNode.data && policyNode.data.hasOwnProperty('policyId')) {
            parentId = policyNode.data.policyId;
        }

        policyTreeAjax.policyCreate(policyTree.getSelectedNode(), parentId, policyTree.getTopLevelPolicyId());
    });
}

$(document).ready(function () {
    initPage();
    policyTreeAjax.getData();
});
