<?php

/**
 * @file controllers/grid/navigationMenus/form/NavigationMenuItemsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NavigationMenuItemsForm
 * @ingroup controllers_grid_navigationMenus
 *
 * @brief Form for managers to create/edit navigationMenuItems.
 */

import('lib.pkp.controllers.grid.navigationMenus.form.PKPNavigationMenuItemsForm');
import('classes.core.ServicesContainer');

class NavigationMenuItemsForm extends PKPNavigationMenuItemsForm {

	function __construct($request, $navigationMenuItemId = null) {
		ServicesContainer::instance()
			->get('navigationMenu');

		parent::__construct($request, $navigationMenuItemId);
	}
}


