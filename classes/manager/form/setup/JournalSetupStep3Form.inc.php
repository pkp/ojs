<?php

/**
 * JournalSetupStep3Form.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 *
 * Form for Step 3 of journal setup.
 *
 * $Id$
 */

import("manager.form.setup.JournalSetupForm");

class JournalSetupStep3Form extends JournalSetupForm {
	
	function JournalSetupStep3Form() {
		parent::JournalSetupForm(
			3,
			array(
				'authorGuidelines' => 'string',
				'submissionChecklist' => 'object',
				'bibFormat' => 'string',
				'copyrightNotice' => 'string',
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
				'metaTypeExamples' => 'string'
			)
		);
	}
	
	function display() {
		$templateMgr = &TemplateManager::getManager();
		// FIXME Move this definition?
		$templateMgr->assign('bibFormatOptions',
			array(
				"APA" => "APA",
				"MLA" => "Modern Language Association (MLA)",
				"Turabian" => "Turabian",
				"CBE" => "Council of Biology Editors (CBE)",
				"BibTeX" => "BibTeX",
				"ABNT" => "ABNT 10520"
			)
		);
		parent::display();
	}
	
}

?>
