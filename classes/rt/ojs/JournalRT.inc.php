<?php

/**
 * JournalRT.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package rt.ojs
 *
 * OJS-specific Reading Tools end-user interface.
 *
 * $Id$
 */

import('rt.RT');
import('rt.ojs.RTDAO');

class JournalRT extends RT {
	var $journalId;

	// Getter/setter methods

	function getJournalId() {
		return $this->journalId;
	}

	function setJournalId($journalId) {
		$this->journalId = $journalId;
	}
}

?>
