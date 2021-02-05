<?php
/**
 * @file classes/components/form/context/AccessForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AccessForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for configuring the terms under which a journal will
 *  allow access to its published content.
 */
namespace APP\components\forms\context;
use \PKP\components\forms\FormComponent;
use \PKP\components\forms\FieldSelect;
use \PKP\components\forms\FieldOptions;

define('FORM_ACCESS', 'access');
define('SUBSCRIPTION_OPEN_ACCESS_DELAY_MIN', '1');
define('SUBSCRIPTION_OPEN_ACCESS_DELAY_MAX', '60');

class AccessForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_ACCESS;

	/** @copydoc FormComponent::$method */
	public $method = 'PUT';

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $locales array Supported locales
	 * @param $context Context Journal or Press to change settings for
	 */
	public function __construct($action, $locales, $context) {
		$this->action = $action;
		$this->locales = $locales;

		$validDelayedOpenAccessDuration[] = ['value' => 0, 'label' => __('common.disabled')];
		for ($i=SUBSCRIPTION_OPEN_ACCESS_DELAY_MIN; $i<=SUBSCRIPTION_OPEN_ACCESS_DELAY_MAX; $i++) {
			$validDelayedOpenAccessDuration[] = [
				'value' => $i,
				'label' => __('manager.subscriptionPolicies.xMonths', array('x' => $i)),
			];
		}

		$this->addField(new FieldOptions('publishingMode', [
				'label' => __('manager.distribution.publishingMode'),
				'type' => 'radio',
				'options' => [
					['value' => PUBLISHING_MODE_OPEN, 'label' => __('manager.distribution.publishingMode.openAccess')],
					['value' => PUBLISHING_MODE_SUBSCRIPTION, 'label' => __('manager.distribution.publishingMode.subscription')],
					['value' => PUBLISHING_MODE_NONE, 'label' => __('manager.distribution.publishingMode.none')],
				],
				'value' => $context->getData('publishingMode'),
			]))
			->addField(new FieldSelect('delayedOpenAccessDuration', [
				'label' => __('about.delayedOpenAccess'),
				'options' => $validDelayedOpenAccessDuration,
				'value' => $context->getData('delayedOpenAccessDuration'),
				'showWhen' => ['publishingMode', PUBLISHING_MODE_SUBSCRIPTION],
			]))
			->addField(new FieldOptions('enableOai', [
				'label' => __('manager.setup.enableOai'),
				'description' => __('manager.setup.enableOai.description'),
				'type' => 'radio',
				'options' => [
					['value' => true, 'label' => __('common.enable')],
					['value' => false, 'label' => __('common.disable')],
				],
				'value' => $context->getData('enableOai'),
			]));
	}
}
