<?php

/**
 * @file plugins/importexport/csv/classes/cachedAttributes/CachedDaos.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CachedDaos
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief This class is responsible for retrieving cached DAOs.
 */

namespace APP\plugins\importexport\csv\classes\cachedAttributes;

use APP\core\Application;
use APP\journal\JournalDAO;
use PKP\db\DAO;
use PKP\db\DAORegistry;
use PKP\galley\DAO as GalleyDAO;
use PKP\submission\GenreDAO;
use PKP\submission\SubmissionKeywordDAO;
use PKP\submission\SubmissionSubjectDAO;
use PKP\user\InterestDAO;

class CachedDaos
{
    /** @var DAO[] */
    public static array $cachedDaos = [];

    /**
     * Retrieves the cached JournalDAO instance.
     */
    public static function getJournalDao(): JournalDAO
    {
        return self::$cachedDaos['JournalDAO'] ??= DAORegistry::getDAO('JournalDAO');
    }

    /**
     * Retrieves the cached GenreDAO instance.
     */
    public static function getGenreDao(): GenreDAO
    {
        return self::$cachedDaos['GenreDAO'] ??= DAORegistry::getDAO('GenreDAO');
    }

    /**
     * Retrieves the cached SubmissionKeywordDAO instance.
     */
    public static function getSubmissionKeywordDao(): SubmissionKeywordDAO
    {
        return self::$cachedDaos['SubmissionKeywordDAO'] ??= DAORegistry::getDAO('SubmissionKeywordDAO');
    }

    /**
     * Retrieves the cached SubmissionSubjectDAO instance.
     */
    public static function getSubmissionSubjectDao(): SubmissionSubjectDAO
    {
        return self::$cachedDaos['SubmissionSubjectDAO'] ??= DAORegistry::getDAO('SubmissionSubjectDAO');
    }

    /**
     * Retrieves the cached GalleyDAO instance, which is used for representations.
     */
    public static function getRepresentationDao(): GalleyDAO
    {
        return self::$cachedDaos['RepresentationDAO'] ??= Application::getRepresentationDAO();
    }

    /**
     * Retrieves the cached InterestDAO instance, which is used for user interests.
     */
    public static function getUserInterestDAO(): InterestDAO
    {
        return self::$cachedDaos['InterestDAO'] ??= DAORegistry::getDAO('InterestDAO');
    }
}
