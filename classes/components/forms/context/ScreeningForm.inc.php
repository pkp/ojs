<?php
/**
 * @file classes/components/form/context/ScreeningForm.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ScreeningForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for configuring author screening options
 */
namespace APP\components\forms\context;
use \PKP\components\forms\FormComponent;
use \PKP\components\forms\FieldHTML;

define('FORM_SCREENING', 'screening');

class ScreeningForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_SCREENING;

	/** @copydoc FormComponent::$method */
	public $method = 'PUT';

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $locales array Supported locales
	 * @param $context Context to change settings for
	 */
	public function __construct($action, $locales, $context) {
		$this->action = $action;
		$this->successMessage = __('manager.setup.authorScreening.success');

		$rules = [];
		\HookRegistry::call('Settings::Workflow::listScreeningPlugins', array(&$rules));
		if (!empty($rules)){
			$screeningPluginRules .= "<table class=\"pkpTable\">\n";
			foreach ($rules as $rule) {
				$screeningPluginRules .= "<tr><td>" . $rule . "</td></tr>\n";
			}
			$screeningPluginRules .= "</table>\n";
		}

		$this->addPage([
				'id' => 'default',
			]);
		$this->addGroup([
				'id' => 'default',
				'pageId' => 'default',
			])
			->addField(new FieldHTML('screening', [
				'description' => $screeningPluginRules,
				'groupId' => 'default',
			]));
	}

}
