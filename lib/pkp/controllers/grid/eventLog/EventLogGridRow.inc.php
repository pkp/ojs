<?php

/**
 * @file controllers/grid/eventLog/EventLogGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EventLogGridRow
 * @ingroup controllers_grid_eventLog
 *
 * @brief EventLog grid row definition
 */

// Parent class
import('lib.pkp.classes.controllers.grid.GridRow');

// Other classes used
import('lib.pkp.classes.log.SubmissionFileEventLogEntry');
import('lib.pkp.controllers.api.file.linkAction.DownloadFileLinkAction');
import('lib.pkp.controllers.grid.eventLog.linkAction.EmailLinkAction');

class EventLogGridRow extends GridRow {
	/** @var Submission **/
	var $_submission;

	/**
	 * Constructor
	 */
	function __construct($submission) {
		$this->_submission = $submission;
		parent::__construct();
	}

	//
	// Overridden methods from GridRow
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request, $template = null) {
		parent::initialize($request, $template);

		$logEntry = $this->getData(); // a Category object
		assert($logEntry != null && (is_a($logEntry, 'EventLogEntry') || is_a($logEntry, 'EmailLogEntry')));

		if (is_a($logEntry, 'EventLogEntry')) {
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
			$params = $logEntry->getParams();

			switch ($logEntry->getEventType()) {
				case SUBMISSION_LOG_FILE_REVISION_UPLOAD:
				case SUBMISSION_LOG_FILE_UPLOAD:
					$submissionFile = $submissionFileDao->getRevision($params['fileId'], $params['fileRevision']);
					if ($submissionFile) $this->addAction(new DownloadFileLinkAction($request, $submissionFile, null, __('common.download')));
					break;
			}

		} elseif (is_a($logEntry, 'EmailLogEntry')) {
			$this->addAction(
				new EmailLinkAction(
					$request,
					__('submission.event.viewEmail'),
					array(
						'submissionId' => $logEntry->getAssocId(),
						'emailLogEntryId' => $logEntry->getId(),
					)
				)
			);
		}
	}
}

?>
