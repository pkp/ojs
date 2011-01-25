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

	function rebuildCache() {
		// Read the full set of categories into an associative array
		$categoryEntryDao =& $this->getEntryDAO();
		$categoryControlledVocab =& $this->build();
		$categoriesIterator =& $categoryEntryDao->getByControlledVocabId($categoryControlledVocab->getId());
		$allCategories = array();
		while ($category =& $categoriesIterator->next()) {
			$allCategories[$category->getId()] =& $category;
			unset($category);
		}

		// Prepare our results array to cache
		$categories = array();

		// Add each journal's categories to the data structure
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journals =& $journalDao->getJournals(true);
		while ($journal =& $journals->next()) {
			$selectedCategories = $journal->getSetting('categories');
			foreach ((array) $selectedCategories as $categoryId) {
				if (!isset($allCategories[$categoryId])) continue;
				if (!isset($categories[$categoryId])) $categories[$categoryId] = array(
					'category' => $allCategories[$categoryId],
					'journals' => array()
				);
				$categories[$categoryId]['journals'][] =& $journal;
			}
			unset($journal);
		}

		// Save the cache file
		$fp = fopen($this->getCacheFilename(), 'w');
		if (!$fp) return false;

		fwrite($fp, serialize($categories));
		fclose($fp);
	}

	function getCache() {
		// The following line is only for classloading purposes
		$categoryEntryDao =& $this->getEntryDAO();
		$journalDao =& DAORegistry::getDAO('JournalDAO');

		// Load and return the cache
		$contents = file_get_contents($this->getCacheFilename());
		if ($contents) return unserialize($contents);
		return null;
	}

	function getCacheFilename() {
		return 'cache/fc-categories.php';
	}
}

?>
