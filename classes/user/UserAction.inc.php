<?php

/**
 * @file classes/user/UserAction.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserAction
 * @ingroup user
 * @see User
 *
 * @brief UserAction class.
 */

import('lib.pkp.classes.user.PKPUserAction');

class UserAction extends PKPUserAction {
	/**
	 * @copydoc PKPUserAction::mergeUsers()
	 */
	public function mergeUsers($oldUserId, $newUserId) {
		parent::mergeUsers($oldUserId, $newUserId);
	}
}

