<?php
/**
 * @file plugins/pubIds/urn/classes/form/FieldUrn.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FieldUrn
 * @ingroup classes_controllers_form
 *
 * @brief A field for entering a URN and then having the check number generated.
 */
namespace Plugins\Generic\URN;

use PKP\components\forms\FieldText;

class FieldUrn extends FieldText {
	/** @copydoc Field::$component */
	public $component = 'field-urn';

	/** @var string The urnPrefix from the urn plugin sttings */
	public $urnPrefix = '';

	/**
	 * @copydoc Field::getConfig()
	 */
	public function getConfig() {
		$config = parent::getConfig();
		$config['urnPrefix'] = $this->urnPrefix;
		$config['addCheckNumberLabel'] = __('plugins.pubIds.urn.editor.addCheckNo');

		return $config;
	}
}
