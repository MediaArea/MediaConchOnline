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

    var policyCreate = function(policyNode, parentId = -1) {
        $.get(Routing.generate('app_xslpolicy_xslpolicytreecreate', {parentId: parentId}))
        .done(function (data) {
            policyTree.policyCreate(data.policy, policyNode);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    }

    var policyEdit = function(form, policyNode) {
        $.ajax({
            type: form.attr('method'),
                url: Routing.generate('app_xslpolicy_xslpolicytreeedit', {id: policyNode.data.policyId}),
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

    var policyDuplicate = function(policyNode, dstNode) {
        $.get(Routing.generate('app_xslpolicy_xslpolicytreeduplicate', {id: policyNode.data.policyId, dstPolicyId: policyTree.getPolicyId(dstNode)}))
        .done(function (data) {
            policyTree.policyDuplicate(data.policy, dstNode);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    }

    var policyMove = function(policyNode, dstNode) {
        $.get(Routing.generate('app_xslpolicy_xslpolicytreemove', {id: policyNode.data.policyId, dstPolicyId: policyTree.getPolicyId(dstNode)}))
        .done(function (data) {
            policyTree.policyMove(data.policy, dstNode, policyNode);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    }

    var ruleCreate = function(policyNode) {
        $.get(Routing.generate('app_xslpolicy_xslpolicytreerulecreate', {policyId: policyNode.data.policyId}))
        .done(function (data) {
            policyTree.ruleCreate(data.rule, policyNode);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    }

    var ruleEdit = function(form, policyId, ruleNode) {
        $.ajax({
            type: form.attr('method'),
                url: Routing.generate('app_xslpolicy_xslpolicytreeruleedit', {id: ruleNode.data.ruleId, policyId: policyId}),
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

    var ruleDelete = function(policyId, ruleNode) {
        $.get(Routing.generate('app_xslpolicy_xslpolicytreeruledelete', {id: ruleNode.data.ruleId, policyId: policyId}))
        .done(function (data) {
            policyTree.ruleDelete(ruleNode);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    }

    var ruleDuplicate = function(policyId, ruleNode, dstNode) {
        $.get(Routing.generate('app_xslpolicy_xslpolicytreeruleduplicate', {id: ruleNode.data.ruleId, policyId: policyId, dstPolicyId: dstNode.data.policyId}))
        .done(function (data) {
            policyTree.ruleDuplicate(data.rule, dstNode);
        })
        .fail(function (jqXHR) {
            mcoMessage.fail(jqXHR);
        })
    }

    var ruleMove = function(policyId, ruleNode, dstNode) {
        $.get(Routing.generate('app_xslpolicy_xslpolicytreerulemove', {id: ruleNode.data.ruleId, policyId: policyId, dstPolicyId: policyTree.getPolicyId(dstNode)}))
        .done(function (data) {
            policyTree.ruleMove(data.rule, dstNode, ruleNode);
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
        policyMove: policyMove,
        policyExport: policyExport,
        ruleCreate: ruleCreate,
        ruleEdit: ruleEdit,
        ruleDelete: ruleDelete,
        ruleDuplicate: ruleDuplicate,
        ruleMove: ruleMove,
        getFieldsList: getFieldsList,
        getValuesList: getValuesList,
    };
})();
