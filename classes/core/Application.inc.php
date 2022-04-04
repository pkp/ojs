<?php

/**
 * @file classes/core/Application.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Application
 * @ingroup core
 *
 * @see PKPApplication
 *
 * @brief Class describing this application.
 *
 */

namespace APP\core;

use APP\facades\Repo;
use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\security\Role;
use PKP\submission\RepresentationDAOInterface;

define('REQUIRES_XSL', false);

define('ASSOC_TYPE_ARTICLE', PKPApplication::ASSOC_TYPE_SUBMISSION); // DEPRECATED but needed by filter framework
define('ASSOC_TYPE_GALLEY', PKPApplication::ASSOC_TYPE_REPRESENTATION);

define('ASSOC_TYPE_JOURNAL', 0x0000100);
define('ASSOC_TYPE_ISSUE', 0x0000103);
define('ASSOC_TYPE_ISSUE_GALLEY', 0x0000105);

define('CONTEXT_JOURNAL', 1);

define('METRIC_TYPE_COUNTER', 'ojs::counter');

class Application extends PKPApplication
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        if (!PKP_STRICT_MODE && !class_exists('\Application')) {
            class_alias('\APP\core\Application', '\Application');
        }

        // Add application locales
        Locale::registerPath(BASE_SYS_DIR . '/locale');
    }

    /**
     * Get the "context depth" of this application, i.e. the number of
     * parts of the URL after index.php that represent the context of
     * the current request (e.g. Journal [1], or Conference and
     * Scheduled Conference [2]).
     *
     * @return int
     */
    public function getContextDepth()
    {
        return 1;
    }

    /**
     * Get the list of context elements.
     *
     * @return array
     */
    public function getContextList()
    {
        return ['journal'];
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
        return('http://pkp.sfu.ca/ojs/xml/ojs-version.xml');
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
            'ReviewerSubmissionDAO' => 'APP\submission\reviewer\ReviewerSubmissionDAO',
            'SectionDAO' => 'APP\journal\SectionDAO',
            'SubscriptionDAO' => 'APP\subscription\SubscriptionDAO',
            'SubscriptionTypeDAO' => 'APP\subscription\SubscriptionTypeDAO',
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
            'auth',
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
     * @return ContextDAO
     */
    public static function getContextDAO()
    {
        return DAORegistry::getDAO('JournalDAO');
    }

    /**
     * Get the section DAO.
     *
     * @return SectionDAO
     */
    public static function getSectionDAO()
    {
        return DAORegistry::getDAO('SectionDAO');
    }

    /**
     * Get the representation DAO.
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
     * @return int ASSOC_TYPE_...
     */
    public static function getContextAssocType()
    {
        return ASSOC_TYPE_JOURNAL;
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
     * @param Context $context
     *
     * @return OJSPaymentManager
     */
    public static function getPaymentManager($context)
    {
        return new \APP\payment\ojs\OJSPaymentManager($context);
    }
}
