<?php

/**
 * @file classes/signoff/SignoffDAO.inc.php
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SignoffDAO
 * @ingroup signoff
 * @see Signoff
 *
 * @brief Operations for retrieving and modifying Signoff objects.
 */


import('lib.pkp.classes.signoff.PKPSignoffDAO');

class SignoffDAO extends PKPSignoffDAO {
	/**
	 * Constructor
	 */
	function SignoffDAO() {
		parent::PKPSignoffDAO();
	}
}

?>
