<?php

/**
 * @file classes/handler/PKPHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package core
 * @class PKPHandler
 *
 * Base request handler abstract class.
 *
 */

class PKPHandler {
	/**
	 * @var string identifier of the controller instance - must be unique
	 *  among all instances of a given controller type.
	 */
	var $_id;

	/** @var Dispatcher, mainly needed for cross-router url construction */
	var $_dispatcher;

	/** @var array validation checks for this page - deprecated! */
	var $_checks = array();

	/**
	 * @var array
	 *  The value of this variable should look like this:
	 *  array(
	 *    ROLE_ID_... => array(...allowed handler operations...),
	 *    ...
	 *  )
	 */
	var $_roleAssignments = array();

	/** @var AuthorizationDecisionManager authorization decision manager for this handler */
	var $_authorizationDecisionManager;

	/** @var boolean Whether to enforce site access restrictions. */
	var $_enforceRestrictedSite = true;

	/**
	 * Constructor
	 */
	function __construct() {
	}

	//
	// Setters and Getters
	//
	function setEnforceRestrictedSite($enforceRestrictedSite) {
		$this->_enforceRestrictedSite = $enforceRestrictedSite;
	}

	/**
	 * Set the controller id
	 * @param $id string
	 */
	function setId($id) {
		$this->_id = $id;
	}

	/**
	 * Get the controller id
	 * @return string
	 */
	function getId() {
		return $this->_id;
	}

	/**
	 * Get the dispatcher
	 *
	 * NB: The dispatcher will only be set after
	 * handler instantiation. Calling getDispatcher()
	 * in the constructor will fail.
	 *
	 * @return Dispatcher
	 */
	function &getDispatcher() {
		assert(!is_null($this->_dispatcher));
		return $this->_dispatcher;
	}

	/**
	 * Set the dispatcher
	 * @param $dispatcher PKPDispatcher
	 */
	function setDispatcher($dispatcher) {
		$this->_dispatcher = $dispatcher;
	}

	/**
	 * Fallback method in case request handler does not implement index method.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$dispatcher = $this->getDispatcher();
		if (isset($dispatcher)) $dispatcher->handle404();
		else Dispatcher::handle404(); // For old-style handlers
	}

	/**
	 * Add an authorization policy for this handler which will
	 * be applied in the authorize() method.
	 *
	 * Policies must be added in the class constructor or in the
	 * subclasses' authorize() method before the parent::authorize()
	 * call so that PKPHandler::authorize() will be able to enforce
	 * them.
	 *
	 * @param $authorizationPolicy AuthorizationPolicy
	 * @param $addToTop boolean whether to insert the new policy
	 *  to the top of the list.
	 */
	function addPolicy($authorizationPolicy, $addToTop = false) {
		if (is_null($this->_authorizationDecisionManager)) {
			// Instantiate the authorization decision manager
			import('lib.pkp.classes.security.authorization.AuthorizationDecisionManager');
			$this->_authorizationDecisionManager = new AuthorizationDecisionManager();
		}

		// Add authorization policies to the authorization decision manager.
		$this->_authorizationDecisionManager->addPolicy($authorizationPolicy, $addToTop);
	}

	/**
	 * Retrieve authorized context objects from the
	 * decision manager.
	 * @param $assocType integer any of the ASSOC_TYPE_* constants
	 * @return mixed
	 */
	function &getAuthorizedContextObject($assocType) {
		assert(is_a($this->_authorizationDecisionManager, 'AuthorizationDecisionManager'));
		return $this->_authorizationDecisionManager->getAuthorizedContextObject($assocType);
	}

	/**
	 * Get the authorized context.
	 *
	 * NB: You should avoid accessing the authorized context
	 * directly to avoid accidentally overwriting an object
	 * in the context. Try to use getAuthorizedContextObject()
	 * instead where possible.
	 *
	 * @return array
	 */
	function &getAuthorizedContext() {
		assert(is_a($this->_authorizationDecisionManager, 'AuthorizationDecisionManager'));
		return $this->_authorizationDecisionManager->getAuthorizedContext();
	}

	/**
	 * Retrieve the last authorization message from the
	 * decision manager.
	 * @return string
	 */
	function getLastAuthorizationMessage() {
		assert(is_a($this->_authorizationDecisionManager, 'AuthorizationDecisionManager'));
		$authorizationMessages = $this->_authorizationDecisionManager->getAuthorizationMessages();
		return end($authorizationMessages);
	}

	/**
	 * Add role - operation assignments to the handler.
	 *
	 * @param $roleIds integer|array one or more of the ROLE_ID_*
	 *  constants
	 * @param $operations string|array a single method name or
	 *  an array of method names to be assigned.
	 */
	function addRoleAssignment($roleIds, $operations) {
		// Allow single operations to be passed in as scalars.
		if (!is_array($operations)) $operations = array($operations);

		// Allow single roles to be passed in as scalars.
		if (!is_array($roleIds)) $roleIds = array($roleIds);

		// Add the given operations to all roles.
		foreach($roleIds as $roleId) {
			// Create an empty assignment array if no operations
			// have been assigned to the given role before.
			if (!isset($this->_roleAssignments[$roleId])) {
				$this->_roleAssignments[$roleId] = array();
			}

			// Merge the new operations with the already assigned
			// ones for the given role.
			$this->_roleAssignments[$roleId] = array_merge(
				$this->_roleAssignments[$roleId],
				$operations
			);
		}
	}

