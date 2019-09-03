<?php
/**
 * @file classes/components/form/FieldSelectIssue.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
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
		$config['scheduledNoticeBase'] = __('publication.scheduledIn', ['issueUrl' => $issueUrlPlaceholder]);
		$config['unscheduleLabel'] = __('publication.unschedule');

		return $config;
	}
}
