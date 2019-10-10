<?php
/**
 * @file classes/components/form/publication/PublishForm.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublishForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for confirming a publication's issue before publishing.
 *   It may also be used for scheduling a publication in an issue for later
 *   publication.
 */
namespace APP\components\forms\publication;
use \PKP\components\forms\FormComponent;
use \PKP\components\forms\FieldHTML;

define('FORM_PUBLISH', 'publish');

class PublishForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_PUBLISH;

	/** @copydoc FormComponent::$method */
	public $method = 'PUT';

	/** @var Publication */
	public $publication;

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $publication Publication The publication to change settings for
	 * @param $requirementErrors array A list of pre-publication requirements that are not met.
	 */
	public function __construct($action, $publication, $requirementErrors) {
		$this->action = $action;
		$this->successMessage = __('publication.publish.success');
		$this->errors = $requirementErrors;
		$this->publication = $publication;

		// Set separate messages and buttons if publication requirements have passed
		if (empty($requirementErrors)) {
			$msg = __('publication.publish.confirmation');
			$submitLabel = __('publication.publish');
			if ($publication->getData('issueId')) {
				$issue = \Services::get('issue')->get($publication->getData('issueId'));
				if ($issue) {
					if ($issue->getData('published')) {
						$msg = __('publication.publish.confirmation.backIssue', ['issue' => $issue->getIssueIdentification()]);
					} else {
						$msg = __('publication.publish.confirmation.futureIssue', ['issue' => $issue->getIssueIdentification()]);
						$submitLabel = __('editor.submission.schedulePublication');
					}
				}
			}
			$this->addPage([
				'id' => 'default',
				'submitButton' => [
					'label' => $submitLabel,
				],
			]);
		} else {
			$msg = '<p>' . __('publication.publish.requirements') . '</p>';
			$msg .= '<ul>';
			foreach ($requirementErrors as $error) {
				$msg .= '<li>' . $error . '</li>';
			}
			$msg .= '</ul>';
			$this->addPage([
				'id' => 'default',
			]);
		}

		$this->addGroup([
				'id' => 'default',
				'pageId' => 'default',
			])
			->addField(new FieldHTML('validation', [
				'description' => $msg,
				'groupId' => 'default',
			]));
	}
}
