<?php

/**
 * NativeImportDom.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Native import/export plugin DOM functions for import
 *
 * $Id$
 */

import('xml.XMLWriter');

class NativeImportDom {
	function importIssues(&$journal, &$issueNodes) {
		foreach ($issueNodes as $issueNode) {
			// FIXME: Import not yet implemented
		}
	}
}

?>
