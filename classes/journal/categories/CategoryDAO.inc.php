<?php

/**
 * @file classes/journal/categories/CategoryDAO.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CategoryDAO
 * @ingroup category
 * @see Category, ControlledVocabDAO
 *
 * @brief Operations for retrieving and modifying Category objects
 */

import('lib.pkp.classes.controlledVocab.ControlledVocabDAO');

define('CATEGORY_SYMBOLIC', 'category');

class CategoryDAO extends ControlledVocabDAO {
	/**
	 * Build the Category controlled vocabulary.
	 * @return ControlledVocab
	 */
	function build() {
		return parent::build(CATEGORY_SYMBOLIC, 0, 0);
	}

	/**
	 * Get the categories list from database.
	 * @return array
	 */
	function getCategories() {
		return $this->enumerateBySymbolic(CATEGORY_SYMBOLIC, 0, 0);
	}

	/**
	 * Get an iterator of category objects.
	 * @return DAOResultFactory
	 */
	function getIterator() {
		$categoryEntryDao = $this->getEntryDAO();
		$categoryControlledVocab = $this->build();
		return $categoryEntryDao->getByControlledVocabId($categoryControlledVocab->getId());
	}

	/**
	 * Rebuild the cache.
	 */
	function rebuildCache() {
		$categoriesIterator = $this->getIterator();
		$allCategories = array();
		while ($category = $categoriesIterator->next()) {
			$allCategories[$category->getId()] = $category;
		}

		// Prepare our results array to cache
		$categories = array();

		// Add each journal's categories to the data structure
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journals = $journalDao->getAll(true);
		while ($journal = $journals->next()) {
			$selectedCategories = $journal->getSetting('categories');
			foreach ((array) $selectedCategories as $categoryId) {
				if (!isset($allCategories[$categoryId])) continue;
				if (!isset($categories[$categoryId])) $categories[$categoryId] = array(
					'category' => $allCategories[$categoryId],
					'journals' => array()
				);
				$categories[$categoryId]['journals'][] = $journal;
			}
		}

		// Save the cache file
		$fp = fopen($this->getCacheFilename(), 'w');
		if (!$fp) return false;

		fwrite($fp, serialize($categories));
		fclose($fp);
	}

	/**
	 * Get the cached set of categories, building it if necessary.
	 * @return array
	 */
	function getCache() {
		// The following lines are only for classloading purposes
		$this->getEntryDAO();
		DAORegistry::getDAO('JournalDAO');

		// Load and return the cache, building it if necessary.
		$filename = $this->getCacheFilename();
		if (!file_exists($filename)) $this->rebuildCache();
		$contents = file_get_contents($filename);
		if ($contents) return unserialize($contents);
		return null;
	}

	/**
	 * Get the cache filename.
	 * @return string
	 */
	function getCacheFilename() {
		return 'cache/fc-categories.php';
	}
}

?>
