<?php

/**
 * @file controllers/tab/settings/policies/form/OJSPoliciesForm.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSPoliciesForm
 * @ingroup controllers_tab_settings_policies_form
 *
 * @brief Form to edit policy information. (OJS-specific extensions.)
 */

import('lib.pkp.controllers.tab.settings.policies.form.PoliciesForm');

class OJSPoliciesForm extends PoliciesForm {

	/**
	 * Constructor.
	 */
	function OJSPoliciesForm($wizardMode = false) {
		$settings = array(
			'requireAuthorCompetingInterests' => 'bool',
			'requireReviewerCompetingInterests' => 'bool',
			'pubFreqPolicy' => 'string',
		);

		parent::PoliciesForm($wizardMode, $settings);
	}


	//
	// Implement template methods from Form.
	//
	/**
	 * @copydoc Form::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		return array_merge(parent::getLocaleFieldNames(), array(
			'pubFreqPolicy',
		));
	}
}

?>
