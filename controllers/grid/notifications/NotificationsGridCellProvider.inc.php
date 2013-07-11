<?php

/**
 * @file controllers/grid/notifications/NotificationsGridCellProvider.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationsGridCellProvider
 * @ingroup controllers_grid_notifications
 *
 * @brief Class for a cell provider that can retrieve labels from notifications
 */

import('lib.pkp.classes.controllers.grid.notifications.PKPNotificationsGridCellProvider');

class NotificationsGridCellProvider extends PKPNotificationsGridCellProvider {
	/**
	 * Constructor
	 */
	function NotificationsGridCellProvider() {
		parent::PKPNotificationsGridCellProvider();
	}

	//
	// Template methods from GridCellProvider
	//
	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$notification = $row->getData();

		switch ($column->getId()) {
			case 'title':
				switch ($notification->getAssocType()) {
					case ASSOC_TYPE_ARTICLE:
						$articleId = $notification->getAssocId();
						break;
					case ASSOC_TYPE_ARTICLE_FILE:
						$fileId = $notification->getAssocId();
						break;
					case ASSOC_TYPE_SIGNOFF:
						$signoffDao = DAORegistry::getDAO('SignoffDAO'); /* @var $signoffDao SignoffDAO */
						$signoff = $signoffDao->getById($notification->getAssocId());
						if ($signoff->getAssocType() == ASSOC_TYPE_ARTICLE) {
							$articleId = $signoff->getAssocId();
						} elseif ($signoff->getAssocType() == ASSOC_TYPE_ARTICLE_FILE) {
							$fileId = $signoff->getAssocId();
						} else {
							// Don't know of SIGNOFFs with other ASSOC types for TASKS
							assert(false);
						}
						break;
					case ASSOC_TYPE_REVIEW_ASSIGNMENT:
						$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO'); /* @var $reviewAssignmentDao ReviewAssignmentDAO */
						$reviewAssignment = $reviewAssignmentDao->getById($notification->getAssocId());
						assert(is_a($reviewAssignment, 'ReviewAssignment'));
						$articleId = $reviewAssignment->getSubmissionId();
						break;
					case ASSOC_TYPE_REVIEW_ROUND:
						$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
						$reviewRound = $reviewRoundDao->getById($notification->getAssocId());
						assert(is_a($reviewRound, 'ReviewRound'));
						$articleId = $reviewRound->getSubmissionId();
						break;
					default:
						// Don't know of other ASSOC_TYPEs for TASK notifications
						assert(false);
				}

				if (!isset($articleId) && isset($fileId)) {
					assert(is_numeric($fileId));
					$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
					$articleFile = $submissionFileDao->getLatestRevision($fileId);
					assert(is_a($articleFile, 'ArticleFile'));
					$articleId = $articleFile->getArticleId();
				}
				assert(is_numeric($articleId));
				$articleDao = DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
				$article = $articleDao->getById($articleId);
				assert(is_a($article, 'Article'));

				$title = $article->getLocalizedTitle();
				if ( empty($title) ) $title = __('common.untitled');
				return array('label' => $title);
				break;
			case 'task':
				// The action has the label
				return array('label' => '');
				break;
		}
	}
}

?>
