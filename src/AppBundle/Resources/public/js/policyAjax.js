var policyTreeAjax = (function() {
    var getData = function() {
        $.get(Routing.generate('app_xslpolicy_xslpolicytreedata'))
        .done(function(data) {
            policyTree.setData(data.policiesTree);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    };

    var policyImport = function(form) {
        $.ajax({
            type: form.attr('method'),
                url: Routing.generate('app_xslpolicy_xslpolicytreeimport'),
                data: new FormData(form[0]),
                processData: false,
                contentType: false
        })
        .done(function (data) {
            policyTree.policyImport(data.policy);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    }

    var policyCreate = function(policyNode, parentId, topLevelId) {
        $.get(Routing.generate('app_xslpolicy_xslpolicytreecreate', {parentId: parentId, topLevelId: topLevelId}))
        .done(function (data) {
            policyTree.policyCreate(data.policy, policyNode);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    }

    var policyEdit = function(form, policyNode, topLevelId) {
        $.ajax({
            type: form.attr('method'),
                url: Routing.generate('app_xslpolicy_xslpolicytreeedit', {id: policyNode.data.policyId, topLevelId: topLevelId}),
                data: new FormData(form[0]),
                processData: false,
                contentType: false
        })
        .done(function (data) {
            policyTree.policyEdit(data, policyNode);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    }

    var policyDelete = function(policyNode) {
        $.get(Routing.generate('app_xslpolicy_xslpolicytreedelete', {id: policyNode.data.policyId}))
        .done(function (data) {
            policyTree.policyDelete(policyNode);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    }

    var policyExport = function(policyNode) {
        window.location = Routing.generate('app_xslpolicy_xslpolicytreeexport', {id: policyNode.data.policyId});
    }

    var policyDuplicate = function(policyNode) {
        $.get(Routing.generate('app_xslpolicy_xslpolicytreeduplicate', {id: policyNode.data.policyId}))
        .done(function (data) {
            policyTree.policyDuplicate(data.policy, policyNode);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    }

    var ruleCreate = function(policyNode, topLevelId) {
        $.get(Routing.generate('app_xslpolicy_xslpolicytreerulecreate', {policyId: policyNode.data.policyId, topLevelId: topLevelId}))
        .done(function (data) {
            policyTree.ruleCreate(data.rule, policyNode);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    }

    var ruleEdit = function(form, policyId, ruleNode, topLevelId) {
        $.ajax({
            type: form.attr('method'),
                url: Routing.generate('app_xslpolicy_xslpolicytreeruleedit', {id: ruleNode.data.ruleId, policyId: policyId, topLevelId: topLevelId}),
                data: new FormData(form[0]),
                processData: false,
                contentType: false
        })
        .done(function (data) {
            policyTree.ruleEdit(data.rule, ruleNode);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    }

    var ruleDelete = function(policyId, ruleNode, topLevelId) {
        $.get(Routing.generate('app_xslpolicy_xslpolicytreeruledelete', {id: ruleNode.data.ruleId, policyId: policyId, topLevelId: topLevelId}))
        .done(function (data) {
            policyTree.ruleDelete(ruleNode);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    }

    var ruleDuplicate = function(policyId, ruleNode, topLevelId) {
        $.get(Routing.generate('app_xslpolicy_xslpolicytreeruleduplicate', {id: ruleNode.data.ruleId, policyId: policyId, topLevelId: topLevelId}))
        .done(function (data) {
            policyTree.ruleDuplicate(data.rule, ruleNode);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    }

    var getFieldsList = function(trackType, field) {
        $.post(Routing.generate('app_xslpolicy_xslpolicyrulefieldslist'), {type: trackType, field: field})
        .done(function(data) {
            policyTreeRules.fieldsListOk(data, field)
        })
        .fail(function () {
            policyTreeRules.fieldsListError(field)
        });
    }

    var getValuesList = function(trackType, field, value) {
        $.post(Routing.generate('app_xslpolicy_xslpolicyrulevalueslist'), {type: trackType, field: field, value: value})
        .done(function(data) {
            policyTreeRules.valuesListOk(data.values, value);
        })
        .fail(function () {
            policyTreeRules.valuesListError(value);
        });
    }

    return {
        getData: getData,
        policyImport: policyImport,
        policyCreate: policyCreate,
        policyEdit: policyEdit,
        policyDelete: policyDelete,
        policyDuplicate: policyDuplicate,
        policyExport: policyExport,
        ruleCreate: ruleCreate,
        ruleEdit: ruleEdit,
        ruleDelete: ruleDelete,
        ruleDuplicate: ruleDuplicate,
        getFieldsList: getFieldsList,
        getValuesList: getValuesList,
    };
})();
