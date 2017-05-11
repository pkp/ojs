<?php

/**
 * @file controllers/grid/settings/metadata/MetadataGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataGridHandler
 * @ingroup controllers_grid_settings_metadata
 *
 * @brief Handle metadata grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.controllers.grid.settings.metadata.MetadataGridCellProvider');

class MetadataGridHandler extends GridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Implement template methods from PKPHandler.
	//
	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Load user-related translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_PKP_MANAGER,
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_PKP_READER
		);

		// Basic grid configuration.
		$this->setTitle('submission.metadata');

		$cellProvider = new MetadataGridCellProvider($request->getContext());

		// Field name.
		$this->addColumn(
			new GridColumn(
				'name',
				'common.name',
				null,
				'controllers/grid/gridCell.tpl',
				$cellProvider,
				array('width' => 60)
			)
		);

		$this->addColumn(
			new GridColumn(
				'workflow',
				'common.enabled',
				null,
				'controllers/grid/common/cell/selectStatusCell.tpl',
				$cellProvider,
				array('alignment' => 'center')
			)
		);

		$this->addColumn(
			new GridColumn(
				'submission',
				'manager.setup.metadata.submission',
				null,
				'controllers/grid/common/cell/selectStatusCell.tpl',
				$cellProvider,
				array('alignment' => 'center')
			)
		);
	}

	/**
	 * Get the list of configurable metadata fields.
	 */
	static function getNames() {
		return array(
			'coverage' => array('name' => __('rt.metadata.dublinCore.coverage')),
			'languages' => array('name' => __('rt.metadata.dublinCore.language')),
			'rights' => array('name' => __('rt.metadata.dublinCore.rights')),
			'source' => array('name' => __('rt.metadata.dublinCore.source')),
			'subject' => array('name' => __('rt.metadata.dublinCore.subject')),
			'type' => array('name' => __('rt.metadata.dublinCore.type')),
			'disciplines' => array('name' => __('rt.metadata.pkp.discipline')),
			'keywords' => array('name' => __('rt.metadata.pkp.subject')),
			'agencies' => array('name' => __('submission.supportingAgencies')),
			'references' => array('name' => __('submission.citations')),
		);
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		return $this->getNames();
	}

	/**
	 * @see GridHandler::getJSHandler()
	 */
	public function getJSHandler() {
		return '$.pkp.controllers.grid.settings.metadata.MetadataGridHandler';
	}
}

?>
