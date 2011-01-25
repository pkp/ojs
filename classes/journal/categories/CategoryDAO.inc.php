<?php

/**
 * @file classes/journal/category/CategoryDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CategoryDAO
 * @ingroup category
 * @see Category, ControlledVocabDAO
 *
 * @brief Operations for retrieving and modifying Category objects
 */

//$Id$


import('lib.pkp.classes.controlledVocab.ControlledVocabDAO');

define('CATEGORY_SYMBOLIC', 'category');

class CategoryDAO extends ControlledVocabDAO {
	function build() {
		return parent::build(CATEGORY_SYMBOLIC, 0, 0);
	}

	function getCategories() {
		return $this->enumerateBySymbolic(CATEGORY_SYMBOLIC, 0, 0);
	}
}

?>
