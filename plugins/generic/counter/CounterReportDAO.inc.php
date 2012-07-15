<?php

/**
 * @file plugins/generic/counter/CounterReportDAO.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CounterReportDAO
 * @ingroup plugins_generic_counter
 *
 * @brief Class for managing COUNTER records.
 */

class CounterReportDAO extends DAO
{

    /**
     * Get the years for which log entries exist in the DB.
     * @return array
     */
    function getYears()
    {
        $result =& $this->retrieve(
            'SELECT DISTINCT year FROM counter_monthly_log'
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
     * Get the years for which log entries exist in the DB.
     * @return array
     */
    function getUserIdForRequestorIdDao($requestorId)
    {
        $result =& $this->retrieve(
            'SELECT user_id FROM users where REQUESTOR_ID = ? ',
            array((string)$requestorId)
        );
        $row = $result->GetRowAssoc(false);
        $result->Close();
        return $row['user_id'];
    }

    /**
     * Get the valid journal IDs for which log entries exist in the DB.
     * @return array
     */
    function getJournalIds()
    {
        $result =& $this->retrieve(
            'SELECT DISTINCT journal_id FROM counter_monthly_log l'
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


    /**
     * Retrieve a monthly log entry range.
     * @param $journalId int
     * @param $begin
     * @param $end
     * @return 2D array
     */
    function getMonthlyLogRange($journalId, $begin, $end)
    {
        $begin = getdate(strtotime($begin));
        $end = getdate(strtotime($end));
        $beginComb = $begin['year'] * 100 + $begin['mon'];
        $endComb = $end['year'] * 100 + $end['mon'];

        $result =& $this->retrieve(
            'SELECT year, month, SUM(count_html) as count_html, SUM(count_pdf) as count_pdf FROM counter_monthly_log
			WHERE journal_id = ? AND year * 100 + month >= ? AND year * 100 + month <= ? GROUP BY year, month',
            array((int)$journalId, (int)$beginComb, (int)$endComb)
        );

        $returner = null;
        if ($result->RecordCount() != 0) {
            $returner = $result->GetArray();
        }

        $result->Close();
        unset($result);

        return $returner;
    }

    /**
     * Retrieve a monthly log entry range.
     * @param $begin
     * @param $end
     * @return 2D array
     */
    function getMonthlyTotalRange($begin, $end)
    {
        $begin = getdate(strtotime($begin));
        $end = getdate(strtotime($end));
        $beginComb = $begin['year'] * 100 + $begin['mon'];
        $endComb = $end['year'] * 100 + $end['mon'];

        $result =& $this->retrieve(
            'SELECT year, month, SUM(count_html) as count_html, SUM(count_pdf) as count_pdf FROM counter_monthly_log
			WHERE year * 100 + month >= ? AND year * 100 + month <= ?
			GROUP BY year, month',
            array((int)$beginComb, (int)$endComb)
        );

        $returner = null;
        if ($result->RecordCount() != 0) {
            $returner = $result->GetArray();
        }

        $result->Close();
        unset($result);

        return $returner;
    }


    /**
     * Internal function to create the monthly record
     * @param $journalId int
     * @param $year int
     * @param $month int
     * @param $userId int
     * @param $articleId int
     */
    function _conditionalCreate($journalId, $year, $month, $userId = null, $articleId)
    {
        if ($userId != null) {
            $result =& $this->retrieve(
                'SELECT * FROM counter_monthly_log WHERE journal_id = ? AND year = ? AND month = ? AND user_id = ? AND article_id = ?',
                array((int)$journalId, (int)$year, (int)$month, (int)$userId, (int)$articleId)
            );

            if ($result->RecordCount() == 0) {
                $this->update(
                    'INSERT INTO counter_monthly_log (journal_id, year, month, user_id, article_id) VALUES (?, ?, ?, ?, ?)',
                    array((int)$journalId, (int)$year, (int)$month, (int)$userId, (int)$articleId)
                );
            }
            $result->Close();
            unset($result);
        } else {
            $result =& $this->retrieve(
                'SELECT * FROM counter_monthly_log WHERE journal_id = ? AND year = ? AND month = ? AND user_id is null AND article_id = ?',
                array((int)$journalId, (int)$year, (int)$month, (int)$articleId)
            );

            if ($result->RecordCount() == 0) {
                $this->update(
                    'INSERT INTO counter_monthly_log (journal_id, year, month, user_id, article_id) VALUES (?, ?, ?, null, ?)',
                    array((int)$journalId, (int)$year, (int)$month, (int)$articleId)
                );
            }
            $result->Close();
            unset($result);
        }

    }


    /**
     * Increment counters for a journal and year.
     * @param $journalId int
     * @param $year int
     * @param $month int
     * @param $isPdf boolean
     * @param $isHtml boolean
     * @param $userId int
     * @param $articleId int
     * @return boolean
     */
    function incrementCount($journalId, $year, $month, $isPdf, $isHtml, $userId = null, $articleId)
    {
        // create the monthly record if it does not exist
        $this->_conditionalCreate($journalId, $year, $month, $userId, $articleId);

        if ($month < 1 || $month > 12) return false;

        if ($userId != null) {
            $this->update(
                "UPDATE counter_monthly_log SET " .
                    ' count_html = count_html + ' . ($isHtml ? '1,' : '0,') .
                    ' count_pdf = count_pdf + ' . ($isPdf ? '1' : '0') .
                    " WHERE journal_id = ? AND year = ? AND month = ? AND user_id = ? AND article_id = ?",
                array((int)$journalId, (int)$year, (int)$month, (int)$userId, (int)$articleId)
            );
        } else {
            $this->update(
                "UPDATE counter_monthly_log SET " .
                    ' count_html = count_html + ' . ($isHtml ? '1,' : '0,') .
                    ' count_pdf = count_pdf + ' . ($isPdf ? '1' : '0') .
                    " WHERE journal_id = ? AND year = ? AND month = ? AND user_id is null AND article_id = ?",
                array((int)$journalId, (int)$year, (int)$month, (int)$articleId)
            );
        }

        return true;
    }

    function getOldLogFilename()
    {
        return dirname(__FILE__) . '/log.txt';
    }

    /*
     * Get all viewings for each user for each journal, one row per user/journal/month
     * @param $year int
     */
    function getAccessDetails($year)
    {
        $sql = "SELECT j.journal_id as journal_id, js.setting_value as journal_title, ifnull(username, 'Not Recorded') as name, ifnull(institution_name, concat(first_name, ' ', last_name) ) as subscriber, month,
                 SUM(count_html) as count_html, SUM(count_pdf) as count_pdf
                 from counter_monthly_log co
                 join journals j on j.journal_id = co.journal_id
                 join journal_settings js on js.journal_id = j.journal_id and js.setting_name='title'
                 left join users u on u.user_id = co.user_id
                 left join subscriptions s on s.user_id = co.user_id and s.journal_id = co.journal_id
                 left join institutional_subscriptions sub on sub.subscription_id = s.subscription_id
                 where year = ? 
                 group by ifnull(username, 'Not Recorded'), js.setting_value, ifnull(institution_name, first_name), month
                 order by name, journal_title, month";

        $result = null;
        $result = $this->retrieve($sql, array((int)$year));

        $returner = null;
        if ($result->RecordCount() != 0) {
            $returner = $result->getArray();
        }

        $result->Close();
        unset($result);
        return $returner;
    }

    /*
     * Show viewings for all articles for this journal, most popular articles first
     * @param $year int
     * @param $journalId int
     */
    function getViewingDetails($year, $journalId)
    {
        $sql = "select arts.setting_value as title, SUM(count_html+count_pdf+count_other) as total_viewings
             from counter_monthly_log co
             join journals j on j.journal_id = co.journal_id
             join journal_settings js on js.journal_id = j.journal_id and js.setting_name='title'
             join published_articles pa on pa.article_id = co.article_id
             join article_settings arts on arts.article_id = pa.article_id and arts.setting_name = 'title'
             where year = ? and j.journal_id = ?
             group by arts.setting_value
             order by total_viewings desc";

        $result = $this->retrieve($sql, array((int)$year, (int)$journalId));

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
