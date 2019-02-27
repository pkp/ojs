<?php
/**
 * @file classes/components/form/context/AccessForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
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
use \PKP\components\forms\FieldRichTextarea;

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
		$this->successMessage = __('manager.distribution.publishingMode.success');
		$this->locales = $locales;

		$validDelayedOpenAccessDuration[] = ['value' => 0, 'label' => __('common.disabled')]; 
		for ($i=SUBSCRIPTION_OPEN_ACCESS_DELAY_MIN; $i<=SUBSCRIPTION_OPEN_ACCESS_DELAY_MAX; $i++) {
			$validDelayedOpenAccessDuration[] = [
				'value' => $i,
				'label' => __('manager.subscriptionPolicies.xMonths', array('x' => $i)),
			];
		}

		$this->addGroup([
				'id' => 'publishingMode',
				'label' => __('manager.distribution.publishingMode'),
			])
			->addField(new FieldOptions('publishingMode', [
				'label' => __('manager.distribution.publishingMode'),
				'type' => 'radio',
				'options' => [
					['value' => PUBLISHING_MODE_OPEN, 'label' => __('manager.distribution.publishingMode.openAccess')],
					['value' => PUBLISHING_MODE_SUBSCRIPTION, 'label' => __('manager.distribution.publishingMode.subscription')],
					['value' => PUBLISHING_MODE_NONE, 'label' => __('manager.distribution.publishingMode.none')],
				],
				'groupId' => 'publishingMode',
				'value' => $context->getData('publishingMode'),
			]))
			->addGroup([
				'id' => 'delayedOpenAccess',
				'label' => __('about.delayedOpenAccess'),
				'description' => __('manager.subscriptionPolicies.delayedOpenAccessDescription'),
				'showWhen' => ['publishingMode', PUBLISHING_MODE_SUBSCRIPTION],
			])
			->addField(new FieldSelect('delayedOpenAccessDuration', [
				'label' => __('manager.subscriptionTypes.duration'),
				'options' => $validDelayedOpenAccessDuration,
				'groupId' => 'delayedOpenAccess',
				'value' => $context->getData('delayedOpenAccessDuration'),
			]))
			->addField(new FieldRichTextarea('delayedOpenAccessPolicy', [
				'label' => __('about.delayedOpenAccess'),
				'description' => __('manager.subscriptionPolicies.delayedOpenAccessPolicyDescription'),
				'isMultilingual' => true,
				'groupId' => 'delayedOpenAccess',
				'value' => $context->getData('delayedOpenAccessPolicy'),
			]))		
			->addGroup([
				'id' => 'enableOai',
				'label' => __('manager.setup.enableOai'),
			])		
			->addField(new FieldOptions('enableOai', [
				'label' => __('manager.setup.enableOai'),
				'description' => __('manager.setup.enableOai.description'),
				'type' => 'radio',
				'options' => [
					['value' => true, 'label' => __('common.enable')],
					['value' => false, 'label' => __('common.disable')],
				],
				'groupId' => 'enableOai',
				'value' => $context->getData('enableOai'),
			]));
	}
}
