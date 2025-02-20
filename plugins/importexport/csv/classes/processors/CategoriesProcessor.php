<?php

/**
 * @file plugins/importexport/csv/classes/processors/CategoriesProcessor.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CategoriesProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the categories data into the database.
 */

namespace APP\plugins\importexport\csv\classes\processors;

use APP\facades\Repo;
use APP\plugins\importexport\csv\classes\cachedAttributes\CachedEntities;

class CategoriesProcessor
{
    /**
     * Processes data for Submission categories. If there's no category with the name provided, a new one will be created.
     */
    public static function process(string $categories, string $locale, int $journalId, int $publicationId): void
    {
        $categoriesArray = explode(';', $categories);

        foreach ($categoriesArray as $categoryPath) {
            $lowerCategoryPath = mb_strtolower(trim($categoryPath));
            $category = CachedEntities::getCachedCategory($lowerCategoryPath, $journalId);

            $categoryDao = Repo::category()->dao;

            if (is_null($category)) {
                $category = $categoryDao->newDataObject();
                $category->setContextId($journalId);
                $category->setTitle($categoryPath, $locale);
                $category->setParentId(null);
                $category->setSequence(REALLY_BIG_NUMBER);
                $category->setPath($lowerCategoryPath);

                $categoryDao->insert($category);
            }

            $categoryDao->insertPublicationAssignment($category->getId(), $publicationId);
        }
    }
}
