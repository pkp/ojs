<?php

/**
 * @file classes/manager/form/setup/JournalSetupStep3Form.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
				'includeCopyrightStatement' => 'bool',
				'licenseURL' => 'string',
				'includeLicense' => 'bool',
				'copyrightNoticeAgree' => 'bool',
				'copyrightHolderType' => 'string',
				'copyrightHolderOther' => 'string',
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
				'metaCitationOutputFilterId' => 'int',
				'copySubmissionAckPrimaryContact' => 'bool',
				'copySubmissionAckSpecified' => 'bool',
				'copySubmissionAckAddress' => 'string'
			)
		);

		$this->addCheck(new FormValidatorEmail($this, 'copySubmissionAckAddress', 'optional', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorLocaleURL($this, 'metaSubjectClassUrl', 'optional', 'manager.setup.subjectClassificationURLValid'));
		$this->addCheck(new FormValidatorURL($this, 'licenseURL', 'optional', 'submission.licenseURLValid'));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('authorGuidelines', 'submissionChecklist', 'copyrightNotice', 'metaDisciplineExamples', 'metaSubjectClassTitle', 'metaSubjectClassUrl', 'metaSubjectExamples', 'metaCoverageGeoExamples', 'metaCoverageChronExamples', 'metaCoverageResearchSampleExamples', 'metaTypeExamples', 'competingInterestGuidelines', 'copyrightHolderOther');
	}

	/**
	 * Display the form
	 * @param $request Request
	 * @param $dispatcher Dispatcher
	 */
	function display($request, $dispatcher) {
		$templateMgr =& TemplateManager::getManager($request);
		// Add extra style sheets required for ajax components
		// FIXME: Must be removed after OMP->OJS backporting
		$templateMgr->addStyleSheet($request->getBaseUrl().'/styles/ojs.css');

		// Add extra java script required for ajax components
		// FIXME: Must be removed after OMP->OJS backporting
		$templateMgr->addJavaScript('lib/pkp/js/functions/modal.js');
		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/validate/jquery.validate.min.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/jqueryValidatorI18n.js');

		import('classes.mail.MailTemplate');
		$mail = new MailTemplate('SUBMISSION_ACK');
		if ($mail->isEnabled()) {
			$templateMgr->assign('submissionAckEnabled', true);
		}

		// Citation editor filter configuration
		//
		// 1) Check whether PHP5 is available.
		if (!checkPhpVersion('5.0.0')) {
			AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);
			$citationEditorError = 'submission.citations.editor.php5Required';
		} else {
			$citationEditorError = null;
		}
		$templateMgr->assign('citationEditorError', $citationEditorError);

		if (!$citationEditorError) {
			// 2) Add the filter grid URLs
			$parserFilterGridUrl = $dispatcher->url($request, ROUTE_COMPONENT, null, 'grid.filter.ParserFilterGridHandler', 'fetchGrid');
			$templateMgr->assign('parserFilterGridUrl', $parserFilterGridUrl);
			$lookupFilterGridUrl = $dispatcher->url($request, ROUTE_COMPONENT, null, 'grid.filter.LookupFilterGridHandler', 'fetchGrid');
			$templateMgr->assign('lookupFilterGridUrl', $lookupFilterGridUrl);

			// 3) Create a list of all available citation output filters.
			$router =& $request->getRouter();
			$journal =& $router->getContext($request);
			$filterDao =& DAORegistry::getDAO('FilterDAO'); /* @var $filterDao FilterDAO */
			$metaCitationOutputFilterObjects =& $filterDao->getObjectsByGroup('nlm30-element-citation=>plaintext', $journal->getId());
			foreach($metaCitationOutputFilterObjects as $metaCitationOutputFilterObject) {
				$metaCitationOutputFilters[$metaCitationOutputFilterObject->getId()] = $metaCitationOutputFilterObject->getDisplayName();
			}
			$templateMgr->assign_by_ref('metaCitationOutputFilters', $metaCitationOutputFilters);
		}

		$templateMgr->assign('ccLicenseOptions', Application::getCCLicenseOptions());
		parent::display($request, $dispatcher);
	}
}

?>
