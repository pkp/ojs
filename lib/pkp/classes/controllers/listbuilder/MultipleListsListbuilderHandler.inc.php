<?php

/**
 * @file classes/controllers/listbuilder/MultipleListsListbuilderHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MultipleListsListbuilderHandler
 * @ingroup controllers_listbuilder
 *
 * @brief Class defining basic operations for handling multiple lists listbuilder UI elements
 */

import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');
import('lib.pkp.classes.controllers.listbuilder.ListbuilderList');

define_exposed('LISTBUILDER_SOURCE_TYPE_NONE', 3);

class MultipleListsListbuilderHandler extends ListbuilderHandler {

	/** @var array Set of ListbuilderList objects that this listbuilder will handle **/
	var $_lists;

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Getters and Setters
	//
	/**
	 * @see ListbuilderHandler::getTemplate()
	 */
	function getTemplate() {
		if (is_null($this->_template)) {
			$this->setTemplate('controllers/listbuilder/multipleListsListbuilder.tpl');
		}

		return $this->_template;
	}

	/**
	 * Get an array with all listbuilder lists.
	 * @return Array of ListbuilderList objects.
	 */
	function &getLists() {
		return $this->_lists;
	}


	//
	// Protected methods.
	//
	/**
	 * Add a list to listbuilder.
	 * @param $list ListbuilderList
	 */
	function addList($list) {
		assert(is_a($list, 'ListbuilderList'));

		$currentLists = $this->getLists();
		if (!is_array($currentLists)) {
			$currentLists = array();
		}
		$currentLists[$list->getId()] = $list;
		$this->_setLists($currentLists);
	}

	/**
	 * @see GridHandler::loadData($request, $filter)
	 * You should not extend or override this method.
	 * All the data loading for this component is done
	 * using ListbuilderList objects.
	 */
	protected function loadData($request, $filter) {
		// Give a chance to subclasses set data
		// on their lists.
		$this->setListsData($request, $filter);

		$data = array();
		$lists = $this->getLists();

		foreach ($lists as $list) {
			$data[$list->getId()] = $list->getData();
		}

		return $data;
	}

	/**
	 * @see ListbuilderHandler::initialize()
	 */
	function initialize($request) {
		// Basic configuration.
		// Currently this component only works with
		// these configurations, but, if needed, it's
		// easy to adapt this class to work with the other
		// listbuilders configuration.
		parent::initialize($request, false);
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_NONE);
		$this->setSaveType(LISTBUILDER_SAVE_TYPE_EXTERNAL);
	}


	//
	// Publicly (remotely) available listbuilder functions
	//
	/**
	 * Fetch the listbuilder.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function fetch($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('lists', $this->getLists());

		return parent::fetch($args, $request);
	}


	//
	// Extended protected methods.
	//
	/**
	 * @see GridHandler::initFeatures()
	 */
	protected function initFeatures($request, $args) {
		// Multiple lists listbuilder always have orderable rows.
		// We don't have any other requirement for it.
		import('lib.pkp.classes.controllers.grid.feature.OrderMultipleListsItemsFeature');
		return array(new OrderMultipleListsItemsFeature());
	}

	/**
	 * @see ListbuilderHandler::getRowInstance()
	 */
	protected function getRowInstance() {
		$row = parent::getRowInstance();

		// Currently we can't/don't need to delete a row inside multiple
		// lists listbuilder. If we need, we have to adapt this class
		// and its js handler.
		$row->setHasDeleteItemLink(false);
		return $row;
	}

	/**
	 * @see GridHandler::renderGridBodyPartsInternally()
	 */
	protected function renderGridBodyPartsInternally($request) {
		// Render the rows.
		$listsRows = array();
		$gridData = $this->getGridDataElements($request);
		foreach ($gridData as $listId => $elements) {
			$listsRows[$listId] = $this->renderRowsInternally($request, $elements);
		}

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('grid', $this);
		$templateMgr->assign('listsRows', $listsRows);

		// In listbuilders we don't use the grid body.
		return false;
	}


	//
	// Protected template methods.
	//
	/**
	 * Implement to set data on each list. This
	 * will be used by the loadData method to retrieve
	 * the listbuilder data.
	 * @param $request Request
	 * @param $filter string
	 */
	protected function setListsData($request, $filter) {
		assert(false);
	}


	//
	// Private helper methods.
	//
	/**
	 * Set the array with all listbuilder lists.
	 * @param $lists Array of ListbuilderList objects.
	 */
	private function _setLists($lists) {
		$this->_lists = $lists;
	}
}

?>
