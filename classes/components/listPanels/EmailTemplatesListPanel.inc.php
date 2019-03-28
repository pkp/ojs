<?php
/**
 * @file components/listPanels/EmailTemplatesListPanel.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailTemplatesListPanel
 * @ingroup classes_components_listPanels
 *
 * @brief A ListPanel component for viewing and editing email templates
 */

namespace APP\components\listPanels;
use \PKP\components\listPanels\PKPEmailTemplatesListPanel;

class EmailTemplatesListPanel extends PKPEmailTemplatesListPanel {
	/**
	 * @copydoc ListPanel::getConfig()
	 */
	public function getConfig() {
    $config = parent::getConfig();
    $config['filters'][count($config['filters']) - 1]['filters'][] = [
      'param' => 'toRoleIds',
      'title' => __('default.groups.name.subscriptionManager'),
      'value' => ROLE_ID_SUBSCRIPTION_MANAGER,
    ];
    return $config;
  }
}
