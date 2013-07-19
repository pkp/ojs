<?php

/**
 * @file controllers/grid/admin/systemIssue/ArticleGalleyGridCellProvider.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyGridCellProvider
 * @ingroup controllers_grid_articleGalleys
 *
 * @brief Grid cell provider for the article galleys grid
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class ArticleGalleyGridCellProvider extends GridCellProvider {
	/**
	 * Constructor
	 */
	function ArticleGalleyGridCellProvider() {
		parent::GridCellProvider();
	}

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$articleGalley = $row->getData();
		$columnId = $column->getId();
		assert (is_a($articleGalley, 'ArticleGalley'));
		assert(!empty($columnId));

		switch ($columnId) {
			case 'label': return array('label' => $articleGalley->getLabel());
			case 'locale':
				$allLocales = AppLocale::getAllLocales();
				return array('label' => $allLocales[$articleGalley->getLocale()]);
			case 'publicGalleyId': return array('label' => $articleGalley->getStoredPubId('publisher-id'));
			case 'isAvailable':
			return array('status' => $articleGalley->getIsAvailable()?'completed':'new');
			default: assert(false); break;
		}
	}

	/**
	 * @copydoc GridCellProvider::getCellActions()
	 */
	function getCellActions($request, $row, $column) {
		$articleGalley =& $row->getData();
		$actionArgs = array(
				'submissionId' => $articleGalley->getSubmissionId(),
				'articleGalleyId' => $articleGalley->getId()
		);
		$submissionId = $articleGalley->getSubmissionId();
		$articleGalleyId = $articleGalley->getId();

		switch ($column->getId()) {
			case 'isAvailable':
				$router = $request->getRouter();
				$toolTip = $articleGalley->getIsAvailable() ? __('common.available') : null;
				return array(new LinkAction(
					'availableArticleGalley',
					new RemoteActionConfirmationModal(
						__($articleGalley->getIsAvailable()?'grid.issueEntry.availableGalley.removeMessage':'grid.issueEntry.availableGalley.message'),
						__('grid.issueEntry.availableGalley.title'),
						$router->url($request, null, 'grid.articleGalleys.ArticleGalleyGridHandler',
							'setAvailable', null, array('articleGalleyId' => $articleGalleyId, 'newAvailableState' => $articleGalley->getIsAvailable()?0:1, 'submissionId' => $submissionId)),
						'modal_approve'),
						__('manager.emails.disable'),
						$articleGalley->getIsAvailable()?'completed':'new',
						$toolTip
				));
			default:
				return array();
		}
	}
}

?>
