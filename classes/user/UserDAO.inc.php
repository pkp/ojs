<?php

/**
 * @file classes/user/UserDAO.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserDAO
 * @ingroup user
 * @see PKPUserDAO
 *
 * @brief Basic class describing users existing in the system.
 */

import('classes.user.User');
import('lib.pkp.classes.user.PKPUserDAO');

class UserDAO extends PKPUserDAO {

	/**
	 * Construct a new User object.
	 * @return User
	 */
	function newDataObject() {
		return new User();
	}

	/**
	 * Renew a membership to dateEnd + 1 year
	 * if the was expired, renew to current date + 1 year
	 * @param $user User
	 */
	function renewMembership($user){
		$dateEnd = $user->getSetting('dateEndMembership', 0);
		if (!$dateEnd) $dateEnd = 0;

		// if the membership is expired, extend it to today + 1 year
		$time = time();
		if ($dateEnd < $time ) $dateEnd = $time;

		$dateEnd = mktime(23, 59, 59, date("m", $dateEnd), date("d", $dateEnd), date("Y", $dateEnd)+1);
		$user->updateSetting('dateEndMembership', $dateEnd, 'date', 0);
	}
}

?>
