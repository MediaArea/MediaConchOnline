var publicPoliciesListAjax = (function() {
    var getList = function() {
        /**
         * Get the json for jstree
         *
         * @return json
         * {"policiesTree":POLICIES_JSTREE_JSON}
         */
        $.get(Routing.generate('app_connectedapi_publicpolicieslist'))
        .done(function(data) {
            policyListSpinner.hide();
            publicPoliciesList.displayList(data.list);
        })
        .fail(function (jqXHR) {
            policyListSpinner.hide();
            mcoMessage.fail(jqXHR);
        })
    };

    var getApiPolicyUrl = function(policyId, policyUserId) {
        /**
         * Url of policy API
         * @param int policyId policy ID of the policy
         * @param int policyUserId user ID of the policy
         *
         * @return string
         * /api/public/v1/publicpolicies/policy/POLICYID/POLICYUSERID
         */
        return Routing.generate('app_publicapi_publicpoliciespolicy', {id: policyId, userId: policyUserId});
    };

    var policyExport = function(policyId, policyUserId) {
        /**
        * Export XML of a policy
        * @param int policyId policy ID of the policy to export
        * @param int policyUserId user ID of the policy to export
        *
        * @return XML
        */
        window.location = Routing.generate('app_publicpolicies_policyexport', {id: policyId, userId: policyUserId});
    };

    var policyImport = function(policyId, policyUserId, button) {
        /**
        * Import a policy to user policies
        * @param int policyId policy ID of the policy to export
        * @param int policyUserId user ID of the policy to export
        *
        * @return json
        * {"policyId":ID}
        */
        $.get(Routing.generate('app_publicpolicies_policyimport', {id: policyId, userId: policyUserId}))
        .done(function(data) {
            importPolicy.success(data.policyId, button);
        })
        .fail(function (jqXHR) {
            importPolicy.error(button);
        })
    };

    var policyUnpublish = function(policyId, button) {
        /**
        * Unpublish a policy
        * @param int id policy ID of the policy to unpublish
        *
        * @return json
        * {"policyId":ID}
        */
        $.ajax({
            url: Routing.generate('app_connectedapi_publicpoliciesunpublish', {id: policyId}),
            method: 'PUT'
        })
        .done(function(data) {
            unpublishPolicy.success(button);
        })
        .fail(function (jqXHR) {
            unpublishPolicy.error(button);
        })
    };

    return {
        getList: getList,
        getApiPolicyUrl: getApiPolicyUrl,
        policyExport: policyExport,
        policyImport: policyImport,
        policyUnpublish: policyUnpublish,
    };
})();
