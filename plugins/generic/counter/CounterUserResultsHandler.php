<?php

/**
 * Description of CounterResultsHandler
 *
 * @author alee
 */
class CounterUserResultsHandler
{

    public static function handleResultBreakdown($plugin, $journalId, $year, $month)
    {
        $counterUserDao = & DAORegistry::getDAO('CounterUserDAO');

        $journalDao =& DAORegistry::getDAO('JournalDAO');

        $journal =& $journalDao->getJournal($journalId);
        $userId = self::getUserId();

        if ($journal) {
            $journalTitle = $journal->getLocalizedTitle();
            $journalPath = $journal->getPath();
            $date = self::getMonths($year);
            if ($month) {
                $type = "monthly";
                $date = $date[($month - 1)];
                $results = $counterUserDao->getJournalViewingDetails($journalId, $userId, $year, $month);
            } else {
                $type = "yearly";
                $date = "";
                $results = $counterUserDao->getAllViewingDetailsByYear($year, $journalId, $userId);
            }
        }

        $templateManager = & TemplateManager::getManager();
        $templateManager->assign('date', $date);
        $templateManager->assign('year', $year);
        $templateManager->assign('type', $type);
        $templateManager->assign('journalTitle', $journalTitle);
        $templateManager->assign('journalPath', $journalPath);
        $templateManager->assign('results', $results);
        $templateManager->display($plugin->getTemplatePath() . 'userStatsBreakdown.tpl');
    }

    public static function handleAllArticles($mode, $plugin)
    {
        $counterUserDao = & DAORegistry::getDAO('CounterUserDAO');
        $userId = self::getUserId();

        foreach ($counterUserDao->getYears() as $year) {
            $entries = $counterUserDao->getYearViewingDetails($year, $userId);
            $first = true;
            $monthValues = array();
            $lastEntry = null;
            foreach ($entries as $entry) {
                if (!$first && ($entry['journal_title'] != $lastEntry['journal_title'])) {
                    self::markNewJournal($lastEntry, $year, $monthValues, $results);
                    unset($monthValues, $total);
                }

                $monthValues[$entry['month']] = array("PDF" => $entry['count_pdf'], "HTML" => $entry['count_html']);
                $lastEntry = $entry;
                $first = false;
            }

            self::markNewJournal($entry, $year, $monthValues, $results);
            unset($entries, $monthValues, $lastEntry, $first);
        }

        $templateManager = & TemplateManager::getManager();
        $templateManager->assign('mode', $mode);
        $templateManager->assign('userId', $userId);
        $templateManager->assign('results', $results);
        $templateManager->display($plugin->getTemplatePath() . 'userStats.tpl');
    }

    private static function markNewJournal(&$entry, $year, &$monthValues, &$results)
    {
        $title = $entry['journal_title'];
        $results[$year]['results'][$title]['title'] = $entry['journal_title'];
        $results[$year]['results'][$title]['id'] = $entry['journal_id'];

        $total = 0;
        for ($i = 1; $i <= 12; $i++) {
            $thisMonthTotal = 0;
            if (isset($monthValues[$i])) {
                $thisMonthTotal = ($monthValues[$i]["PDF"] + $monthValues[$i]["HTML"]);
                $total += $thisMonthTotal;
            }
            $results[$year]['results'][$title]['monthTotal'][$i] = $thisMonthTotal;
        }

        $results[$year]['results'][$title]['yearTotal'] = $total;
        $results[$year]['months'] = self::getMonths($year);
    }

    public static function handleMostViewedArticles($mode, $plugin)
    {
        $counterUserDao = & DAORegistry::getDAO('CounterUserDAO');
        $journalDao =& DAORegistry::getDAO('JournalDAO');
        $userId = self::getUserId();

        $journals = $counterUserDao->getJournalIds($userId);

        foreach ($journals as $journalId) {
            $journal =& $journalDao->getJournal($journalId);
            $journalTitle = $journal->getLocalizedTitle();

            $entries = $counterUserDao->getAllViewingDetails($journalId, $userId);
            $journalCounts = array();
            foreach ($entries as $entry) {
                $journalCounts[$entry['title']] = array("views" => $entry['total_viewings'], "id" => $entry['article_id']);
            }
            $results[$journalTitle] = array("allCounts" => $journalCounts, "journalPath" => $journal->getPath());
        }

        $templateManager = & TemplateManager::getManager();
        $templateManager->assign('mode', $mode);
        $templateManager->assign('userId', $userId);
        $templateManager->assign('results', $results);
        $templateManager->display($plugin->getTemplatePath() . 'userStats.tpl');
    }

    private static function getUserId()
    {
        $sessionManager = & SessionManager::getManager();
        $session = & $sessionManager->getUserSession();
        return $session->getUserId();
    }

    private static function getMonths($year)
    {
        for ($i = 1; $i <= 12; $i++) {
            $time = strtotime($year . '-' . $i . '-01');
            strftime('%b', $time);
            $months[] = strftime('%b', $time);
        }
        return $months;
    }

}

?>
