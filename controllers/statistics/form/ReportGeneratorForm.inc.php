<?php

/**
 * @file controllers/statistics/form/ReportGeneratorForm.inc.php
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReportGeneratorForm
 * @ingroup controllers_statistics_form
 * @see Form
 *
 * @brief Form to generate custom statistics reports.
 */

import('lib.pkp.controllers.statistics.form.PKPReportGeneratorForm');

class ReportGeneratorForm extends PKPReportGeneratorForm {

	/**
	 * @see PKPReportGeneratorForm::getFileAssocTypes()
	 */
	function getFileAssocTypes() {
		return array(ASSOC_TYPE_SUBMISSION_FILE, ASSOC_TYPE_ISSUE_GALLEY);
	}
}


