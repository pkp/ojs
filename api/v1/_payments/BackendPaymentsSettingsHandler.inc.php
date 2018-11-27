<?php
/**
 * @file api/v1/_submissions/BackendPaymentsSettingsHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BackendPaymentsSettingsHandler
 * @ingroup api_v1_backend
 *
 * @brief A private API endpoint handler for payment settings. It may be
 *  possible to deprecate this when we have a working endpoint for plugin
 *  settings.
 */
import('lib.pkp.classes.handler.APIHandler');
import('classes.core.Services');

class BackendPaymentsSettingsHandler extends APIHandler {

	/**
	 * Constructor
	 */
	public function __construct() {
		$rootPattern = '/{contextPath}/api/{version}/_payments';
		$this->_endpoints = array_merge_recursive($this->_endpoints, array(
			'PUT' => array(
				array(
					'pattern' => $rootPattern,
					'handler' => array($this, 'edit'),
					'roles' => array(
						ROLE_ID_SITE_ADMIN,
						ROLE_ID_MANAGER,
					),
				),
			),
		));
		parent::__construct();
	}

	/**
	 * @copydoc PKPHandler::authorize
	 */
	public function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PolicySet');
		$rolePolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);

		import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');
		foreach ($roleAssignments as $role => $operations) {
			$rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
		}
		$this->addPolicy($rolePolicy);

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Receive requests to edit the payments form
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 *
	 * @return Response
	 */
	public function edit($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$context = $request->getContext();
		$params = $slimRequest->getParsedBody();
		$contextService = Services::get('context');

		// Process query params to format incoming data as needed
		foreach ($slimRequest->getParsedBody() as $param => $val) {
			switch ($param) {
				case 'paymentsEnabled':
					$params[$param] = $val === 'true';
					break;
				case 'currency':
					$params[$param] = (string) $val;
					break;
			}
		}

		if (isset($params['currency'])) {
			$errors = $contextService->validate(
				VALIDATE_ACTION_EDIT,
				['currency' => $params['currency']],
				$context->getSupportedLocales(),
				$context->getPrimaryLocale()
			);
			if (!empty($errors)) {
				return $response->withStatus(400)->withJson($errors);
			}
		}

		$paymentPlugins = PluginRegistry::loadCategory('paymethod', true);
		$errors = [];
		foreach ($paymentPlugins as $paymentPlugin) {
			$errors = array_merge(
				$errors,
				$paymentPlugin->saveSettings($params, $slimRequest, $request)
			);
		}
		if (!empty($errors)) {
			return $response->withStatus(400)->withJson($errors);
		}

		$context = $contextService->get($context->getId());
		$context = $contextService->edit($context, $params, $request);

		return $response->withJson($params);
	}
}
