<?php

/**
 * @file plugins/importexport/csv/classes/processors/CategoriesProcessor.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CategoriesProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the categories data into the database.
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Processors;

use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedDaos;
use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedEntities;

class CategoriesProcessor
{
    /**
	 * Processes data for Submission categories. If there's no category with the name provided, a new one will be created.
	 *
	 * @param string $categories
	 * @param string $locale
	 * @param int $journalId
	 * @param int $publicationId
	 *
	 * @return void
	 */
	public static function process($categories, $locale, $journalId, $publicationId)
    {
        $categoriesArray = explode(';', $categories);

        foreach ($categoriesArray as $categoryPath) {
            $lowerCategoryPath = mb_strtolower(trim($categoryPath));
            $category = CachedEntities::getCachedCategory($lowerCategoryPath, $journalId);
            $categoryDao = CachedDaos::getCategoryDao();

            if (is_null($category)) {
				/** @var \Category $category */
                $category = $categoryDao->newDataObject();
                $category->setContextId($journalId);
                $category->setTitle($categoryPath, $locale);
                $category->setParentId(null);
                $category->setSequence(REALLY_BIG_NUMBER);
                $category->setPath($lowerCategoryPath);

                $categoryDao->insertObject($category);
            }

            $categoryDao->insertPublicationAssignment($category->getId(), $publicationId);
        }
	}
}
