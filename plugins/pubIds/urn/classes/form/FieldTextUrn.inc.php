<?php
/**
 * @file plugins/pubIds/urn/classes/form/FieldTextUrn.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FieldTextUrn
 * @ingroup classes_controllers_form
 *
 * @brief A field for entering a URN and then having the check number generated.
 */
namespace Plugins\Generic\URN;

use PKP\components\forms\FieldText;

class FieldTextUrn extends FieldText {
	/** @copydoc Field::$component */
	public $component = 'field-text-urn';

	/** @var string The urnPrefix from the urn plugin sttings */
	public $urnPrefix = '';

	public $applyCheckNumber = false;

	/**
	 * @copydoc Field::getConfig()
	 */
	public function getConfig() {
		$config = parent::getConfig();
		$config['urnPrefix'] = $this->urnPrefix;
		$config['applyCheckNumber'] = $this->applyCheckNumber;
		$config['addCheckNumberLabel'] = __('plugins.pubIds.urn.editor.addCheckNo');

		return $config;
	}
}
