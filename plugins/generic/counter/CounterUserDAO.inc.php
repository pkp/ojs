<?php

/**
 * @file plugins/generic/counter/CounterReportDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CounterReportDAO
 * @ingroup plugins_generic_counter
 *
 * @brief Class for managing COUNTER records.
 */

class CounterUserDAO extends DAO
{

    /**
     * Get the years for which log entries exist in the DB.
     * @return array
     */
    function getYears()
    {
        $result =& $this->retrieve(
            'SELECT DISTINCT year FROM counter_monthly_log order by year desc'
        );
        $years = array();
        while (!$result->EOF) {
            $row = $result->GetRowAssoc(false);
            $years[] = $row['year'];
            $result->MoveNext();
        }
        $result->Close();
        return $years;
    }

    /**
     * The journals which this user has accessed.
     * @return array
     */
    function getJournalIds($userId)
    {
        $result =& $this->retrieve(
            'SELECT DISTINCT journal_id FROM counter_monthly_log l where count_pdf > 0 and l.article_id != 0 and l.user_id=?', array((int)$userId)
        );
        $journalIds = array();
        while (!$result->EOF) {
            $row = $result->GetRowAssoc(false);
            $journalIds[] = $row['journal_id'];
            $result->MoveNext();
        }
        $result->Close();
        return $journalIds;
    }


    /*
    * Get a years viewings for this user for all journals, one row per user/journal/month
    */
    function getYearViewingDetails($year, $userId)
    {
        $sql = "SELECT j.journal_id as journal_id, js.setting_value as journal_title, ifnull(username, 'Not Recorded') as name, ifnull(institution_name, concat(first_name, ' ', last_name) ) as subscriber, month,
                    SUM(count_html) as count_html, SUM(count_pdf) as count_pdf
                    from counter_monthly_log co
                    join journals j on j.journal_id = co.journal_id
                    join journal_settings js on js.journal_id = j.journal_id and js.setting_name='title'
                    left join users u on u.user_id = co.user_id
                    left join subscriptions s on s.user_id = co.user_id and s.journal_id = co.journal_id
                    left join institutional_subscriptions sub on sub.subscription_id = s.subscription_id
                    where co.article_id != 0 and year = ? and co.user_id=?
                    group by ifnull(username, 'Not Recorded'), js.setting_value, ifnull(institution_name, first_name), month
                    order by name, journal_title, month";


        $result = null;
        $result = $this->retrieve($sql, array((int)$year, (int)$userId));

        $returner = null;
        if ($result->RecordCount() != 0) {
            $returner = $result->getArray();
        }

        $result->Close();
        unset($result);
        return $returner;
    }

    /*
     * Show the articles the user viewed in this journal on this month, most popular articles first
     */
    function getJournalViewingDetails($journal, $user_id, $year, $month)
    {
        $sql = "select co.article_id as articleId, arts.setting_value as title, SUM(count_html+count_pdf+count_other) as total_viewings
             from counter_monthly_log co
             join journals j on j.journal_id = co.journal_id
             join journal_settings js on js.journal_id = j.journal_id and js.setting_name='title'
             join published_articles pa on pa.article_id = co.article_id
             join article_settings arts on arts.article_id = pa.article_id and arts.setting_name = 'title'
             where year = ? and j.journal_id = ? and co.month = ? and co.user_id = ?
             group by arts.setting_value
             order by total_viewings desc";

        $result = $this->retrieve($sql, array((int)$year, (int)$journal, (int)$month, (int)$user_id));

        $returner = null;
        if ($result->RecordCount() != 0) {
            $returner = $result->getArray();
        }

        $result->Close();
        unset($result);
        return $returner;
    }

    /*
    * Show all the articles the user has viewed for each journal and their viewing count. most popular articles first
    */
    function getAllViewingDetails($journalId, $userId)
    {
        $sql = "select co.article_id, arts.setting_value as title, SUM(count_html+count_pdf+count_other) as total_viewings
             from counter_monthly_log co
             join journals j on j.journal_id = co.journal_id
             join journal_settings js on js.journal_id = j.journal_id and js.setting_name='title'
             join published_articles pa on pa.article_id = co.article_id
             join article_settings arts on arts.article_id = pa.article_id and arts.setting_name = 'title'
             where co.article_id != 0 and j.journal_id = ? and co.user_id = ?
             group by arts.setting_value
             order by total_viewings desc";

        $result = $this->retrieve($sql, array((int)$journalId, (int)$userId));

        $returner = null;
        if ($result->RecordCount() != 0) {
            $returner = $result->getArray();
        }

        $result->Close();
        unset($result);
        return $returner;
    }

    /*
    * Show all the articles the user has viewed for each journal and their viewing count. most popular articles first
    */
    function getAllViewingDetailsByYear($year, $journalId, $userId)
    {
        $sql = "select co.article_id, arts.setting_value as title, SUM(count_html+count_pdf+count_other) as total_viewings
             from counter_monthly_log co
             join journals j on j.journal_id = co.journal_id
             join journal_settings js on js.journal_id = j.journal_id and js.setting_name='title'
             join published_articles pa on pa.article_id = co.article_id
             join article_settings arts on arts.article_id = pa.article_id and arts.setting_name = 'title'
             where co.article_id != 0 and j.journal_id = ? and co.user_id = ? and co.year=?
             group by arts.setting_value
             order by total_viewings desc";

        $result = $this->retrieve($sql, array((int)$journalId, (int)$userId, (int)$year));

        $returner = null;
        if ($result->RecordCount() != 0) {
            $returner = $result->getArray();
        }

        $result->Close();
        unset($result);
        return $returner;
    }
}

?>
