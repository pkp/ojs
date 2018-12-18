<?php
/**
 * @file classes/components/form/context/AppearanceSetupForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AppearanceSetupForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for general website appearance setup, such as uploading
 *  a logo.
 */
namespace APP\components\forms\context;
use \PKP\components\forms\context\PKPAppearanceSetupForm;
use \PKP\components\forms\FieldUploadImage;

class AppearanceSetupForm extends PKPAppearanceSetupForm {

	/**
	 * @copydoc PKPAppearanceSetupForm::__construct()
	 */
	public function __construct($action, $locales, $context, $baseUrl, $temporaryFileApiUrl) {
		parent::__construct($action, $locales, $context, $baseUrl, $temporaryFileApiUrl);

		$this->addField(new FieldUploadImage('journalThumbnail', [
				'label' => __('manager.setup.journalThumbnail'),
				'tooltip' => __('manager.setup.journalThumbnail.description'),
				'isMultilingual' => true,
				'value' => $context->getData('journalThumbnail'),
				'baseUrl' => $baseUrl,
				'options' => [
					'url' => $temporaryFileApiUrl,
				],
			]), ['after', 'pageHeaderLogoImage']);
	}
}
