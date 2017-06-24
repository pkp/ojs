<?php

/**
 * @file controllers/grid/users/subscriberSelect/SubscriberSelectGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriberSelectGridHandler
 * @ingroup controllers_grid_users_subscriberSelect
 *
 * @brief Handle subscriber selector grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.controllers.grid.users.userSelect.UserSelectGridCellProvider');

class SubscriberSelectGridHandler extends GridHandler {
	/** @var array (user group ID => user group name) **/
	var $_userGroupOptions;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUBSCRIPTION_MANAGER),
			array('fetchGrid', 'fetchRows')
		);
	}

	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_PKP_MANAGER,
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_PKP_EDITOR,
			LOCALE_COMPONENT_APP_EDITOR
		);

		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroups = $userGroupDao->getUserGroupsByStage(
			$request->getContext()->getId(),
			$stageId
		);
		$this->_userGroupOptions = array();
		while ($userGroup = $userGroups->next()) {
			$this->_userGroupOptions[$userGroup->getId()] = $userGroup->getLocalizedName();
		}

		$this->setTitle('editor.submission.findAndSelectUser');

		// Columns
		$cellProvider = new UserSelectGridCellProvider();
		$this->addColumn(
			new GridColumn(
				'select',
				'',
				null,
				'controllers/grid/users/userSelect/userSelectRadioButton.tpl',
				$cellProvider,
				array('width' => 5)
			)
		);
		$this->addColumn(
			new GridColumn(
				'name',
				'author.users.contributor.name',
				null,
				null,
				$cellProvider,
				array('alignment' => COLUMN_ALIGNMENT_LEFT,
					'width' => 30
				)
			)
		);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.InfiniteScrollingFeature');
		import('lib.pkp.classes.controllers.grid.feature.CollapsibleGridFeature');
		return array(new InfiniteScrollingFeature('infiniteScrolling', $this->getItemsNumber()), new CollapsibleGridFeature());
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		// Get the context.
		$context = $request->getContext();

		// Get all users for this context that match search criteria.
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$rangeInfo = $this->getGridRangeInfo($request, $this->getId());

		return $users = $userGroupDao->getUsersById(
			$filter['userGroup'],
			$context->getId(),
			$filter['searchField'],
			$filter['search']?$filter['search']:null,
			$filter['searchMatch'],
			$rangeInfo
		);
	}

	/**
	 * @copydoc GridHandler::renderFilter()
	 */
	function renderFilter($request) {
		$context = $request->getContext();
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroups = $userGroupDao->getByContextId($context->getId());
		$userGroupOptions = array('' => __('grid.user.allRoles'));
		while ($userGroup = $userGroups->next()) {
			$userGroupOptions[$userGroup->getId()] = $userGroup->getLocalizedName();
		}

		// Import PKPUserDAO to define the USER_FIELD_* constants.
		import('lib.pkp.classes.user.PKPUserDAO');
		$fieldOptions = array(
			USER_FIELD_FIRSTNAME => 'user.firstName',
			USER_FIELD_LASTNAME => 'user.lastName',
			USER_FIELD_USERNAME => 'user.username',
			USER_FIELD_EMAIL => 'user.email'
		);

		$matchOptions = array(
			'contains' => 'form.contains',
			'is' => 'form.is'
		);

		$filterData = array(
			'userGroupOptions' => $userGroupOptions,
			'fieldOptions' => $fieldOptions,
			'matchOptions' => $matchOptions
		);

		return parent::renderFilter($request, $filterData);
	}

	/**
	 * @copydoc GridHandler::getFilterSelectionData()
	 * @return array Filter selection data.
	 */
	function getFilterSelectionData($request) {
		// Get the search terms.
		$userGroup = $request->getUserVar('userGroup') ? (int)$request->getUserVar('userGroup') : null;
		$searchField = $request->getUserVar('searchField');
		$searchMatch = $request->getUserVar('searchMatch');
		$search = $request->getUserVar('search');

		return $filterSelectionData = array(
			'userGroup' => $userGroup,
			'searchField' => $searchField,
			'searchMatch' => $searchMatch,
			'search' => $search ? $search : ''
		);
	}

	/**
	 * @copydoc GridHandler::getFilterForm()
	 * @return string Filter template.
	 */
	protected function getFilterForm() {
		return 'controllers/grid/users/exportableUsers/userGridFilter.tpl';
	}

	/**
	 * Determine whether a filter form should be collapsible.
	 * @return boolean
	 */
	protected function isFilterFormCollapsible() {
		return false;
	}

	/**
	 * Define how many items this grid will start loading.
	 * @return int
	 */
	protected function getItemsNumber() {
		return 5;
	}
}

?>
