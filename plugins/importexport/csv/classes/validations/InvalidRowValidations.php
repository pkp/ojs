<?php

/**
 * @file plugins/importexport/csv/classes/validations/InvalidRowValidations.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InvalidRowValidations
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Class to validate all necessary requirements for a CSV row to be valid
 */

namespace APP\plugins\importexport\csv\classes\validations;

use APP\journal\Journal;
use APP\plugins\importexport\csv\classes\cachedAttributes\CachedEntities;

class InvalidRowValidations
{
    /** @var string[] */
    public static array $coverImageAllowedTypes = ['gif', 'jpg', 'png', 'webp'];

    /**
     * Validates whether the CSV row contains all fields. Returns the reason if an error occurred,
     * or null if everything is correct.
     */
    public static function validateRowContainAllFields(array $fields, int $expectedSize): ?string
    {
        return count($fields) < $expectedSize
            ? __('plugins.importexport.csv.rowDoesntContainAllFields')
            : null;
    }

    /**
     * Validates whether the CSV row contains all required fields. Returns the reason if an error occurred,
     * or null if everything is correct.
     */
    public static function validateRowHasAllRequiredFields(object $data, callable $requiredFieldsValidation): ?string
    {
        return !$requiredFieldsValidation($data)
            ? __('plugins.importexport.csv.verifyRequiredFieldsForThisRow')
            : null;
    }


    /**
     * Validates whether the article file exists and is readable. Returns the reason if an error occurred,
     * or null if everything is correct.
     */
    public static function validateArticleFileIsValid(string $coverImageFilename, string $sourceDir): ?string
    {
        $articleCoverImagePath = "{$sourceDir}/{$coverImageFilename}";

        return !is_readable($articleCoverImagePath)
            ? __('plugins.importexport.csv.invalidArticleFile')
            : null;
    }

    /**
     * Validates the article cover image. Returns the reason if an error occurred,
     * or null if everything is correct.
     */
    public static function validateCoverImageIsValid(string $coverImageFilename, string $sourceDir): ?string
    {
        $articleCoverImagePath = "{$sourceDir}/{$coverImageFilename}";

        if (!is_readable($articleCoverImagePath)) {
            return __('plugins.importexport.csv.invalidBookCoverImage');
        }

        $coverImgExtension = pathinfo(mb_strtolower($coverImageFilename), PATHINFO_EXTENSION);

        if (!in_array($coverImgExtension, self::$coverImageAllowedTypes)) {
            return __('plugins.importexport.csv.invalidFileExtension');
        }

        return null;
    }

    /**
     * Perform all necessary validations for article galleys. Returns the reason if an error occurred,
     * or null if everything is correct.
     */
    public static function validateArticleGalleys(string $galleyFilenames, string $galleyLabels, string $sourceDir): ?string
    {
        $galleyFilenamesArray = explode(';', $galleyFilenames);
        $galleyLabelsArray = explode(';', $galleyLabels);

        if (count($galleyFilenamesArray) !== count($galleyLabelsArray)) {
            return __('plugins.importexport.csv.invalidNumberOfLabelsAndGalleys');
        }

        foreach ($galleyFilenamesArray as $galleyFilename) {
            $galleyPath = "{$sourceDir}/{$galleyFilename}";
            if (!is_readable($galleyPath)) {
                return __('plugins.importexport.csv.invalidGalleyFile', ['filename' => $galleyFilename]);
            }
        }

        return null;
    }

    /**
     * Validates whether the journal is valid for the CSV row. Returns the reason if an error occurred,
     * or null if everything is correct.
     */
    public static function validateJournalIsValid(?Journal $journal, string $journalPath): ?string
    {
        return !$journal ? __('plugins.importexport.csv.unknownJournal', ['journalPath' => $journalPath]) : null;
    }

    /**
     * Validates if the journal supports the locale provided in the CSV row. Returns the reason if an error occurred
     * or null if everything is correct.
     */
    public static function validateJournalLocale(Journal $journal, string $locale): ?string
    {
        $supportedLocales = $journal->getSupportedSubmissionLocales();
        if (!is_array($supportedLocales) || count($supportedLocales) < 1) {
            $supportedLocales = [$journal->getPrimaryLocale()];
        }

        return !in_array($locale, $supportedLocales)
            ? __('plugins.importexport.csv.unknownLocale', ['locale' => $locale])
            : null;
    }

    /**
     * Validates if a genre exists for the name provided in the CSV row. Returns the reason if an error occurred
     * or null if everything is correct.
     */
    public static function validateGenreIdValid(?int $genreId, string $genreName): ?string
    {
        return !$genreId ? __('plugins.importexport.csv.noGenre', ['genreName' => $genreName]) : null;
    }

    /**
     * Validates if the user group ID is valid. Returns the reason if an error occurred
     * or null if everything is correct.
     */
    public static function validateUserGroupId(?int $userGroupId, string $journalPath): ?string
    {
        return !$userGroupId
            ? __('plugins.importexport.csv.noAuthorGroup', ['journal' => $journalPath])
            : null;
    }

    public static function validateAllUserGroupsAreValid(array $roles, int $journalId, string $locale): ?string
    {
        $userGroups = CachedEntities::getCachedUserGroupsByJournalId($journalId);

        $allDbRoles = 0;
        foreach ($roles as $role) {
            $matchingGroups = array_filter($userGroups, function ($userGroup) use ($role, $locale) {
                return mb_strtolower($userGroup->getName($locale)) === mb_strtolower($role);
            });
            $allDbRoles += count($matchingGroups);
        }

        return $allDbRoles !== count($roles)
            ? __('plugins.importexport.csv.roleDoesntExist', ['role' => $role])
            : null;
    }
}
