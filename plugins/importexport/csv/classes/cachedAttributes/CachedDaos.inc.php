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

namespace PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes;

class CachedDaos
{
    /** @var array<string,\DAO> */
    static array $_cachedDaos = [];

    /**
     * Generic method to get cached DAO instances
     *
     * @param string $daoName The name of the DAO to retrieve
	 *
     * @return \DAO
     */
    private static function _getDao($daoName)
    {
        return self::$_cachedDaos[$daoName] ?? self::$_cachedDaos[$daoName] = \DAORegistry::getDAO($daoName);
    }

    /**
     * Retrieves the cached JournalDAO instance.
	 *
     * @return \JournalDAO
     */
    public static function getJournalDao()
    {
        return self::_getDao('JournalDAO');
    }

    /**
     * Retrieves the cached GenreDAO instance.
	 *
     * @return \GenreDAO
     */
    public static function getGenreDao()
    {
        return self::_getDao('GenreDAO');
    }

    /**
     * Retrieves the cached SubmissionKeywordDAO instance.
	 *
     * @return \SubmissionKeywordDAO
     */
    public static function getSubmissionKeywordDao()
    {
        return self::_getDao('SubmissionKeywordDAO');
    }

    /**
     * Retrieves the cached SubmissionSubjectDAO instance.
     *
	 * @return \SubmissionSubjectDAO
     */
    public static function getSubmissionSubjectDao()
    {
        return self::_getDao('SubmissionSubjectDAO');
    }

    /**
     * Retrieves the cached GalleyDAO instance, which is used for representations.
     *
	 * @return \ArticleGalleyDAO
     */
    public static function getArticleGalleyDao()
    {
        return self::$_cachedDaos['ArticleGalleyDAO'] ?? self::$_cachedDaos['ArticleGalleyDAO'] = \Application::getRepresentationDAO();
    }

    /**
     * Retrieves the cached InterestDAO instance, which is used for user interests.
	 *
     * @return \InterestDAO
     */
    public static function getUserInterestDao()
    {
        return self::_getDao('InterestDAO');
    }

	/**
	 * Retrieves the cached UserGroupDAO instance.
	 *
	 * @return \UserGroupDAO
	 */
	public static function getUserGroupDao()
	{
		return self::_getDao('UserGroupDAO');
	}

	/**
	 * Retrieves the cached UserDAO instance.
	 *
	 * @return \UserDAO
	 */
	public static function getUserDao()
	{
		return self::_getDao('UserDAO');
	}

	/**
	 * Retrieves the cached CategoryDAO instance.
	 *
	 * @return \CategoryDAO
	 */
	public static function getCategoryDao()
	{
		return self::_getDao('CategoryDAO');
	}

	/**
	 * Retrieves the cached IssueDAO instance.
	 *
	 * @return \IssueDAO
	 */
	public static function getIssueDao()
	{
		return self::_getDao('IssueDAO');
	}

	/**
	 * Retrieves the cached SectionDAO instance.
	 *
	 * @return \SectionDAO
	 */
	public static function getSectionDao()
	{
		return self::_getDao('SectionDAO');
	}

	/**
	 * Retrieves the cached AuthorDAO instance.
	 *
	 * @return \AuthorDAO
	 */
	public static function getAuthorDao()
	{
		return self::_getDao('AuthorDAO');
	}

	/**
	 * Retrieves the cached PublicationDAO instance.
	 *
	 * @return \PublicationDAO
	 */
	public static function getPublicationDao()
	{
		return self::_getDao('PublicationDAO');
	}

	/**
	 * Retrieves the cached SubmissionFileDAO instance.
	 *
	 * @return \SubmissionFileDAO
	 */
	public static function getSubmissionFileDao()
	{
		return self::_getDao('SubmissionFileDAO');
	}

	/**
	 * Retrieves the cached SubmissionDAO instance.
	 *
	 * @return \SubmissionDAO
	 */
	public static function getSubmissionDao()
	{
		return self::_getDao('SubmissionDAO');
	}
}
