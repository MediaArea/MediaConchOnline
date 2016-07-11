function getPolicyTreeData() {
    $.get(Routing.generate('app_xslpolicy_xslpolicytreedata'))
    .done(function(data) {
        displayTree(data.policiesTree);
    })
}

function policyImportForm(form) {
    $.ajax({
        type: form.attr('method'),
            url: Routing.generate('app_xslpolicy_xslpolicytreeimport'),
            data: new FormData(form[0]),
            processData: false,
            contentType: false
    })
    .done(function (data) {
        policyImport(data);
    })
    .fail(function (jqXHR) {
        failResponse(jqXHR, 'form[name="xslPolicyImport"]');
    })
}

function policyCreateForm(form) {
    $.ajax({
        type: form.attr('method'),
            url: Routing.generate('app_xslpolicy_xslpolicytreecreate'),
            data: new FormData(form[0]),
            processData: false,
            contentType: false
    })
    .done(function (data) {
        policyCreate(data);
    })
    .fail(function (jqXHR) {
        failResponse(jqXHR, 'form[name="xslPolicyCreate"]');
    })
}

function policyRuleForm(form, policyNode, ruleNode, action) {
    if ('delete' == action) {
        routeAction = 'app_xslpolicy_xslpolicytreeruledelete';
    }
    else {
        routeAction = 'app_xslpolicy_xslpolicytreeruleedit';
    }

    $.ajax({
        type: form.attr('method'),
            url: Routing.generate(routeAction, {id: policyNode.data.policyId, ruleId: ruleNode.data.ruleId, action: action}),
            data: new FormData(form[0]),
            processData: false,
            contentType: false
    })
    .done(function (data) {
        ruleAction(data, ruleNode, action);
    })
    .fail(function (jqXHR) {
        failResponse(jqXHR, 'form[name="xslPolicyRule"]');
    })
}

function policyNameForm(form, policyNode) {
    $.ajax({
        type: form.attr('method'),
            url: Routing.generate('app_xslpolicy_xslpolicytreename', {id: policyNode.data.policyId}),
            data: new FormData(form[0]),
            processData: false,
            contentType: false
    })
    .done(function (data) {
        policyNameChange(data, policyNode);
    })
    .fail(function (jqXHR) {
        failResponse(jqXHR, 'form[name="xslPolicyName"]');
    })
}

function policyDuplicateRequest(policyNode) {
    $.get(Routing.generate('app_xslpolicy_xslpolicytreeduplicate', {id: policyNode.data.policyId}))
    .done(function (data) {
        policyDuplicate(data, policyNode);
    })
    .fail(function (jqXHR) {
        failResponse(jqXHR, '#policyDuplicate');
    })
}

function policyExportRequest(policyId) {
    window.location = Routing.generate('app_xslpolicy_xslpolicyexport', {id: policyId});
}

function policyDeleteRequest(policyNode) {
    $.get(Routing.generate('app_xslpolicy_xslpolicytreedelete', {id: policyNode.data.policyId}))
    .done(function (data) {
        policyDelete(data, policyNode);
    })
    .fail(function (jqXHR) {
        failResponse(jqXHR, '#policyDelete');
    })
}

function policyRuleCreateRequest(policyNode) {
    $.get(Routing.generate('app_xslpolicy_xslpolicytreecheck', {id: policyNode.data.policyId}))
    .done(function (data) {
        rule = {text: 'New rule', type: 'r', data: {ruleId: 'new', trackType: '', field: '', occurrence: 1, validator: '', value: ''}};
        policyRuleCreate(rule, policyNode);
    })
    .fail(function (jqXHR) {
        failResponse(jqXHR, '#policyRuleCreate');
    })
}

function getFieldsList(trackType, field) {
    $.post(Routing.generate('app_xslpolicy_xslpolicyrulefieldslist'), {type: trackType, field: field})
    .done(function(data) {
        fieldsListOk(data, field)
    })
    .fail(function () {
        fieldsListError(field)
    });
}

function getValuesList(trackType, field, value) {
    $.post(Routing.generate('app_xslpolicy_xslpolicyrulevalueslist'), {type: trackType, field: field, value: value})
    .done(function(data) {
        valuesListOk(data.values, value);
    })
    .fail(function () {
        valuesListError(value);
    });
}
