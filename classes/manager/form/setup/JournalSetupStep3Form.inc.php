<?php

/**
 * @file classes/manager/form/setup/JournalSetupStep3Form.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSetupStep3Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 3 of journal setup.
 */



import('classes.manager.form.setup.JournalSetupForm');

class JournalSetupStep3Form extends JournalSetupForm {
	/**
	 * Constructor.
	 */
	function JournalSetupStep3Form() {
		parent::JournalSetupForm(
			3,
			array(
				'authorGuidelines' => 'string',
				'submissionChecklist' => 'object',
				'copyrightNotice' => 'string',
				'includeCreativeCommons' => 'bool',
				'copyrightNoticeAgree' => 'bool',
				'requireAuthorCompetingInterests' => 'bool',
				'requireReviewerCompetingInterests' => 'bool',
				'competingInterestGuidelines' => 'string',
				'metaDiscipline' => 'bool',
				'metaDisciplineExamples' => 'string',
				'metaSubjectClass' => 'bool',
				'metaSubjectClassTitle' => 'string',
				'metaSubjectClassUrl' => 'string',
				'metaSubject' => 'bool',
				'metaSubjectExamples' => 'string',
				'metaCoverage' => 'bool',
				'metaCoverageGeoExamples' => 'string',
				'metaCoverageChronExamples' => 'string',
				'metaCoverageResearchSampleExamples' => 'string',
				'metaType' => 'bool',
				'metaTypeExamples' => 'string',
				'metaCitations' => 'bool',
				'copySubmissionAckPrimaryContact' => 'bool',
				'copySubmissionAckSpecified' => 'bool',
				'copySubmissionAckAddress' => 'string'
			)
		);

		$this->addCheck(new FormValidatorEmail($this, 'copySubmissionAckAddress', 'optional', 'user.profile.form.emailRequired'));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('authorGuidelines', 'submissionChecklist', 'copyrightNotice', 'metaDisciplineExamples', 'metaSubjectClassTitle', 'metaSubjectClassUrl', 'metaSubjectExamples', 'metaCoverageGeoExamples', 'metaCoverageChronExamples', 'metaCoverageResearchSampleExamples', 'metaTypeExamples', 'competingInterestGuidelines');
	}

	/**
	 * Display the form
	 * @param $request Request
	 * @param $dispatcher Dispatcher
	 */
	function display(&$request, &$dispatcher) {
		import('classes.mail.MailTemplate');
		$mail = new MailTemplate('SUBMISSION_ACK');
		if ($mail->isEnabled()) {
			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign('submissionAckEnabled', true);

			// Add extra style sheets required for ajax components
			// FIXME: Must be removed after OMP->OJS backporting
			$templateMgr->addStyleSheet($request->getBaseUrl().'/styles/ojs.css');

			// Add extra java script required for ajax components
			// FIXME: Must be removed after OMP->OJS backporting
			$templateMgr->addJavaScript('lib/pkp/js/grid-clickhandler.js');
			$templateMgr->addJavaScript('lib/pkp/js/modal.js');
			$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/validate/jquery.validate.min.js');
			$templateMgr->addJavaScript('lib/pkp/js/jqueryValidatorI18n.js');

			// Add the grid URLs
			$parserFilterGridUrl = $dispatcher->url($request, ROUTE_COMPONENT, null, 'grid.filter.ParserFilterGridHandler', 'fetchGrid');
			$templateMgr->assign('parserFilterGridUrl', $parserFilterGridUrl);
			$lookupFilterGridUrl = $dispatcher->url($request, ROUTE_COMPONENT, null, 'grid.filter.LookupFilterGridHandler', 'fetchGrid');
			$templateMgr->assign('lookupFilterGridUrl', $lookupFilterGridUrl);
		}

		parent::display();
	}
}

?>
