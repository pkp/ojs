<?php

/**
 * @file plugins/gateways/resolver/ResolverPlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ResolverPlugin
 *
 * @brief Simple resolver gateway plugin
 */

namespace APP\plugins\gateways\resolver;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Collector;
use APP\journal\JournalDAO;
use APP\template\TemplateManager;
use PKP\core\PKPString;
use PKP\db\DAORegistry;
use PKP\plugins\GatewayPlugin;

class ResolverPlugin extends GatewayPlugin
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
     * Get the name of the settings file to be installed on new journal
     * creation.
     *
     * @return string
     */
    public function getContextSpecificPluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    /**
     * Get the name of this plugin. The name must be unique within
     * its category.
     *
     * @return string name of plugin
     */
    public function getName()
    {
        return 'ResolverPlugin';
    }

    public function getDisplayName()
    {
        return __('plugins.gateways.resolver.displayName');
    }

    public function getDescription()
    {
        return __('plugins.gateways.resolver.description');
    }

    /**
     * Handle fetch requests for this plugin.
     */
    public function fetch($args, $request)
    {
        if (!$this->getEnabled()) {
            return false;
        }

        $scheme = array_shift($args);
        switch ($scheme) {
            case 'doi':
                $doi = implode('/', $args);
                $article = Repo::submission()->getByDoi($doi, $request->getJournal()->getId());
                if ($article) {
                    $request->redirect(null, 'article', 'view', $article->getBestId());
                }
                break;
            case 'vnp': // Volume, number, page
            case 'ynp': // Volume, number, year, page
                // This can only be used from within a journal context
                $journal = $request->getJournal();
                if (!$journal) {
                    break;
                }

                if ($scheme == 'vnp') {
                    $volume = (int) array_shift($args);
                    $year = null;
                } elseif ($scheme == 'ynp') {
                    $year = (int) array_shift($args);
                    $volume = null;
                } else {
                    return; // Suppress scrutinizer warn
                }
                $number = array_shift($args);
                $page = (int) array_shift($args);

                $issueCollector = Repo::issue()->getCollector()
                    ->filterByContextIds([$journal->getId()]);

                if ($volume !== null) {
                    $issueCollector->filterByVolumes([$volume]);
                }

                if ($number !== null) {
                    $issueCollector->filterByNumbers([$number]);
                }

                if ($year !== null) {
                    $issueCollector->filterByYears([$year]);
                }

                $issues = $issueCollector->getMany();

                // Ensure only one issue matched, and fetch it.
                if ($issues->count() != 1) {
                    break;
                }
                $issue = $issues->first();
                unset($issues);

                $submissions = Repo::submission()
                    ->getCollector()
                    ->filterByContextIds([$issue->getJournalId()])
                    ->filterByIssueIds([$issue->getId()])
                    ->getMany();

                foreach ($submissions as $submission) {
                    // Look for the correct page in the list of articles.
                    $matches = null;
                    if (PKPString::regexp_match_get('/^[Pp][Pp]?[.]?[ ]?(\d+)$/', $submission->getPages(), $matches)) {
                        $matchedPage = $matches[1];
                        if ($page == $matchedPage) {
                            $request->redirect(null, 'article', 'view', $submission->getBestId());
                        }
                    }
                    if (PKPString::regexp_match_get('/^[Pp][Pp]?[.]?[ ]?(\d+)[ ]?-[ ]?([Pp][Pp]?[.]?[ ]?)?(\d+)$/', $submission->getPages(), $matches)) {
                        $matchedPageFrom = $matches[1];
                        $matchedPageTo = $matches[3];
                        if ($page >= $matchedPageFrom && ($page < $matchedPageTo || ($page == $matchedPageTo && $matchedPageFrom = $matchedPageTo))) {
                            $request->redirect(null, 'article', 'view', $submission->getBestId());
                        }
                    }
                    unset($submission);
                }
                break;
        }

        // Failure.
        header('HTTP/1.0 404 Not Found');
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('message', 'plugins.gateways.resolver.errors.errorMessage');
        $templateMgr->display('frontend/pages/message.tpl');
        exit;
    }

    public function sanitize($string)
    {
        return str_replace("\t", ' ', strip_tags($string));
    }

    public function exportHoldings()
    {
        $journalDao = DAORegistry::getDAO('JournalDAO'); /** @var JournalDAO $journalDao */
        $journals = $journalDao->getAll(true);
        $request = Application::get()->getRequest();
        header('content-type: text/plain');
        header('content-disposition: attachment; filename=holdings.txt');
        echo "title\tissn\te_issn\tstart_date\tend_date\tembargo_months\tembargo_days\tjournal_url\tvol_start\tvol_end\tiss_start\tiss_end\n";
        while ($journal = $journals->next()) {
            $issues = Repo::issue()->getCollector()
                ->filterByContextIds([$journal->getId()])
                ->filterByPublished(true)
                ->orderBy(Collector::ORDERBY_PUBLISHED_ISSUES)
                ->getMany();
            $startDate = $endDate = null;
            $startNumber = $endNumber = null;
            $startVolume = $endVolume = null;
            foreach ($issues as $issue) {
                $datePublished = $issue->getDatePublished();
                if ($datePublished !== null) {
                    $datePublished = strtotime($datePublished);
                }
                if ($startDate === null || $startDate > $datePublished) {
                    $startDate = $datePublished;
                }
                if ($endDate === null || $endDate < $datePublished) {
                    $endDate = $datePublished;
                }
                $volume = $issue->getVolume();
                if ($startVolume === null || $startVolume > $volume) {
                    $startVolume = $volume;
                }
                if ($endVolume === null || $endVolume < $volume) {
                    $endVolume = $volume;
                }
                $number = $issue->getNumber();
                if ($startNumber === null || $startNumber > $number) {
                    $startNumber = $number;
                }
                if ($endNumber === null || $endNumber < $number) {
                    $endNumber = $number;
                }
            }

            echo $this->sanitize($journal->getLocalizedName()) . "\t";
            echo $this->sanitize($journal->getData('printIssn')) . "\t";
            echo $this->sanitize($journal->getData('onlineIssn')) . "\t";
            echo $this->sanitize($startDate === null ? '' : date('Y-m-d', $startDate)) . "\t"; // start_date
            echo $this->sanitize($endDate === null ? '' : date('Y-m-d', $endDate)) . "\t"; // end_date
            echo $this->sanitize('') . "\t"; // embargo_months
            echo $this->sanitize('') . "\t"; // embargo_days
            echo $request->url($journal->getPath()) . "\t"; // journal_url
            echo $this->sanitize($startVolume) . "\t"; // vol_start
            echo $this->sanitize($endVolume) . "\t"; // vol_end
            echo $this->sanitize($startNumber) . "\t"; // iss_start
            echo $this->sanitize($endNumber) . "\n"; // iss_end
        }
    }
}
