<?php

/**
 * @file pages/gateway/GatewayHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GatewayHandler
 *
 * @ingroup pages_gateway
 *
 * @brief Handle external gateway requests.
 */

namespace APP\pages\gateway;

use APP\core\PageRouter;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\journal\JournalDAO;
use APP\template\TemplateManager;
use Illuminate\Support\LazyCollection;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\plugins\PluginRegistry;

class GatewayHandler extends Handler
{
    public $plugin;

    /**
     * Constructor
     *
     * @param \APP\core\Request $request
     */
    public function __construct($request)
    {
        parent::__construct();
        /** @var PageRouter */
        $router = $request->getRouter();
        $op = $router->getRequestedOp($request);
        if ($op == 'plugin') {
            $args = $router->getRequestedArgs($request);
            $pluginName = array_shift($args);
            $plugins = PluginRegistry::loadCategory('gateways');
            if (!isset($plugins[$pluginName])) {
                $request->getDispatcher()->handle404();
            }
            $this->plugin = $plugins[$pluginName];
            foreach ($this->plugin->getPolicies($request) as $policy) {
                $this->addPolicy($policy);
            }
        }
    }

    /**
     * Index handler.
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function index($args, $request)
    {
        $request->redirect(null, 'index');
    }

    /**
     * Display the LOCKSS manifest.
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function lockss($args, $request)
    {
        $this->validate();
        $this->setupTemplate($request);

        $journal = $request->getContext();
        $templateMgr = TemplateManager::getManager($request);

        if ($journal != null) {
            if (!$journal->getData('enableLockss')) {
                $request->redirect(null, 'index');
            }
            $yearsIssuesPublished = Repo::issue()->getYearsIssuesPublished($journal->getId())->values();

            // FIXME Should probably go in IssueDAO or a subclass
            $year = $yearsIssuesPublished->contains((int) $request->getUserVar('year'))
                ? (int) $request->getUserVar('year')
                : null;

            if (!isset($year)) {
                $year = $yearsIssuesPublished->max();
                $templateMgr->assign('showInfo', true);
            }

            $prevYear = $nextYear = null;
            if (isset($year)) {
                $key = $yearsIssuesPublished->search(function ($i) use ($year) {
                    return $i === $year;
                });
                if (isset($key)) {
                    $prevYear = $yearsIssuesPublished->get($key - 1);
                    $nextYear = $yearsIssuesPublished->get($key + 1);
                }
            }

            $issues = $this->getPublishedIssuesByNumber($journal->getId(), null, null, $year);
            $templateMgr->assign([
                'journal' => $journal,
                'year' => $year,
                'prevYear' => $prevYear,
                'nextYear' => $nextYear,
                'issues' => $issues->toArray(),
            ]);

            $locales = $journal->getSupportedLocaleNames();
            if (!isset($locales) || empty($locales)) {
                $primaryLocale = Locale::getPrimaryLocale();
                $locales = [$primaryLocale => Locale::getMetadata($primaryLocale)->getDisplayName()];
            }
            $templateMgr->assign('locales', $locales);
        } else {
            $journalDao = DAORegistry::getDAO('JournalDAO'); /** @var JournalDAO $journalDao */
            $journals = $journalDao->getAll(true);
            $templateMgr->assign('journals', $journals);
        }

        $templateMgr->display('gateway/lockss.tpl');
    }

    /**
     * Display the CLOCKSS manifest.
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function clockss($args, $request)
    {
        $this->validate();
        $this->setupTemplate($request);

        $journal = $request->getContext();
        $templateMgr = TemplateManager::getManager($request);

        if ($journal != null) {
            if (!$journal->getData('enableClockss')) {
                $request->redirect(null, 'index');
            }

            $yearsIssuesPublished = Repo::issue()->getYearsIssuesPublished($journal->getId())->values();

            // FIXME Should probably go in Issue DAO or a subclass
            $year = $yearsIssuesPublished->contains((int) $request->getUserVar('year'))
                ? (int) $request->getUserVar('year')
                : null;


            if (!isset($year)) {
                $year = $yearsIssuesPublished->max();
                $issues = $this->getPublishedIssuesByNumber($journal->getId(), null, null, $year);
                $templateMgr->assign([
                    'issues' => $issues->toArray(),
                    'showInfo' => true,
                ]);
            }

            $prevYear = $nextYear = null;
            if (isset($year)) {
                $key = $yearsIssuesPublished->search(function ($i) use ($year) {
                    return $i === $year;
                });
                if (isset($key)) {
                    $prevYear = $yearsIssuesPublished->get($key - 1);
                    $nextYear = $yearsIssuesPublished->get($key + 1);
                }
            }

            $issues = $this->getPublishedIssuesByNumber($journal->getId(), null, null, $year);
            $templateMgr->assign([
                'journal' => $journal,
                'year' => $year,
                'prevYear' => $prevYear,
                'nextYear' => $nextYear,
                'issues' => $issues->toArray(),
            ]);

            $locales = $journal->getSupportedLocaleNames();
            if (!isset($locales) || empty($locales)) {
                $primaryLocale = Locale::getPrimaryLocale();
                $locales = [$primaryLocale => Locale::getMetadata($primaryLocale)->getDisplayName()];
            }
            $templateMgr->assign('locales', $locales);
        } else {
            $journalDao = DAORegistry::getDAO('JournalDAO'); /** @var JournalDAO $journalDao */
            $journals = $journalDao->getAll(true);
            $templateMgr->assign('journals', $journals);
        }

        $templateMgr->display('gateway/clockss.tpl');
    }

    /**
     * Handle requests for gateway plugins.
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function plugin($args, $request)
    {
        $this->validate();
        if (isset($this->plugin)) {
            if (!$this->plugin->fetch(array_slice($args, 1), $request)) {
                $request->redirect(null, 'index');
            }
        } else {
            $request->redirect(null, 'index');
        }
    }

    /**
     * Retrieve Issue by some combination of volume, number, and year
     *
     */
    protected function getPublishedIssuesByNumber(int $contextId, ?int $volume = null, ?int $number = null, ?int $year = null): LazyCollection
    {
        $collector = Repo::issue()->getCollector()
            ->filterByContextIds([$contextId]);

        if ($volume !== null) {
            $collector->filterByVolumes([$volume]);
        }

        if ($number !== null) {
            $collector->filterByNumbers([$number]);
        }

        if ($year !== null) {
            $collector->filterByYears([$year]);
        }

        return $collector->getMany();
    }
}
