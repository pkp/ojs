<?php

/**
 * @file controllers/grid/PLNStatusGridHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class PLNStatusGridHandler
 * @brief Handle PLNStatus grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.pln.controllers.grid.PLNStatusGridRow');
import('plugins.generic.pln.controllers.grid.PLNStatusGridCellProvider');

class PLNStatusGridHandler extends GridHandler {
	/** @var PLNPlugin The pln plugin */
	static $plugin;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array('fetchGrid', 'fetchRow', 'resetDeposit')
		);
		self::$plugin = PluginRegistry::getPlugin('generic', PLN_PLUGIN_NAME);
	}

	/**
	 * Set the translator plugin.
	 * @param $plugin StaticPagesPlugin
	 */
	public static function setPlugin($plugin) {
		self::$plugin = $plugin;
	}


	//
	// Overridden template methods
	//
	/**
	 * @copydoc Gridhandler::initialize()
	 */
	public function initialize($request, $args = null) {
		parent::initialize($request);

		// Set the grid title.
		$this->setTitle('plugins.generic.pln.status.deposits');

		// Set the grid instructions.
		$this->setEmptyRowText('common.none');

		// Columns
		$cellProvider = new PLNStatusGridCellProvider();
		$this->addColumn(new GridColumn('id', 'common.id', null, null, $cellProvider));
		$this->addColumn(new GridColumn('objectId', 'plugins.generic.pln.issueId', null, null, $cellProvider));
		$this->addColumn(new GridColumn('status', 'plugins.generic.pln.status.status', null, null, $cellProvider));
		$this->addColumn(new GridColumn('latestUpdate', 'plugins.generic.pln.status.latestupdate', null, null, $cellProvider));
		$this->addColumn(new GridColumn('error', 'plugins.generic.pln.displayedstatus.error', null, null, $cellProvider));
	}

	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	public function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
		return array(new PagingFeature());
	}

	/**
	 * @copydoc GridHandler::getRowInstance()
	 */
	protected function getRowInstance() {
		import('plugins.generic.pln.controllers.grid.PLNStatusGridRow');
		return new PLNStatusGridRow();
	}

	/**
	 * @copydoc GridHandler::authorize()
	 */
	public function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments, false);
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		$context = $request->getContext();
		$depositDao = DAORegistry::getDAO('DepositDAO');
		$rangeInfo = $this->getGridRangeInfo($request, $this->getId());
		return $depositDao->getByJournalId($context->getId(), $rangeInfo);
	}

	//
	// Public Grid Actions
	//
	/**
	 * Reset Deposit
	 * @param $args array
	 * @param $request PKPRequest
	 *
	 * @return string Serialized JSON object
	 */
	public function resetDeposit($args, $request) {
		$depositId = $args['depositId'];
		$depositDao = DAORegistry::getDAO('DepositDAO');
		$journal = $request->getJournal();

		if (!is_null($depositId)) {
			$deposit = $depositDao->getById($depositId, $journal->getId()); /** @var $deposit Deposit */
			
			$deposit->reset();

			$depositDao->updateObject($deposit);
		}

		return DAO::getDataChangedEvent();
	}
}
