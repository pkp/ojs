<?php
/**
 * @file controllers/form/context/ContextForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContextForm
 * @ingroup classes_controllers_form
 *
 * @brief Add OJS-specific fields to the context add/edit form.
 */
namespace APP\components\forms\context;
use \PKP\components\forms\context\PKPContextForm;
use \PKP\components\forms\FieldText;

class ContextForm extends PKPContextForm {

	/**
	 * @copydoc PKPContextForm::__construct()
	 */
	public function __construct($action, $successMessage, $locales, $baseUrl, $context) {
		parent::__construct($action, $successMessage, $locales, $baseUrl, $context);

		$this->addField(new FieldText('abbreviation', [
				'label' => __('manager.setup.journalAbbreviation'),
				'isMultilingual' => true,
				'value' => $context ? $context->getData('abbreviation') : null,
			]), ['after', 'acronym']);
	}
}