	/**
	 * This method returns an assignment of operation names for the
	 * given role.
	 * @param $roleId int
	 * @return array assignment for the given role.
	 */
	function getRoleAssignment($roleId) {
		if (!is_null($roleId)) {
			if (isset($this->_roleAssignments[$roleId])) {
				return $this->_roleAssignments[$roleId];
			} else {
				return null;
			}
		}
	}

	/**
	 * This method returns an assignment of roles to operation names.
	 *
	 * @return array assignments for all roles.
	 */
	function getRoleAssignments() {
		return $this->_roleAssignments;
	}

	/**
	 * Authorize this request.
	 *
	 * Routers will call this method automatically thereby enforcing
	 * authorization. This method will be called before the
	 * validate() method and before passing control on to the
	 * handler operation.
	 *
	 * NB: This method will be called once for every request only.
	 *
	 * @param $request Request
	 * @param $args array request arguments
	 * @param $roleAssignments array the operation role assignment,
	 *  see getRoleAssignment() for more details.
	 * @return boolean
	 */
	function authorize($request, &$args, $roleAssignments) {
		// Enforce restricted site access if required.
		if ($this->_enforceRestrictedSite) {
			import('lib.pkp.classes.security.authorization.RestrictedSiteAccessPolicy');
			$this->addPolicy(new RestrictedSiteAccessPolicy($request), true);
		}

		// Enforce SSL site-wide.
		if ($this->requireSSL()) {
			import('lib.pkp.classes.security.authorization.HttpsPolicy');
			$this->addPolicy(new HttpsPolicy($request), true);
		}

		if (!defined('SESSION_DISABLE_INIT')) {
			// Add user roles in authorized context.
			$user = $request->getUser();
			if (is_a($user, 'User')) {
				import('lib.pkp.classes.security.authorization.UserRolesRequiredPolicy');
				$this->addPolicy(new UserRolesRequiredPolicy($request), true);
			}
		}

		// Make sure that we have a valid decision manager instance.
		assert(is_a($this->_authorizationDecisionManager, 'AuthorizationDecisionManager'));

		$router = $request->getRouter();
		if (is_a($router, 'PKPPageRouter')) {
			// We have to apply a blacklist approach for page
			// controllers to maintain backwards compatibility:
			// Requests are implicitly authorized if no policy
			// explicitly denies access.
			$this->_authorizationDecisionManager->setDecisionIfNoPolicyApplies(AUTHORIZATION_PERMIT);
		} else {
			// We implement a strict whitelist approach for
			// all other components: Requests will only be
			// authorized if at least one policy explicitly
			// grants access and none denies access.
			$this->_authorizationDecisionManager->setDecisionIfNoPolicyApplies(AUTHORIZATION_DENY);
		}

		// Let the authorization decision manager take a decision.
		$decision = $this->_authorizationDecisionManager->decide();
		if ($decision == AUTHORIZATION_PERMIT) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Perform data integrity checks.
	 *
	 * This method will be called once for every request only.
	 *
	 * NB: Any kind of authorization check is now deprecated
	 * within this method. This method is purely meant for data
	 * integrity checks that do not lead to denial of access
	 * to resources (e.g. via redirect) like handler operations
	 * or data objects.
	 *
	 * @param $requiredContexts array
	 * @param $request Request
	 */
	function validate($requiredContexts = null, $request = null) {
		// FIXME: for backwards compatibility only - remove when request/router refactoring complete
		if (!isset($request)) {
			$request =& Registry::get('request');
			if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated call without request object.');
		}

		foreach ($this->_checks as $check) {
			// Using authorization checks in the validate() method is deprecated
			// FIXME: Trigger a deprecation warning.

			// check should redirect on fail and continue on pass
			// default action is to redirect to the index page on fail
			if ( !$check->isValid() ) {
				if ( $check->redirectToLogin ) {
					Validation::redirectLogin();
				} else {
					// An unauthorized page request will be re-routed
					// to the index page.
					$request->redirect(null, 'index');
				}
			}
		}

		return true;
	}

	/**
	 * Subclasses can override this method to configure the
	 * handler.
	 *
	 * NB: This method will be called after validation and
	 * authorization.
	 *
	 * @param $request PKPRequest
	 */
	function initialize($request) {
		// Set the controller id to the requested
		// page (page routing) or component name
		// (component routing) by default.
		$router = $request->getRouter();
		if (is_a($router, 'PKPComponentRouter')) {
			$componentId = $router->getRequestedComponent($request);
			// Create a somewhat compressed but still globally unique
			// and human readable component id.
			// Example: "grid.citation.CitationGridHandler"
			// becomes "grid-citation-citationgrid"
			$componentId = str_replace('.', '-', PKPString::strtolower(PKPString::substr($componentId, 0, -7)));
			$this->setId($componentId);
		} else {
			assert(is_a($router, 'PKPPageRouter'));
			$this->setId($router->getRequestedPage($request));
		}
	}

	/**
	 * Return the DBResultRange structure and misc. variables describing the current page of a set of pages.
	 * @param $request PKPRequest
	 * @param $rangeName string Symbolic name of range of pages; must match the Smarty {page_list ...} name.
	 * @param $contextData array If set, this should contain a set of data that are required to
	 * 	define the context of this request (for maintaining page numbers across requests).
	 *	To disable persistent page contexts, set this variable to null.
	 * @return DBResultRange
	 */
	static function getRangeInfo($request, $rangeName, $contextData = null) {
		$context = $request->getContext();
		$pageNum = $request->getUserVar(self::getPageParamName($rangeName));
		if (empty($pageNum)) {
			$session =& $request->getSession();
			$pageNum = 1; // Default to page 1
			if ($session && $contextData !== null) {
				// See if we can get a page number from a prior request
				$contextHash = self::hashPageContext($request, $contextData);

				if ($request->getUserVar('clearPageContext')) {
					// Explicitly clear the old page context
					$session->unsetSessionVar("page-$contextHash");
				} else {
					$oldPage = $session->getSessionVar("page-$contextHash");
					if (is_numeric($oldPage)) $pageNum = $oldPage;
				}
			}
		} else {
			$session =& $request->getSession();
			if ($session && $contextData !== null) {
				// Store the page number
				$contextHash = self::hashPageContext($request, $contextData);
				$session->setSessionVar("page-$contextHash", $pageNum);
			}
		}

		if ($context) $count = $context->getSetting('itemsPerPage');
		if (!isset($count)) $count = Config::getVar('interface', 'items_per_page');

		import('lib.pkp.classes.db.DBResultRange');

		if (isset($count)) return new DBResultRange($count, $pageNum);
		else return new DBResultRange(-1, -1);
	}

	/**
	 * Get the range info page parameter name.
	 * @param $rangeName string
	 * @return string
	 */
	static function getPageParamName($rangeName) {
		return $rangeName . 'Page';
	}

	/**
	 * Set up the basic template.
	 * @param $request PKPRequest
	 */
	function setupTemplate($request) {
		// FIXME: for backwards compatibility only - remove
		if (!isset($request)) {
			$request =& Registry::get('request');
			if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated call without request object.');
		}
		assert(is_a($request, 'PKPRequest'));

		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_COMMON,
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_APP_COMMON
		);

		$userRoles = (array) $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		if (array_intersect(array(ROLE_ID_MANAGER), $userRoles)) {
			AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER);
		}

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('userRoles', $userRoles);

