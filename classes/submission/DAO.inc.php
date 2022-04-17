<?php
/**
 * @file classes/submission/DAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class submission
 *
 * @brief Read and write submissions to the database.
 */

namespace APP\submission;

use PKP\db\DAORegistry;
use PKP\db\DAOResultFactory;
use PKP\db\Generator;
use PKP\observers\events\SubmissionDeleted;

class DAO extends \PKP\submission\DAO
{
    /**
     * @copydoc \PKP\core\EntityDAO::deleteById()
     */
    public function deleteById(int $id)
    {
        $articleSearchDao = DAORegistry::getDAO('ArticleSearchDAO'); /** @var ArticleSearchDAO  $articleSearchDao */
        $articleSearchDao->deleteSubmissionKeywords($id);

        event(new SubmissionDeleted($this->get($id)));

        parent::deleteById($id);
    }

    /**
     * Get all published submissions (eventually with a pubId assigned and) matching the specified settings.
     *
     * @param null|mixed $pubIdType
     * @param null|mixed $title
     * @param null|mixed $author
     * @param null|mixed $issueId
     * @param null|mixed $pubIdSettingName
     * @param null|mixed $pubIdSettingValue
     * @param null|mixed $rangeInfo
     *
     * @return Generator
     */
    public function getExportable(
        $contextId,
        $pubIdType = null,
        $title = null,
        $author = null,
        $issueId = null,
        $pubIdSettingName = null,
        $pubIdSettingValue = null,
        $rangeInfo = null
    ) {
        $params = [];
        if ($pubIdSettingName) {
            $params[] = $pubIdSettingName;
        }
        $params[] = Submission::STATUS_PUBLISHED;
        $params[] = $contextId;
        if ($pubIdType) {
            $params[] = 'pub-id::' . $pubIdType;
        }
        if ($title) {
            $params[] = 'title';
            $params[] = '%' . $title . '%';
        }
        if ($author) {
            $params[] = $author;
            $params[] = $author;
        }
        if ($issueId) {
            $params[] = $issueId;
        }
        if ($pubIdSettingName && $pubIdSettingValue && $pubIdSettingValue != EXPORT_STATUS_NOT_DEPOSITED) {
            $params[] = $pubIdSettingValue;
        }

        $sql = 'SELECT	s.*
            FROM	submissions s
                LEFT JOIN publications p ON s.current_publication_id = p.publication_id
                LEFT JOIN publication_settings ps ON p.publication_id = ps.publication_id'
                . ($issueId ? ' LEFT JOIN publication_settings psi ON p.publication_id = psi.publication_id AND psi.setting_name = \'issueId\' AND psi.locale = \'\'' : '')
                . ($pubIdType != null ? ' LEFT JOIN publication_settings pspidt ON (p.publication_id = pspidt.publication_id)' : '')
                . ($title != null ? ' LEFT JOIN publication_settings pst ON (p.publication_id = pst.publication_id)' : '')
                . ($author != null ? ' LEFT JOIN authors au ON (p.publication_id = au.publication_id)
                        LEFT JOIN author_settings asgs ON (asgs.author_id = au.author_id AND asgs.setting_name = \'' . Identity::IDENTITY_SETTING_GIVENNAME . '\')
                        LEFT JOIN author_settings asfs ON (asfs.author_id = au.author_id AND asfs.setting_name = \'' . Identity::IDENTITY_SETTING_FAMILYNAME . '\')
                    ' : '')
                . ($pubIdSettingName != null ? ' LEFT JOIN submission_settings pss ON (s.submission_id = pss.submission_id AND pss.setting_name = ?)' : '')
            . ' WHERE	s.status = ?
                AND s.context_id = ?'
                . ($pubIdType != null ? ' AND pspidt.setting_name = ? AND pspidt.setting_value IS NOT NULL' : '')
                . ($title != null ? ' AND (pst.setting_name = ? AND pst.setting_value LIKE ?)' : '')
                . ($author != null ? ' AND (asgs.setting_value LIKE ? OR asfs.setting_value LIKE ?)' : '')
                . ($issueId != null ? ' AND psi.setting_value = ?' : '')
                . (($pubIdSettingName != null && $pubIdSettingValue != null && $pubIdSettingValue == EXPORT_STATUS_NOT_DEPOSITED) ? ' AND pss.setting_value IS NULL' : '')
                . (($pubIdSettingName != null && $pubIdSettingValue != null && $pubIdSettingValue != EXPORT_STATUS_NOT_DEPOSITED) ? ' AND pss.setting_value = ?' : '')
                . (($pubIdSettingName != null && is_null($pubIdSettingValue)) ? ' AND (pss.setting_value IS NULL OR pss.setting_value = \'\')' : '')
            . ' GROUP BY s.submission_id
            ORDER BY MAX(p.date_published) DESC, s.submission_id DESC';

        $rows = $this->deprecatedDao->retrieveRange($sql, $params, $rangeInfo);
        return new DAOResultFactory($rows, $this, 'fromRow', [], $sql, $params, $rangeInfo);
    }
}
