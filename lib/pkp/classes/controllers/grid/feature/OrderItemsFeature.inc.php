<?php

/**
 * @file classes/controllers/grid/feature/OrderItemsFeature.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OrderItemsFeature
 * @ingroup controllers_grid_feature
 *
 * @brief Base class for grid widgets ordering functionality.
 *
 */

import('lib.pkp.classes.controllers.grid.feature.GridFeature');

class OrderItemsFeature extends GridFeature {
	/** @var boolean */
	var $_overrideRowTemplate;

	/** @var string */
	var $_nonOrderableItemMessage;

	/**
	 * Constructor.
	 * @param $overrideRowTemplate boolean This feature uses row
	 * actions and it will force the usage of the gridRow.tpl.
	 * If you want to use a different grid row template file, set this flag to
	 * false and make sure to use a template file that adds row actions.
	 * @param $nonOrderableItemMessage string optional A translated message to be used
	 * when user tries to move a non orderable grid item.
	 */
	function __construct($overrideRowTemplate, $nonOrderableItemMessage = null) {
		parent::__construct('orderItems');

		$this->setOverrideRowTemplate($overrideRowTemplate);
		$this->setNonOrderableItemMessage($nonOrderableItemMessage);
	}


	//
	// Getters and setters.
	//
	/**
	 * Set override row template flag.
	 * @param $customRowTemplate boolean
	 */
	function setOverrideRowTemplate($overrideRowTemplate) {
		$this->_overrideRowTemplate = $overrideRowTemplate;
	}

	/**
	 * Get override row template flag.
	 * @param $gridRow GridRow
	 * @return boolean
	 */
	function getOverrideRowTemplate(&$gridRow) {
		// Make sure we don't return the override row template
		// flag to objects that are not instances of GridRow class.
		if (get_class($gridRow) == 'GridRow') {
			return $this->_overrideRowTemplate;
		} else {
			return false;
		}
	}

	/**
	 * Set non orderable item message.
	 * @param $nonOrderableItemMessage string Message already translated.
	 */
	function setNonOrderableItemMessage($nonOrderableItemMessage) {
		$this->_nonOrderableItemMessage = $nonOrderableItemMessage;
	}

	/**
	 * Get non orderable item message.
	 * @return string Message already translated.
	 */
	function getNonOrderableItemMessage() {
		return $this->_nonOrderableItemMessage;
	}


	//
	// Extended methods from GridFeature.
	//
	/**
	 * @see GridFeature::setOptions()
	 */
	function setOptions($request, $grid) {
		parent::setOptions($request, $grid);

		$router = $request->getRouter();
		$this->addOptions(array(
			'saveItemsSequenceUrl' => $router->url($request, null, null, 'saveSequence', null, $grid->getRequestArgs())
		));
	}

	/**
	 * @see GridFeature::fetchUIElements()
	 */
	function fetchUIElements($request, $grid) {
		$templateMgr = TemplateManager::getManager($request);
		$UIElements = array();
		if ($this->isOrderActionNecessary()) {
			$templateMgr->assign('gridId', $grid->getId());
			$UIElements['orderFinishControls'] = $templateMgr->fetch('controllers/grid/feature/gridOrderFinishControls.tpl');
		}
		$nonOrderableItemMessage = $this->getNonOrderableItemMessage();
		if ($nonOrderableItemMessage) {
			$templateMgr->assign('orderMessage', $nonOrderableItemMessage);
			$UIElements['orderMessage'] = $templateMgr->fetch('controllers/grid/feature/gridOrderNonOrderableMessage.tpl');
		}

		return $UIElements;
	}


	//
	// Hooks implementation.
	//
	/**
	 * @see GridFeature::getInitializedRowInstance()
	 */
	function getInitializedRowInstance($args) {
		$row =& $args['row'];
		if ($args['grid']->getDataElementSequence($row->getData()) !== false) {
			$this->addRowOrderAction($row);
		}
	}

	/**
	 * @see GridFeature::gridInitialize()
	 */
	function gridInitialize($args) {
		$grid =& $args['grid'];

		if ($this->isOrderActionNecessary()) {
			import('lib.pkp.classes.linkAction.request.NullAction');
			$grid->addAction(
				new LinkAction(
					'orderItems',
					new NullAction(),
					__('grid.action.order'),
					'order_items'
				)
			);
		}
	}


	//
	// Protected methods.
	//
	/**
	 * Add grid row order action.
	 * @param $row GridRow
	 * @param $actionPosition int
	 * @param $rowTemplate string
	 */
	function addRowOrderAction($row) {
		if ($this->getOverrideRowTemplate($row)) {
			$row->setTemplate('controllers/grid/gridRow.tpl');
		}

		import('lib.pkp.classes.linkAction.request.NullAction');
		$row->addAction(
			new LinkAction(
				'moveItem',
				new NullAction(),
				'',
				'order_items'
			), GRID_ACTION_POSITION_ROW_LEFT
		);
	}

	//
	// Protected template methods.
	//
	/**
	 * Return if this feature will use
	 * a grid level order action. Default is
	 * true, override it if needed.
	 * @return boolean
	 */
	function isOrderActionNecessary() {
		return true;
	}
}

?>
