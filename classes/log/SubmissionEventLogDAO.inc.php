<?php

/**
 * @file classes/log/SubmissionEventLogDAO.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionEventLogDAO
 * @ingroup log
 * @see PKPSubmissionEventLogDAO
 *
 */

import('lib.pkp.classes.log.PKPSubmissionEventLogDAO');
import('classes.log.SubmissionEventLogEntry');

class SubmissionEventLogDAO extends PKPSubmissionEventLogDAO {
	/**
	 * Constructor
	 */
	function SubmissionEventLogDAO() {
		parent::PKPSubmissionEventLogDAO();
	}
}

?>
