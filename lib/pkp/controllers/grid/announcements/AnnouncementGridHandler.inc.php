<?php

/**
 * @file controllers/grid/announcements/AnnouncementGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementGridHandler
 * @ingroup controllers_grid_announcements
 *
 * @brief Handle announcements grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');
import('lib.pkp.classes.controllers.grid.DateGridCellProvider');

class AnnouncementGridHandler extends GridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridHandler::authorize()
	 * @param $requireAnnouncementsEnabled Iff true, allow access only if context settings enable announcements
	 */
	function authorize($request, &$args, $roleAssignments, $requireAnnouncementsEnabled = true) {

		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));

		$returner = parent::authorize($request, $args, $roleAssignments);

		// Ensure announcements are enabled.
		$context = $request->getContext();
		if ($requireAnnouncementsEnabled && !$context->getSetting('enableAnnouncements')) {
			return false;
		}

		$announcementId = $request->getUserVar('announcementId');
		if ($announcementId) {
			// Ensure announcement is valid and for this context
			$announcementDao = DAORegistry::getDAO('AnnouncementDAO'); /* @var $announcementDao AnnouncementDAO */
			if ($announcementDao->getAnnouncementAssocType($announcementId) != $context->getAssocType() &&
				$announcementDao->getAnnouncementAssocId($announcementId) != $context->getId()) {
				return false;
			}
		}

		return $returner;
	}

	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Set the no items row text
		$this->setEmptyRowText('announcement.noneExist');

		$context = $request->getContext();

		// Columns
		import('lib.pkp.controllers.grid.announcements.AnnouncementGridCellProvider');
		$announcementCellProvider = new AnnouncementGridCellProvider();
		$this->addColumn(
			new GridColumn('title',
				'common.title',
				null,
				null,
				$announcementCellProvider,
				array('width' => 60)
			)
		);

		$this->addColumn(
			new GridColumn('type',
				'common.type',
				null,
				null,
				$announcementCellProvider
			)
		);

		$dateCellProvider = new DateGridCellProvider(
			new DataObjectGridCellProvider(),
			Config::getVar('general', 'date_format_short')
		);
		$this->addColumn(
			new GridColumn(
				'datePosted',
				'announcement.posted',
				null,
				null,
				$dateCellProvider
			)
		);
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		$context = $request->getContext();
		$announcementDao = DAORegistry::getDAO('AnnouncementDAO');
		$rangeInfo = $this->getGridRangeInfo($request, $this->getId());
		return $announcementDao->getAnnouncementsNotExpiredByAssocId($context->getAssocType(), $context->getId(), $rangeInfo);
	}


	//
	// Public grid actions.
	//
	/**
	 * Load and fetch the announcement form in read-only mode.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function moreInformation($args, $request) {
		$announcementId = (int)$request->getUserVar('announcementId');
		$context = $request->getContext();
		$contextId = $context->getId();

		import('lib.pkp.controllers.grid.announcements.form.AnnouncementForm');
		$announcementForm = new AnnouncementForm($contextId, $announcementId, true);

		$announcementForm->initData($args, $request);

		return new JSONMessage(true, $announcementForm->fetch($request));
	}
}

?>
