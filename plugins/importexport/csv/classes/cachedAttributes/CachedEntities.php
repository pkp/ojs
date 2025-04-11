<?php

/**
 * @file plugins/importexport/csv/classes/cachedAttributes/CachedEntities.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CachedEntities
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief This class is responsible for retrieving cached entities such as
 * journals, user groups, genres, categories, sections, and issues.
 */

namespace APP\plugins\importexport\csv\classes\cachedAttributes;

use APP\facades\Repo;
use APP\issue\Issue;
use APP\journal\Journal;
use APP\section\Section;
use PKP\category\Category;
use PKP\security\Role;
use PKP\user\User;
use PKP\userGroup\UserGroup;

class CachedEntities
{
    /** @var Journal[] */
    public static array $journals = [];

    /** @var int[] */
    public static array $userGroupIds = [];

    /** @var UserGroup[] */
    public static array $userGroups = [];

    /** @var int[] */
    public static array $genreIds = [];

    /** @var Category[] */
    public static array $categories = [];

    /** @var Section[] */
    public static array $sections = [];

    /** @var Issue[] */
    public static array $issues = [];

    /** @var User[] */
    public static array $users = [];

    /**
     * Retrieves a cached Journal by its path. Returns null if an error occurs.
     */
    public static function getCachedJournal(string $journalPath): ?Journal
    {
        $journalDao = CachedDaos::getJournalDao();

        return self::$journals[$journalPath] ??= $journalDao->getByPath($journalPath);
    }

    /**
     * Retrieves a cached userGroup ID by journalId. Returns null if an error occurs.
     */
    public static function getCachedUserGroupId(string $journalPath, int $journalId): ?int
    {
        return self::$userGroupIds[$journalPath] ??= Repo::userGroup()
            ->getByRoleIds([Role::ROLE_ID_AUTHOR], $journalId)
            ->first()?->getId();
    }

    public static function getCachedUserByEmail(string $email): ?User
    {
        return self::$users[$email] ??= Repo::user()->getByEmail($email);
    }

    public static function getCachedUserByUsername(string $username): ?User
    {
        return self::$users[$username] ??= Repo::user()->getByUsername($username);
    }

    public static function getCachedUserGroupsByJournalId(int $journalId): array
    {
        return self::$userGroups[$journalId] ??= Repo::userGroup()->getCollector()
            ->filterByContextIds([$journalId])
            ->getMany()
            ->toArray();
    }

    public static function getCachedUserGroupByName(string $name, int $journalId, string $locale): ?UserGroup
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
     */
    public static function getCachedGenreId(string $genreName, int $journalId): ?int
    {
        return self::$genreIds[$genreName] ??= CachedDaos::getGenreDao()
            ->getByKey($genreName, $journalId)
            ?->getId();
    }

    /**
     * Retrieves a cached Category by categoryName and journalId. Returns null if an error occurs.
     */
    public static function getCachedCategory(string $categoryName, int $journalId): ?Category
    {
        $result = Repo::category()->getCollector()
            ->filterByContextIds([$journalId])
            ->filterByPaths([$categoryName])
            ->limit(1)
            ->getMany()
            ->toArray();

        return self::$categories[$categoryName] ??= (array_values($result)[0] ?? null);
    }

    /**
     * Retrieves a cached Issue by issue data and journalId. Returns null if an error occurs.
     */
    public static function getCachedIssue(object $data, int $journalId): ?Issue
    {
        $customIssueDescription = "{$data->issueVolume}_{$data->issueNumber}_{$data->issueYear}";
        $result = Repo::issue()->getCollector()
            ->filterByContextIds([$journalId])
            ->filterByNumbers([$data->issueNumber])
            ->filterByVolumes([$data->issueVolume])
            ->filterByYears([$data->issueYear])
            ->limit(1)
            ->getMany()
            ->toArray();

        return self::$issues[$customIssueDescription] ??= (array_values($result)[0] ?? null);
    }

    /**
     * Retrieves a cached Section by sectionTitle, sectionAbbrev, and journalId. Returns null if an error occurs.
     */
    public static function getCachedSection(string $sectionTitle, string $sectionAbbrev, int $journalId): ?Section
    {
        $result = Repo::section()->getCollector()
            ->filterByContextIds([$journalId])
            ->filterByTitles([$sectionTitle])
            ->filterByAbbrevs([$sectionAbbrev])
            ->limit(1)
            ->getMany()
            ->toArray();

        return self::$sections["{$sectionTitle}_{$sectionAbbrev}"] ??= (array_values($result)[0] ?? null);
    }
}