		$accessibleWorkflowStages = $this->getAuthorizedContextObject(ASSOC_TYPE_ACCESSIBLE_WORKFLOW_STAGES);
		if ($accessibleWorkflowStages) $templateMgr->assign('accessibleWorkflowStages', $accessibleWorkflowStages);
	}

	/**
	 * Generate a unique-ish hash of the page's identity, including all
	 * context that differentiates it from other similar pages (e.g. all
	 * articles vs. all articles starting with "l").
	 * @param $request PKPRequest
	 * @param $contextData array A set of information identifying the page
	 * @return string hash
	 */
	static function hashPageContext($request, $contextData = array()) {
		return md5(
			implode(',', $request->getRequestedContextPath()) . ',' .
			$request->getRequestedPage() . ',' .
			$request->getRequestedOp() . ',' .
			serialize($contextData)
		);
	}

	/**
	 * Get the iterator of working contexts.
	 * @param $request PKPRequest
	 * @return ItemIterator
	 */
	function getWorkingContexts($request) {
		// For installation process
		if (defined('SESSION_DISABLE_INIT') || !Config::getVar('general', 'installed')) {
			return null;
		}

		$user = $request->getUser();
		$contextDao = Application::getContextDAO();
		return $contextDao->getAvailable($user?$user->getId():null);
	}

	/**
	 * Return the context that is configured in site redirect setting.
	 * @param $request Request
	 * @return mixed Either Context or null
	 */
	function getSiteRedirectContext($request) {
		$site = $request->getSite();
		if ($site && ($contextId = $site->getRedirect())) {
			$contextDao = Application::getContextDAO(); /* @var $contextDao ContextDAO */
			return $contextDao->getById($contextId);
		}
		return null;
	}

	/**
	 * Return the first context that user is enrolled with.
	 * @param $user User
	 * @param $contexts Array
	 * @return mixed Either Context or null
	 */
	function getFirstUserContext($user, $contexts) {
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$context = null;
		foreach($contexts as $workingContext) {
			$userIsEnrolled = $userGroupDao->userInAnyGroup($user->getId(), $workingContext->getId());
			if ($userIsEnrolled) {
				$context = $workingContext;
				break;
			}
		}
		return $context;
	}

	/**
	 * Assume SSL is required for all handlers, unless overridden in subclasses.
	 * @return boolean
	 */
	function requireSSL() {
		return true;
	}
}

?>
