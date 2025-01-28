<?php

/**
 * @file plugins/importexport/csv/classes/cachedAttributes/CachedEntities.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CachedEntities
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief This class is responsible for retrieving cached entities such as
 * journals, user groups, genres, categories, sections, and issues.
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes;

class CachedEntities
{
    /** @var \Journal[] */
    static array $journals = [];

    /** @var int[] */
    static array $userGroupIds = [];

    /** @var \UserGroup[] */
    static array $userGroups = [];

    /** @var int[] */
    static array $genreIds = [];

    /** @var \Category[] */
    static array $categories = [];

    /** @var \Section[] */
    static array $sections = [];

    /** @var \Issue[] */
    static array $issues = [];

    /** @var \User[] */
    static array $users = [];

    /**
     * Retrieves a cached Journal by its path. Returns null if an error occurs.
	 *
	 * @return \Journal|null
     */
    static function getCachedJournal(string $journalPath)
    {
        $journalDao = CachedDaos::getJournalDao();

        return self::$journals[$journalPath] ?? self::$journals[$journalPath] = $journalDao->getByPath($journalPath);
    }

    /**
     * Retrieves a cached userGroup ID by journalId. Returns null if an error occurs.
	 *
	 * @return int|null
     */
    static function getCachedUserGroupId(string $journalPath, int $journalId)
    {
		$userGroupDao = CachedDaos::getUserGroupDao();
		$userGroup = $userGroupDao->getDefaultByRoleId($journalId, ROLE_ID_AUTHOR);

        return self::$userGroupIds[$journalPath] ?? self::$userGroupIds[$journalPath] = $userGroup->getId();
    }

	/**
	 * Retrieves a cached User by email. Returns null if an error occurs.
	 *
	 * @return \User|null
	 */
    static function getCachedUserByEmail(string $email)
    {
		$userDao = CachedDaos::getUserDao();
		$user = $userDao->getUserByEmail($email);

		return self::$users[$email] ?? self::$users[$email] = $user;
    }

	/**
	 * Retrieves a cached User by username. Returns null if an error occurs.
	 *
	 * @return \User|null
	 */
    static function getCachedUserByUsername(string $username)
    {
		$userDao = CachedDaos::getUserDao();
		$user = $userDao->getByUsername($username);

		return self::$users[$username] ?? self::$users[$username] = $user;
    }

	/**
	 * Retrieves a cached UserGroup by journalId. Returns null if an error occurs.
	 *
	 * @return \UserGroup[]
	 */
    static function getCachedUserGroupsByJournalId(int $journalId): array
    {
		$userGroupDao = CachedDaos::getUserGroupDao();
		$userGroups = $userGroupDao->getByContextId($journalId)->toArray();

		return self::$userGroups[$journalId] ?? self::$userGroups[$journalId] = $userGroups;
    }

	/**
	 * Retrieves a cached UserGroup by name and journalId. Returns null if an error occurs.
	 *
	 * @return \UserGroup|null
	 */
    static function getCachedUserGroupByName(string $name, int $journalId, string $locale)
    {
        $userGroups = self::getCachedUserGroupsByJournalId($journalId);

        foreach ($userGroups as $userGroup) {
            if (mb_strtolower($userGroup->getName($locale)) === mb_strtolower($name)) {
                return $userGroup;
            }
        }

        return null;
    }

    /**
     * Retrieves a cached genre ID by genreName and journalId. Returns null if an error occurs.
	 *
	 * @return int|null
     */
    static function getCachedGenreId(string $genreName, int $journalId)
    {
		$genreDao = CachedDaos::getGenreDao();
		$genre = $genreDao->getByKey($genreName, $journalId);

		return self::$genreIds[$genreName] ?? self::$genreIds[$genreName] = $genre->getId();
    }

    /**
     * Retrieves a cached Category by categoryName and journalId. Returns null if an error occurs.
	 *
	 * @return \Category|null
     */
    static function getCachedCategory(string $categoryName, int $journalId)
    {
		$categoryDao = CachedDaos::getCategoryDao();
		$category = $categoryDao->getByPath($categoryName, $journalId);

		return self::$categories[$categoryName] ?? self::$categories[$categoryName] = $category;
    }

    /**
     * Retrieves a cached Issue by issue data and journalId. Returns null if an error occurs.
	 *
	 * @return \Issue|null
     */
    static function getCachedIssue(object $data, int $journalId)
    {
        $customIssueDescription = "{$data->issueVolume}_{$data->issueNumber}_{$data->issueYear}";

		if (isset(self::$issues[$customIssueDescription])) {
			return self::$issues[$customIssueDescription];
		}

		$issueDao = CachedDaos::getIssueDao();

		self::$issues[$customIssueDescription] = $issueDao->getIssuesByIdentification($journalId, $data->issueVolume, $data->issueNumber, $data->issueYear)
			->toArray()[0] ?? null;

		return self::$issues[$customIssueDescription];
    }

    /**
     * Retrieves a cached Section by sectionTitle, sectionAbbrev, and journalId. Returns null if an error occurs.
	 *
	 * @return \Section|null
     */
    static function getCachedSection(string $sectionTitle, string $sectionAbbrev, string $locale, int $journalId)
    {
		$customSectionKey = "{$sectionTitle}_{$sectionAbbrev}";

		if (isset(self::$sections[$customSectionKey])) {
			return self::$sections[$customSectionKey];
		}

		$sectionDao = CachedDaos::getSectionDao();
		$sectionByAbbrev = $sectionDao->getByAbbrev($sectionAbbrev, $journalId);

		if ($sectionByAbbrev && $sectionByAbbrev->getTitle($locale) === $sectionTitle) {
			return self::$sections[$customSectionKey] = $sectionByAbbrev;
		}

		return null;
    }
}
