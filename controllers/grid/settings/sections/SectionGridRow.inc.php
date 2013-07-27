<?php

/**
 * @file controllers/grid/settings/section/SectionGridRow.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionGridRow
 * @ingroup controllers_grid_settings_section
 *
 * @brief Handle section grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class SectionGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function SectionGridRow() {
		parent::GridRow();
	}

	//
	// Overridden template methods
	//
	/*
	 * Configure the grid row
	 * @param $request PKPRequest
	 */
	function initialize($request) {
		parent::initialize($request);

		$this->setupTemplate($request);

		// Is this a new row or an existing row?
		$sectionId = $this->getId();
		if (!empty($sectionId) && is_numeric($sectionId)) {
			$router = $request->getRouter();

			import('lib.pkp.classes.linkAction.request.AjaxModal');
			$this->addAction(
				new LinkAction(
					'editSection',
					new AjaxModal(
						$router->url($request, null, null, 'editSection', null, array('sectionId' => $sectionId)),
						__('grid.action.edit'),
						'modal_edit',
						true),
					__('grid.action.edit'),
					'edit'
				)
			);

			import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
			$this->addAction(
				new LinkAction(
					'deleteSection',
					new RemoteActionConfirmationModal(
						__('manager.sections.confirmDelete'),
						__('grid.action.delete'),
						$router->url($request, null, null, 'deleteSection', null, array('sectionId' => $sectionId)), 'modal_delete'
					),
					__('grid.action.delete'),
					'delete'
				)
			);
		}
	}

	/**
	 * @copydoc PKPHandler::setupTemplate()
	 */
	function setupTemplate($request) {
		// Load manager translations. FIXME are these needed?
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_MANAGER,
			LOCALE_COMPONENT_PKP_COMMON,
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_APP_COMMON
		);
	}
}

?>
