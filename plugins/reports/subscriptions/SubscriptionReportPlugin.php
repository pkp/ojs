<?php

/**
 * @file plugins/reports/subscriptions/SubscriptionReportPlugin.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionReportPlugin
 *
 * @brief Subscription report plugin
 */

namespace APP\plugins\reports\subscriptions;

use APP\facades\Repo;
use APP\subscription\IndividualSubscriptionDAO;
use APP\subscription\InstitutionalSubscriptionDAO;
use APP\subscription\SubscriptionTypeDAO;
use PKP\core\PKPString;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\plugins\ReportPlugin;

class SubscriptionReportPlugin extends ReportPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        $this->addLocaleData();
        return $success;
    }

    /**
     * Get the name of this plugin. The name must be unique within
     * its category.
     *
     * @return string name of plugin
     */
    public function getName()
    {
        return 'SubscriptionReportPlugin';
    }

    /**
     * Get the display name of this plugin.
     *
     * @return string display name of plugin
     */
    public function getDisplayName()
    {
        return __('plugins.reports.subscriptions.displayName');
    }

    /**
     * Get the description text for this plugin.
     *
     * @return string description text for this plugin
     */
    public function getDescription()
    {
        return __('plugins.reports.subscriptions.description');
    }

    /**
     * @copydoc ReportPlugin::display()
     */
    public function display($args, $request)
    {
        $journal = $request->getContext();
        $journalId = $journal->getId();
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $individualSubscriptionDao */
        $institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /** @var InstitutionalSubscriptionDAO $institutionalSubscriptionDao */
        $countries = Locale::getCountries();

        header('content-type: text/comma-separated-values');
        header('content-disposition: attachment; filename=subscriptions-' . date('Ymd') . '.csv');
        $fp = fopen('php://output', 'wt');
        //Add BOM (byte order mark) to fix UTF-8 in Excel
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Columns for individual subscriptions
        $columns = [__('subscriptionManager.individualSubscriptions')];
        fputcsv($fp, array_values($columns));

        $columnsCommon = [
            'subscription_id' => __('common.id'),
            'status' => __('subscriptions.status'),
            'type' => __('common.type'),
            'format' => __('subscriptionTypes.format'),
            'date_start' => __('manager.subscriptions.dateStart'),
            'date_end' => __('manager.subscriptions.dateEnd'),
            'membership' => __('manager.subscriptions.membership'),
            'reference_number' => __('manager.subscriptions.referenceNumber'),
            'notes' => __('common.notes')
        ];

        $columnsIndividual = [
            'name' => __('user.name'),
            'mailing_address' => __('common.mailingAddress'),
            'country' => __('common.country'),
            'email' => __('user.email'),
            'phone' => __('user.phone'),
        ];

        $columns = array_merge($columnsCommon, $columnsIndividual);

        // Write out individual subscription column headings to file
        fputcsv($fp, array_values($columns));

        // Iterate over individual subscriptions and write out each to file
        $individualSubscriptions = $individualSubscriptionDao->getByJournalId($journalId);
        while ($subscription = $individualSubscriptions->next()) {
            $user = Repo::user()->get($subscription->getUserId(), true);
            $subscriptionType = $subscriptionTypeDao->getById($subscription->getTypeId());

            foreach ($columns as $index => $junk) {
                switch ($index) {
                    case 'subscription_id':
                        $columns[$index] = $subscription->getId();
                        break;
                    case 'status':
                        $columns[$index] = $subscription->getStatusString();
                        break;
                    case 'type':
                        $columns[$index] = $subscription->getSubscriptionTypeSummaryString();
                        break;
                    case 'format':
                        $columns[$index] = __($subscriptionType->getFormatString());
                        break;
                    case 'date_start':
                        $columns[$index] = $subscription->getDateStart();
                        break;
                    case 'date_end':
                        $columns[$index] = $subscription->getDateEnd();
                        break;
                    case 'membership':
                        $columns[$index] = $subscription->getMembership();
                        break;
                    case 'reference_number':
                        $columns[$index] = $subscription->getReferenceNumber();
                        break;
                    case 'notes':
                        $columns[$index] = PKPString::html2text($subscription->getNotes());
                        break;
                    case 'name':
                        $columns[$index] = $user->getFullName();
                        break;
                    case 'mailing_address':
                        $columns[$index] = PKPString::html2text($user->getMailingAddress());
                        break;
                    case 'country':
                        $userCountry = $user->getCountry();
                        $country = null;
                        if ($userCountry) {
                            $country = $countries->getByAlpha2($user->getCountry());
                        }
                        $columns[$index] = $country ? $country->getLocalName() : '';
                        break;
                    case 'email':
                        $columns[$index] = $user->getEmail();
                        break;
                    case 'phone':
                        $columns[$index] = $user->getPhone();
                        break;
                    default:
                        $columns[$index] = '';
                }
            }

            fputcsv($fp, $columns);
        }

        // Columns for institutional subscriptions
        $columns = [''];
        fputcsv($fp, array_values($columns));

        $columns = [__('subscriptionManager.institutionalSubscriptions')];
        fputcsv($fp, array_values($columns));

        $columnsInstitution = [
            'institution_name' => __('manager.subscriptions.institutionName'),
            'institution_mailing_address' => __('plugins.reports.subscriptions.institutionMailingAddress'),
            'domain' => __('manager.subscriptions.domain'),
            'ip_ranges' => __('plugins.reports.subscriptions.ipRanges'),
            'contact' => __('manager.subscriptions.contact'),
            'mailing_address' => __('common.mailingAddress'),
            'country' => __('common.country'),
            'email' => __('user.email'),
            'phone' => __('user.phone'),
        ];

        $columns = array_merge($columnsCommon, $columnsInstitution);

        // Write out institutional subscription column headings to file
        fputcsv($fp, array_values($columns));

        // Iterate over institutional subscriptions and write out each to file
        $institutionalSubscriptions = $institutionalSubscriptionDao->getByJournalId($journalId);
        while ($subscription = $institutionalSubscriptions->next()) {
            $user = Repo::user()->get($subscription->getUserId(), true);
            $subscriptionType = $subscriptionTypeDao->getById($subscription->getTypeId());
            $institution = Repo::institution()->get($subscription->getInstitutionId());

            foreach ($columns as $index => $junk) {
                switch ($index) {
                    case 'subscription_id':
                        $columns[$index] = $subscription->getId();
                        break;
                    case 'status':
                        $columns[$index] = $subscription->getStatusString();
                        break;
                    case 'type':
                        $columns[$index] = $subscription->getSubscriptionTypeSummaryString();
                        break;
                    case 'format':
                        $columns[$index] = __($subscriptionType->getFormatString());
                        break;
                    case 'date_start':
                        $columns[$index] = $subscription->getDateStart();
                        break;
                    case 'date_end':
                        $columns[$index] = $subscription->getDateEnd();
                        break;
                    case 'membership':
                        $columns[$index] = $subscription->getMembership();
                        break;
                    case 'reference_number':
                        $columns[$index] = $subscription->getReferenceNumber();
                        break;
                    case 'notes':
                        $columns[$index] = PKPString::html2text($subscription->getNotes());
                        break;
                    case 'institution_name':
                        $columns[$index] = $institution->getLocalizedName();
                        break;
                    case 'institution_mailing_address':
                        $columns[$index] = PKPString::html2text($subscription->getInstitutionMailingAddress());
                        break;
                    case 'domain':
                        $columns[$index] = $subscription->getDomain();
                        break;
                    case 'ip_ranges':
                        $columns[$index] = $this->_formatIPRanges($institution->getIPRanges());
                        break;
                    case 'contact':
                        $columns[$index] = $user->getFullName();
                        break;
                    case 'mailing_address':
                        $columns[$index] = PKPString::html2text($user->getMailingAddress());
                        break;
                    case 'country':
                        $country = $countries->getByAlpha2($user->getCountry());
                        $columns[$index] = $country ? $country->getLocalName() : '';
                        break;
                    case 'email':
                        $columns[$index] = $user->getEmail();
                        break;
                    case 'phone':
                        $columns[$index] = $user->getPhone();
                        break;
                    default:
                        $columns[$index] = '';
                }
            }

            fputcsv($fp, $columns);
        }

        fclose($fp);
    }

    /**
     * Pretty format IP ranges, one per line via line feeds.
     *
     * @param array $ipRanges IP ranges
     *
     * @return string Text of IP ranges formatted with newlines
     */
    public function _formatIPRanges($ipRanges)
    {
        $numRanges = count($ipRanges);
        $ipRangesString = '';

        for ($i = 0; $i < $numRanges; $i++) {
            $ipRangesString .= $ipRanges[$i];
            if ($i + 1 < $numRanges) {
                $ipRangesString .= chr(13) . chr(10);
            }
        }

        return $ipRangesString;
    }
}
