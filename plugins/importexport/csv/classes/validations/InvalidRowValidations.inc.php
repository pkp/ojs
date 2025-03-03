<?php

/**
 * @file plugins/importexport/csv/classes/validations/InvalidRowValidations.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InvalidRowValidations
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Class to validate all necessary requirements for a CSV row to be valid
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Validations;

use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedEntities;

class InvalidRowValidations
{

    /** @var string[] */
    static array $coverImageAllowedTypes = ['gif', 'jpg', 'png', 'webp'];

    /**
     * Validates whether the CSV row contains all fields. Returns the reason if an error occurred,
     * or null if everything is correct.
	 *
	 * @param array $fields
	 * @param int $expectedSize
	 *
	 * @return string|null
     */
    public static function validateRowContainAllFields($fields, $expectedSize)
    {
        return count($fields) < $expectedSize
            ? __('plugins.importexport.csv.rowDoesntContainAllFields')
            : null;
    }

    /**
     * Validates whether the CSV row contains all required fields. Returns the reason if an error occurred,
     * or null if everything is correct.
	 *
	 * @param object $data
	 * @param callable $requiredFieldsValidation
	 *
	 * @return string|null
     */
    public static function validateRowHasAllRequiredFields($data, $requiredFieldsValidation)
    {
        return !$requiredFieldsValidation($data)
            ? __('plugins.importexport.csv.verifyRequiredFieldsForThisRow')
            : null;
    }


    /**
     * Validates whether the article file exists and is readable. Returns the reason if an error occurred,
     * or null if everything is correct.
	 *
	 * @param string $coverImageFilename
	 * @param string $sourceDir
	 *
	 * @return string|null
     */
    public static function validateArticleFileIsValid($coverImageFilename, $sourceDir)
    {
        $articleCoverImagePath = "{$sourceDir}/{$coverImageFilename}";

        return !is_readable($articleCoverImagePath)
            ? __('plugins.importexport.csv.invalidArticleFile')
            : null;
    }

    /**
     * Validates the article cover image. Returns the reason if an error occurred,
     * or null if everything is correct.
	 *
	 * @param string $coverImageFilename
	 * @param string $sourceDir
	 *
	 * @return string|null
     */
    public static function validateCoverImageIsValid($coverImageFilename, $sourceDir)
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
	 *
	 * @param string $suppFilenames
	 * @param string $suppLabels
	 * @param string $sourceDir
	 *
	 * @return string|null
     */
    public static function validateArticleGalleys($suppFilenames, $suppLabels, $sourceDir)
    {
        $suppFilenamesArray = explode(';', $suppFilenames);
        $suppLabelsArray = explode(';', $suppLabels);

        if (count($suppFilenamesArray) !== count($suppLabelsArray)) {
            return __('plugins.importexport.csv.invalidNumberOfLabelsAndGalleys');
        }

        foreach($suppFilenamesArray as $suppFilename) {
            $suppPath = "{$sourceDir}/{$suppFilename}";
            if (!is_readable($suppPath)) {
                return __('plugins.importexport.csv.invalidGalleyFile', ['filename' => $suppFilename]);
            }
        }

        return null;
    }

    /**
     * Validates whether the journal is valid for the CSV row. Returns the reason if an error occurred,
     * or null if everything is correct.
	 *
	 * @param \Journal|null $journal
	 * @param string $journalPath
	 *
	 * @return string|null
     */
    public static function validateJournalIsValid($journal, $journalPath)
    {
        return !$journal ? __('plugins.importexport.csv.unknownJournal', ['journalPath' => $journalPath]) : null;
    }

    /**
     * Validates if the journal supports the locale provided in the CSV row. Returns the reason if an error occurred
     * or null if everything is correct.
	 *
	 * @param \Journal|null $journal
	 * @param string $locale
	 *
	 * @return string|null
     */
    public static function validateJournalLocale($journal, $locale)
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
	 *
	 * @param int|null $genreId
	 * @param string $genreName
	 *
	 * @return string|null
     */
    public static function validateGenreIdValid($genreId, $genreName)
    {
        return !$genreId ? __('plugins.importexport.csv.noGenre', ['genreName' => $genreName]) : null;
    }

    /**
     * Validates if the user group ID is valid. Returns the reason if an error occurred
     * or null if everything is correct.
	 *
	 * @param int|null $userGroupId
	 * @param string $journalPath
	 *
	 * @return string|null
     */
    public static function validateUserGroupId($userGroupId, $journalPath)
    {
        return !$userGroupId
            ? __('plugins.importexport.csv.noAuthorGroup', ['journal' => $journalPath])
            : null;
    }

    /**
     * Validates if all user groups are valid. Returns the reason if an error occurred
     * or null if everything is correct.
	 *
	 * @param array $roles
	 * @param int $journalId
	 * @param string $locale
	 *
	 * @return string|null
     */
    public static function validateAllUserGroupsAreValid($roles, $journalId, $locale)
    {
        $userGroups = CachedEntities::getCachedUserGroupsByJournalId($journalId);

        $allDbRoles = 0;
        foreach ($roles as $role) {
            $matchingGroups = array_filter($userGroups, function($userGroup) use ($role, $locale) {
                return mb_strtolower($userGroup->getName($locale)) === mb_strtolower($role);
            });
            $allDbRoles += count($matchingGroups);
        }

        return $allDbRoles !== count($roles)
            ? __('plugins.importexport.csv.roleDoesntExist', ['role' => $role])
            : null;
    }
}
