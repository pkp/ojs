<?php
/**
 * @file controllers/form/context/ArchivingLockssForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArchivingLockssForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for configuring the LOCKSS and CLOCKSS settings.
 */
import('lib.pkp.components.forms.FormComponent');

define('FORM_ARCHIVING_LOCKSS', 'archivingLockss');

class ArchivingLockssForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_ARCHIVING_LOCKSS;

	/** @copydoc FormComponent::$method */
	public $method = 'PUT';

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $locales array Supported locales
	 * @param $context Context Journal or Press to change settings for
	 * @param $lockssUrl string URL to the publisher manifest page for LOCKSS
	 * @param $clockssUrl string URL to the publisher manifest page for CLOCKSS
	 */
	public function __construct($action, $locales, $context, $lockssUrl, $clockssUrl) {
		$this->action = $action;
		$this->successMessage = __('manager.setup.archiving.success');
		$this->locales = $locales;

		$this->addField(new FieldOptions('enableLockss', [
				'label' => __('manager.setup.lockssTitle'),
				'description' => __('manager.setup.lockssLicenseDescription'),
				'options' => [
					[
						'value' => true,
						'label' => __('manager.setup.lockssEnable', ['lockssUrl' => $lockssUrl]),
					],
				],
				'value' => (bool) $context->getData('enableLockss'),
			]))
			->addField(new FieldOptions('enableClockss', [
				'label' => __('manager.setup.clockssTitle'),
				'description' => __('manager.setup.clockssLicenseDescription'),
				'options' => [
					[
						'value' => true,
						'label' => __('manager.setup.clockssEnable', ['clockssUrl' => $clockssUrl]),
					],
				],
				'value' => (bool) $context->getData('enableClockss'),
			]));
	}
}
