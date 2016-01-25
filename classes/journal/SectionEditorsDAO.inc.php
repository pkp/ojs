<?php

/**
 * @file classes/journal/SectionEditorsDAO.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionEditorsDAO
 * @ingroup journal
 *
 * @brief Class for DAO relating sections to editors.
 */

import('lib.pkp.classes.context.SubEditorsDAO');

class SectionEditorsDAO extends SubEditorsDAO {
	/**
	 * Constructor
	 */
	function SectionEditorsDAO() {
		parent::SubEditorsDAO();
	}
}

?>
