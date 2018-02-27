<?php
/**
 * @file controllers/grid/ExternalFeedGridHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExternalFeedGridHandler
 * @ingroup controllers_grid_externalFeed
 *
 * @brief Handle external feeds grid requests.
 */
import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.externalFeed.controllers.grid.ExternalFeedGridRow');
import('plugins.generic.externalFeed.controllers.grid.ExternalFeedGridCellProvider');

class ExternalFeedGridHandler extends GridHandler {
	/** @var ExternalFeedPlugin The external feeds plugin */
	static $plugin;

	/**
	 * Set the external feeds plugin.
	 * @param $plugin ExternalFeedPlugin
	 */
	static public function setPlugin($plugin) {
		self::$plugin = $plugin;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array('index', 'fetchGrid', 'fetchRow', 'addExternalFeed', 'editExternalFeed', 'updateExternalFeed', 'delete')
		);
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc Gridhandler::initialize()
	 */
	public function initialize($request, $args = null) {
		parent::initialize($request);
		$context = $request->getContext();
		
		// Set the grid details.
		$this->setTitle('plugins.generic.externalFeed.displayName');
		$this->setEmptyRowText('plugins.generic.externalFeed.manager.noneCreated');
		
		// Get the pages and add the data to the grid
		self::$plugin->import('classes.ExternalFeed');
		$externalFeedDao = DAORegistry::getDAO('ExternalFeedDAO');
		$feeds = $externalFeedDao->getByContextId($context->getId());
		$this->setGridDataElements($feeds);
		
		// Add grid-level actions
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$this->addAction(
			new LinkAction(
				'addExternalFeed',
				new AjaxModal(
						$router->url($request, null, null, 'addExternalFeed'),
						__('plugins.generic.externalFeed.manager.create'),
						'modal_add_item'
						),
				__('plugins.generic.externalFeed.manager.create'),
				'add_item'
			)
		);
		
		// Columns
		$cellProvider = new ExternalFeedGridCellProvider();
		$this->addColumn(new GridColumn(
			'title',
			'plugins.generic.externalFeed.manager.title',
			null,
			'controllers/grid/gridCell.tpl',
			$cellProvider
		));
		$this->addColumn(new GridColumn(
			'homepage',
			'plugins.generic.externalFeed.manager.displayHomepage',
			null,
			'controllers/grid/common/cell/selectStatusCell.tpl',
			$cellProvider
		));
		$this->addColumn(new GridColumn(
			'displayBlockAll',
			'plugins.generic.externalFeed.manager.displayBlockAll',
			null,
			'controllers/grid/common/cell/selectStatusCell.tpl', 
			$cellProvider
		));
		$this->addColumn(new GridColumn(
			'displayBlockHomepage',
			'plugins.generic.externalFeed.manager.displayBlockHomepage',
			null,
			'controllers/grid/common/cell/selectStatusCell.tpl',
			$cellProvider
		));
		
	}

	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
		return array(new OrderGridItemsFeature());
	}

	/**
	 * @copydoc GridHandler::getDataElementSequence()
	 */
	function getDataElementSequence($row) {
		return $row->getSequence();
	}

	/**
	 * @copydoc GridHandler::setDataElementSequence()
	 */
	function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence) {
		$externalFeedId = $rowId;
		$context = $request->getContext();
		$externalFeedDao = DAORegistry::getDAO('ExternalFeedDAO');
		
		if ($externalFeedId != null) {
			$feed = $externalFeedDao->getById($externalFeedId, $context->getId());
			if ($feed) {
				$feed->setSequence($newSequence);
				$externalFeedDao->updateObject($feed);
			}
		}
		
	}

	//
	// Overridden methods from GridHandler
	//
	/**
	 * @copydoc Gridhandler::getRowInstance()
	 */
	function getRowInstance() {
		return new ExternalFeedGridRow();
	}

	//
	// Public Grid Actions
	//
	/**
	 * Display the grid's containing page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$dispatcher = $request->getDispatcher();
		return $templateMgr->fetchAjax(
			'externalFeedGridContainer',
			$dispatcher->url(
				$request, ROUTE_COMPONENT, null,
				'plugins.generic.externalFeed.controllers.grid.ExternalFeedGridHandler',
				'fetchGrid'
			)
		);
	}

	/**
	 * An action to add a new external feed
	 * @param $args array Arguments to the request
	 * @param $request PKPRequest Request object
	 */
	function addExternalFeed($args, $request) {
		return $this->editExternalFeed($args, $request);
	}

	/**
	 * An action to edit an external feed
	 * @param $args array Arguments to the request
	 * @param $request PKPRequest Request object
	 * @return string Serialized JSON object
	 */
	function editExternalFeed($args, $request) {
		$feedId = $request->getUserVar('feedId');
		$context = $request->getContext();
		$this->setupTemplate($request);
		// Create and present the edit form
		import('plugins.generic.externalFeed.controllers.grid.form.ExternalFeedForm');
		$externalFeedPlugin = self::$plugin;
		$externalFeedForm = new ExternalFeedForm(self::$plugin, $context->getId(), $feedId);
		$externalFeedForm->initData();
		return new JSONMessage(true, $externalFeedForm->fetch($request));
	}

	/**
	 * Update feed
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateExternalFeed($args, $request) {
		$feedId = $request->getUserVar('feedId');
		$context = $request->getContext();
		$this->setupTemplate($request);
	
		import('plugins.generic.externalFeed.controllers.grid.form.ExternalFeedForm');
		$externalFeedPlugin = self::$plugin;
		$externalFeedForm = new ExternalFeedForm(self::$plugin, $context->getId(), $feedId);
		$externalFeedForm->readInputData($request);
	
		if ($externalFeedForm->validate()) {
			$externalFeedForm->execute();
			return DAO::getDataChangedEvent();
		} else {
			// Present any errors
			$json = new JSONMessage(true, $externalFeedForm->fetch($request));
			return $json->getString();
		}
	}

	/**
	 * Delete an external feed entry
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function delete($args, $request) {
		$feedId = $request->getUserVar('feedId');
		$context = $request->getContext();
		
		$externalFeedDao = DAORegistry::getDAO('ExternalFeedDAO');
		if ($externalFeedDao->getExternalFeedJournalId($feedId) == $context->getId()) {
			$externalFeedDao->deleteById($feedId);
		}
		
		return DAO::getDataChangedEvent();
	}

}