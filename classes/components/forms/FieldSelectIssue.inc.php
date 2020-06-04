<?php
/**
 * @file classes/components/form/FieldSelectIssue.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FieldSelectIssue
 * @ingroup classes_controllers_form
 *
 * @brief An extension of the FieldSelect for selecting an issue.
 */
namespace APP\components\forms;
use PKP\components\forms\FieldSelect;

class FieldSelectIssue extends FieldSelect {
	/** @copydoc Field::$component */
	public $component = 'field-select-issue';

	/** @var int One of the STATUS_ constants  */
	public $publicationStatus;

	/**
	 * @copydoc Field::getConfig()
	 */
	public function getConfig() {
		$config = parent::getConfig();
		$config['publicationStatus'] = $this->publicationStatus;

		$issueUrlPlaceholder = \Application::get()->getRequest()->getDispatcher()->url(
			\Application::get()->getRequest(),
			ROUTE_PAGE,
			null,
			'issue',
			'view',
			'__issueId__'
		);

		$config['publishedNoticeBase'] = __('publication.publishedIn', ['issueUrl' => $issueUrlPlaceholder]);
		$config['scheduleLabel'] = __('publication.scheduleInissue');
		$config['scheduledNoticeBase'] = __('publication.scheduledIn', ['issueUrl' => $issueUrlPlaceholder]);
		$config['unscheduledNotice'] = __('publication.unscheduledIn');
		$config['unscheduleLabel'] = __('publication.unschedule');

		return $config;
	}
}
