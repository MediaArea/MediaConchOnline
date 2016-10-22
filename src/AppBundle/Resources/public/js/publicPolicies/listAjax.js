var publicPoliciesListAjax = (function() {
    var getList = function() {
        /**
         * Get the json for jstree
         *
         * @return json
         * {"policiesTree":POLICIES_JSTREE_JSON}
         */
        $.get(Routing.generate('app_publicapi_publicpolicieslist'))
        .done(function(data) {
            publicPoliciesList.displayList(data.list);
        })
        .fail(function (jqXHR) {
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

    return {
        getList: getList,
        getApiPolicyUrl: getApiPolicyUrl,
        policyExport: policyExport,
        policyImport: policyImport,
    };
})();
