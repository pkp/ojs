<?php
/**
 * @file api/v1/site/SiteHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SiteHandler
 * @ingroup api_v1_users
 *
 * @brief Base class to handle API requests for the site object.
 */

import('lib.pkp.classes.handler.APIHandler');

class SiteHandler extends APIHandler {
	/** @var string One of the SCHEMA_... constants */
	public $schemaName = SCHEMA_SITE;

	/**
	 * @copydoc APIHandler::__construct()
	 */
	public function __construct() {
		$this->_handlerPath = 'site';
		$roles = [ROLE_ID_SITE_ADMIN];
		$this->_endpoints = array(
			'GET' => array(
				array(
					'pattern' => $this->getEndpointPattern(),
					'handler' => array($this, 'get'),
					'roles' => $roles,
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/theme',
					'handler' => array($this, 'getTheme'),
					'roles' => $roles,
				),
			),
			'PUT' => array(
				array(
					'pattern' => $this->getEndpointPattern(),
					'handler' => array($this, 'edit'),
					'roles' => $roles,
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/theme',
					'handler' => array($this, 'editTheme'),
					'roles' => $roles,
				),
			),
		);
		parent::__construct();
	}

	/**
	 * @copydoc PKPHandler::authorize
	 */
	public function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PolicySet');
		$rolePolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);

		import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');
		foreach($roleAssignments as $role => $operations) {
			$rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
		}
		$this->addPolicy($rolePolicy);

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Get the site
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 *
	 * @return Response
	 */
	public function get($slimRequest, $response, $args) {
		$request = $this->getRequest();

		$siteProps = Services::get('site')
			->getFullProperties($request->getSite(), [
				'request' => $request,
			]);

		return $response->withJson($siteProps, 200);
	}

	/**
	 * Get the active theme on the site
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 *
	 * @return Response
	 */
	public function getTheme($slimRequest, $response, $args) {
		$site = $this->getRequest()->getSite();

		$allThemes = PluginRegistry::loadCategory('themes', true);
		$activeTheme = null;
		foreach ($allThemes as $theme) {
			if ($site->getData('themePluginPath') === $theme->getDirName()) {
				$activeTheme = $theme;
				break;
			}
		}

		if (!$activeTheme) {
			return $response->withStatus(404)->withJsonError('api.themes.404.themeUnavailable');
		}

		$data = array_merge(
			$activeTheme->getOptionValues(CONTEXT_ID_NONE),
			['themePluginPath' => $theme->getDirName()]
		);

		ksort($data);

		return $response->withJson($data, 200);
	}

	/**
	 * Edit the site
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 *
	 * @return Response
	 */
	public function edit($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$site = $request->getSite();
		$siteService = Services::get('site');

		$params = $this->convertStringsToSchema(SCHEMA_SITE, $slimRequest->getParsedBody());

		$errors = $siteService->validate($params, $site->getSupportedLocales(), $site->getPrimaryLocale());

		if (!empty($errors)) {
			return $response->withStatus(400)->withJson($errors);
		}
		$site = $siteService->edit($site, $params, $request);

		$siteProps = $siteService->getFullProperties($site, array(
			'request' => $request,
			'slimRequest' 	=> $slimRequest
		));

		return $response->withJson($siteProps, 200);
	}

	/**
	 * Edit the active theme and theme options on the site
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 *
	 * @return Response
	 */
	public function editTheme($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$site = $request->getSite();
		$siteService = Services::get('site');

		$params = $slimRequest->getParsedBody();

		// Validate the themePluginPath and allow themes to perform their own validation
		$themePluginPath = empty($params['themePluginPath']) ? null : $params['themePluginPath'];
		if ($themePluginPath !== $site->getData('themePluginPath')) {
			$errors = $siteService->validate(
				['themePluginPath' => $themePluginPath],
				$site->getSupportedLocales(),
				$site->getPrimaryLocale()
			);
			if (!empty($errors)) {
				return $response->withJson($errors, 400);
			}
			$newSite = $siteService->edit($site, ['themePluginPath' => $themePluginPath], $request);
		}

		// Get the appropriate theme plugin
		$allThemes = PluginRegistry::loadCategory('themes', true);
		$selectedTheme = null;
		foreach ($allThemes as $theme) {
			if ($themePluginPath === $theme->getDirName()) {
				$selectedTheme = $theme;
				break;
			}
		}

		// Run the theme's init() method if a new theme has been selected
		if (isset($newSite)) {
			$selectedTheme->init();
		}

		$errors = $selectedTheme->validateOptions($params, $themePluginPath, CONTEXT_ID_NONE, $request);
		if (!empty($errors)) {
			return $response->withJson($errors, 400);
		}

		// Only accept params that are defined in the theme options
		$options = $selectedTheme->getOptionsConfig();
		foreach ($options as $optionName => $optionConfig) {
			if (!array_key_exists($optionName, $params)) {
				continue;
			}
			$selectedTheme->saveOption($optionName, $params[$optionName], CONTEXT_ID_NONE);
		}

		// Clear the template cache so that new settings can take effect
		$templateMgr = TemplateManager::getManager(Application::get()->getRequest());
		$templateMgr->clearTemplateCache();
		$templateMgr->clearCssCache();

		$data = array_merge(
			$selectedTheme->getOptionValues(CONTEXT_ID_NONE),
			['themePluginPath' => $themePluginPath]
		);

		ksort($data);

		return $response->withJson($data, 200);
	}
}
