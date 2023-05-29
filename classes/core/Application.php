<?php

/**
 * @file classes/core/Application.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Application
 *
 * @ingroup core
 *
 * @see PKPApplication
 *
 * @brief Class describing this application.
 *
 */

namespace APP\core;

use APP\facades\Repo;
use APP\journal\JournalDAO;
use APP\payment\ojs\OJSPaymentManager;
use PKP\context\Context;
use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\security\Role;
use PKP\submission\RepresentationDAOInterface;

class Application extends PKPApplication
{
    public const ASSOC_TYPE_ARTICLE = self::ASSOC_TYPE_SUBMISSION; // DEPRECATED but needed by filter framework;
    public const ASSOC_TYPE_GALLEY = self::ASSOC_TYPE_REPRESENTATION;

    public const ASSOC_TYPE_JOURNAL = 0x0000100;
    public const ASSOC_TYPE_ISSUE = 0x0000103;
    public const ASSOC_TYPE_ISSUE_GALLEY = 0x0000105;

    public const CONTEXT_JOURNAL = 1; // not used?

    public const REQUIRES_XSL = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        if (!PKP_STRICT_MODE) {
            foreach ([
                'REQUIRES_XSL',
                'ASSOC_TYPE_ARTICLE',
                'ASSOC_TYPE_GALLEY',
                'ASSOC_TYPE_JOURNAL',
                'ASSOC_TYPE_ISSUE',
                'ASSOC_TYPE_ISSUE_GALLEY',
                'CONTEXT_JOURNAL',
            ] as $constantName) {
                if (!defined($constantName)) {
                    define($constantName, constant('self::' . $constantName));
                }
            }
            if (!class_exists('\Application')) {
                class_alias('\APP\core\Application', '\Application');
            }
        }

        // Add application locales
        Locale::registerPath(BASE_SYS_DIR . '/locale');
    }

    /**
     * Get the name of the application context.
     */
    public function getContextName(): string
    {
        return 'journal';
    }

    /**
     * Get the symbolic name of this application
     *
     * @return string
     */
    public static function getName()
    {
        return 'ojs2';
    }

    /**
     * Get the locale key for the name of this application.
     *
     * @return string
     */
    public function getNameKey()
    {
        return('common.software');
    }

    /**
     * Get the URL to the XML descriptor for the current version of this
     * application.
     *
     * @return string
     */
    public function getVersionDescriptorUrl()
    {
        return 'https://pkp.sfu.ca/ojs/xml/ojs-version.xml';
    }

    /**
     * Get the map of DAOName => full.class.Path for this application.
     *
     * @return array
     */
    public function getDAOMap()
    {
        return array_merge(parent::getDAOMap(), [
            'ArticleSearchDAO' => 'APP\search\ArticleSearchDAO',
            'IndividualSubscriptionDAO' => 'APP\subscription\IndividualSubscriptionDAO',
            'InstitutionalSubscriptionDAO' => 'APP\subscription\InstitutionalSubscriptionDAO',
            'IssueGalleyDAO' => 'APP\issue\IssueGalleyDAO',
            'IssueFileDAO' => 'APP\issue\IssueFileDAO',
            'JournalDAO' => 'APP\journal\JournalDAO',
            'MetricsDAO' => 'APP\statistics\MetricsDAO',
            'OAIDAO' => 'APP\oai\ojs\OAIDAO',
            'OJSCompletedPaymentDAO' => 'APP\payment\ojs\OJSCompletedPaymentDAO',
            'SubscriptionDAO' => 'APP\subscription\SubscriptionDAO',
            'SubscriptionTypeDAO' => 'APP\subscription\SubscriptionTypeDAO',
            'TemporaryTotalsDAO' => 'APP\statistics\TemporaryTotalsDAO',
            'TemporaryItemInvestigationsDAO' => 'APP\statistics\TemporaryItemInvestigationsDAO',
            'TemporaryItemRequestsDAO' => 'APP\statistics\TemporaryItemRequestsDAO',
        ]);
    }

    /**
     * Get the list of plugin categories for this application.
     *
     * @return array
     */
    public function getPluginCategories()
    {
        return [
            // NB: Meta-data plug-ins are first in the list as this
            // will make them load (and install) first.
            // This is necessary as several other plug-in categories
            // depend on meta-data. This is a very rudimentary type of
            // dependency management for plug-ins.
            'metadata',
            'blocks',
            'gateways',
            'generic',
            'importexport',
            'oaiMetadataFormats',
            'paymethod',
            'pubIds',
            'reports',
            'themes'
        ];
    }

    /**
     * Get the top-level context DAO.
     *
     * @return JournalDAO
     */
    public static function getContextDAO()
    {
        /** @var JournalDAO */
        $dao = DAORegistry::getDAO('JournalDAO');
        return $dao;
    }

    /**
     * Get the representation DAO.
     *
     * @return \PKP\galley\DAO&RepresentationDAOInterface
     */
    public static function getRepresentationDAO(): RepresentationDAOInterface
    {
        return Repo::galley()->dao;
    }

    /**
     * Get a SubmissionSearchIndex instance.
     */
    public static function getSubmissionSearchIndex()
    {
        return new \APP\search\ArticleSearchIndex();
    }

    /**
     * Get a SubmissionSearchDAO instance.
     */
    public static function getSubmissionSearchDAO()
    {
        return DAORegistry::getDAO('ArticleSearchDAO');
    }

    /**
     * Get the stages used by the application.
     *
     * @return array
     */
    public static function getApplicationStages()
    {
        // We leave out WORKFLOW_STAGE_ID_PUBLISHED since it technically is not a 'stage'.
        return [
            WORKFLOW_STAGE_ID_SUBMISSION,
            WORKFLOW_STAGE_ID_EXTERNAL_REVIEW,
            WORKFLOW_STAGE_ID_EDITING,
            WORKFLOW_STAGE_ID_PRODUCTION
        ];
    }

    /**
     * Returns the context type for this application.
     *
     * @return int Application::ASSOC_TYPE_...
     */
    public static function getContextAssocType()
    {
        return self::ASSOC_TYPE_JOURNAL;
    }

    /**
     * Get the file directory array map used by the application.
     */
    public static function getFileDirectories()
    {
        return ['context' => '/journals/', 'submission' => '/articles/'];
    }

    /**
     * @copydoc PKPApplication::getRoleNames()
     *
     * @param null|mixed $roleIds
     */
    public static function getRoleNames($contextOnly = false, $roleIds = null)
    {
        $roleNames = parent::getRoleNames($contextOnly, $roleIds);
        if (!$roleIds || in_array(Role::ROLE_ID_SUBSCRIPTION_MANAGER, $roleIds)) {
            $roleNames[Role::ROLE_ID_SUBSCRIPTION_MANAGER] = 'user.role.subscriptionManager';
        }
        return $roleNames;
    }

    /**
     * Get the payment manager.
     *
     * @param \APP\journal\Journal $context
     *
     * @return OJSPaymentManager
     */
    public static function getPaymentManager($context)
    {
        return new OJSPaymentManager($context);
    }
}
