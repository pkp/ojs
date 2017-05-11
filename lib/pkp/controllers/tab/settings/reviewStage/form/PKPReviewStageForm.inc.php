<?php

/**
 * @file controllers/tab/settings/reviewStage/form/PKPReviewStageForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewStageForm
 * @ingroup controllers_tab_settings_reviewStage_form
 *
 * @brief Form to edit review stage settings.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class PKPReviewStageForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 */
	function __construct($wizardMode = false, $settings = array(), $template = 'controllers/tab/settings/reviewStage/form/reviewStageForm.tpl') {
		parent::__construct(
			array_merge(
				$settings,
				array(
					'reviewGuidelines' => 'string',
					'competingInterests' => 'string',
					'numWeeksPerResponse' => 'int',
					'numWeeksPerReview' => 'int',
					'numDaysBeforeInviteReminder' => 'int',
					'numDaysBeforeSubmitReminder' => 'int',
					// 'rateReviewerOnQuality' => 'bool', /* http://github.com/pkp/pkp-lib/issues/372 */
					'showEnsuringLink' => 'bool',
					'reviewerCompetingInterestsRequired' => 'bool',
					'defaultReviewMode' => 'int',
				)
			),
			$template,
			$wizardMode
		);
	}


	//
	// Implement template methods from Form.
	//
	/**
	 * @copydoc Form::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		return array('reviewGuidelines', 'competingInterests');
	}

	/**
	 * @copydoc ContextSettingsForm::fetch()
	 */
	function fetch($request) {
		$params = array();

		// Ensuring blind review link.
		import('lib.pkp.classes.linkAction.request.ConfirmationModal');
		import('lib.pkp.classes.linkAction.LinkAction');
		$params['ensuringLink'] = new LinkAction(
			'showReviewPolicy',
			new ConfirmationModal(
				__('review.blindPeerReview'),
				__('review.ensuringBlindReview'), 'modal_information', null, null, true, MODAL_WIDTH_DEFAULT),
			__('manager.setup.reviewOptions.showBlindReviewLink')
		);

		$params['scheduledTasksDisabled'] = (Config::getVar('general', 'scheduled_tasks')) ? false : true;

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'numDaysBeforeInviteReminderValues' => array_combine(range(1, 10), range(1, 10)),
			'numDaysBeforeSubmitReminderValues' => array_combine(range(1, 10), range(1, 10))
		));

		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$templateMgr->assign('reviewMethodOptions', $reviewAssignmentDao->getReviewMethodsTranslationKeys());

		return parent::fetch($request, $params);
	}
}

?>
