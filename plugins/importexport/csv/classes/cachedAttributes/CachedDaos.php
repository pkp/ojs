<?php

/**
 * @file plugins/importexport/csv/classes/cachedAttributes/CachedDaos.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
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
     * Retrieves the cached GalleyDAO instance, which is used for representations.
     */
    public static function getRepresentationDao(): GalleyDAO
    {
        return self::$cachedDaos['RepresentationDAO'] ??= Application::getRepresentationDAO();
    }
}
