<?php

/**
 * @file classes/journal/SectionDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SectionDAO
 * @ingroup journal
 *
 * @see Section
 *
 * @brief Operations for retrieving and modifying Section objects.
 */

namespace APP\journal;

use APP\core\Application;
use APP\facades\Repo;
use PKP\cache\CacheManager;
use PKP\context\PKPSectionDAO;
use PKP\db\DAORegistry;
use PKP\db\DAOResultFactory;
use PKP\plugins\HookRegistry;
use PKP\submission\PKPSubmission;

class SectionDAO extends PKPSectionDAO
{
    public $cache;

    /**
     * Get the name of the section table in the database
     *
     * @return string
     */
    protected function _getTableName()
    {
        return 'sections';
    }

    /**
     * Get the name of the context ID table column
     *
     * @return string
     */
    protected function _getContextIdColumnName()
    {
        return 'journal_id';
    }

    public function _cacheMiss($cache, $id)
    {
        $section = $this->getById($id, null, false);
        $cache->setCache($id, $section);
        return $section;
    }

    public function &_getCache()
    {
        if (!isset($this->cache)) {
            $cacheManager = CacheManager::getManager();
            $this->cache = $cacheManager->getObjectCache('sections', 0, [$this, '_cacheMiss']);
        }
        return $this->cache;
    }

    /**
     * Retrieve a section by ID.
     *
     * @param int $sectionId
     * @param int $journalId Journal ID optional
     * @param bool $useCache optional
     *
     * @return Section?
     */
    public function getById($sectionId, $journalId = null, $useCache = false)
    {
        if ($useCache) {
            $cache = $this->_getCache();
            $returner = $cache->get($sectionId);
            if ($returner && $journalId != null && $journalId != $returner->getJournalId()) {
                $returner = null;
            }
            return $returner;
        }

        $params = [(int) $sectionId];
        if ($journalId !== null) {
            $params[] = (int) $journalId;
        }
        $result = $this->retrieve(
            'SELECT * FROM sections WHERE section_id = ?'
            . ($journalId !== null ? ' AND journal_id = ?' : ''),
            $params
        );

        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Retrieve a section by abbreviation.
     *
     * @param string $sectionAbbrev
     * @param int $journalId Journal ID
     * @param string $locale optional
     *
     * @return Section?
     */
    public function getByAbbrev($sectionAbbrev, $journalId, $locale = null)
    {
        $params = ['abbrev', $sectionAbbrev, (int) $journalId];
        if ($locale !== null) {
            $params[] = $locale;
        }

        $result = $this->retrieve(
            'SELECT	s.*
			FROM	sections s, section_settings l
			WHERE	l.section_id = s.section_id AND
				l.setting_name = ? AND
				l.setting_value = ? AND
				s.journal_id = ?' .
                ($locale !== null ? ' AND l.locale = ?' : ''),
            $params
        );

        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Retrieve a section by title.
     *
     * @param string $sectionTitle
     * @param int $journalId Journal ID
     * @param string $locale optional
     *
     * @return Section?
     */
    public function getByTitle($sectionTitle, $journalId, $locale = null)
    {
        $params = ['title', $sectionTitle, (int) $journalId];
        if ($locale !== null) {
            $params[] = $locale;
        }

        $result = $this->retrieve(
            'SELECT	s.*
			FROM	sections s, section_settings l
			WHERE	l.section_id = s.section_id AND
				l.setting_name = ? AND
				l.setting_value = ? AND
				s.journal_id = ?' .
                ($locale !== null ? ' AND l.locale = ?' : ''),
            $params
        );

        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Retrieve section a submission is assigned to.
     *
     * @param int $submissionId Submission id
     *
     * @return Section
     */
    public function getBySubmissionId($submissionId)
    {
        $result = $this->retrieve(
            'SELECT sections.* FROM sections
				JOIN submissions
				ON (submissions.section_id = sections.section_id)
				WHERE submissions.submission_id = ?',
            [(int) $submissionId]
        );

        $row = $result->current();
        return $row ? $this->_fromRow((array) $row) : null;
    }

    /**
     * Return a new data object.
     */
    public function newDataObject()
    {
        return new Section();
    }

    /**
     * Internal function to return a Section object from a row.
     *
     * @param array $row
     *
     * @return Section
     */
    public function _fromRow($row)
    {
        $section = parent::_fromRow($row);

        $section->setId($row['section_id']);
        $section->setJournalId($row['journal_id']);
        $section->setMetaIndexed($row['meta_indexed']);
        $section->setMetaReviewed($row['meta_reviewed']);
        $section->setAbstractsNotRequired($row['abstracts_not_required']);
        $section->setHideTitle($row['hide_title']);
        $section->setHideAuthor($row['hide_author']);
        $section->setIsInactive($row['is_inactive']);
        $section->setAbstractWordCount($row['abstract_word_count']);

        $this->getDataObjectSettings('section_settings', 'section_id', $row['section_id'], $section);

        HookRegistry::call('SectionDAO::_fromRow', [&$section, &$row]);

        return $section;
    }

    /**
     * Get the list of fields for which data can be localized.
     *
     * @return array
     */
    public function getLocaleFieldNames()
    {
        return array_merge(
            parent::getLocaleFieldNames(),
            ['abbrev', 'identifyType']
        );
    }

    /**
     * Update the localized fields for this table
     *
     * @param object $section
     */
    public function updateLocaleFields($section)
    {
        $this->updateDataObjectSettings(
            'section_settings',
            $section,
            ['section_id' => $section->getId()]
        );
    }

    /**
     * Insert a new section.
     *
     * @param Section $section
     *
     * @return int new Section ID
     */
    public function insertObject($section)
    {
        $this->update(
            'INSERT INTO sections
				(journal_id, review_form_id, seq, meta_indexed, meta_reviewed, abstracts_not_required, editor_restricted, hide_title, hide_author, is_inactive, abstract_word_count)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                (int)$section->getJournalId(),
                (int)$section->getReviewFormId(),
                (float) $section->getSequence(),
                $section->getMetaIndexed() ? 1 : 0,
                $section->getMetaReviewed() ? 1 : 0,
                $section->getAbstractsNotRequired() ? 1 : 0,
                $section->getEditorRestricted() ? 1 : 0,
                $section->getHideTitle() ? 1 : 0,
                $section->getHideAuthor() ? 1 : 0,
                $section->getIsInactive() ? 1 : 0,
                (int) $section->getAbstractWordCount()
            ]
        );

        $section->setId($this->getInsertId());
        $this->updateLocaleFields($section);
        return $section->getId();
    }

    /**
     * Update an existing section.
     *
     * @param Section $section
     */
    public function updateObject($section)
    {
        $this->update(
            'UPDATE sections
				SET
					review_form_id = ?,
					seq = ?,
					meta_indexed = ?,
					meta_reviewed = ?,
					abstracts_not_required = ?,
					editor_restricted = ?,
					hide_title = ?,
					hide_author = ?,
					is_inactive = ?,
					abstract_word_count = ?
				WHERE section_id = ?',
            [
                (int)$section->getReviewFormId(),
                (float) $section->getSequence(),
                (int)$section->getMetaIndexed(),
                (int)$section->getMetaReviewed(),
                (int)$section->getAbstractsNotRequired(),
                (int)$section->getEditorRestricted(),
                (int)$section->getHideTitle(),
                (int)$section->getHideAuthor(),
                (int)$section->getIsInactive(),
                $this->nullOrInt($section->getAbstractWordCount()),
                (int)$section->getId()
            ]
        );
        $this->updateLocaleFields($section);
    }

    /**
     * Delete a section by ID.
     *
     * @param int $sectionId Section ID
     * @param int $contextId optional
     */
    public function deleteById($sectionId, $contextId = null)
    {
        // No articles should exist in this section
        $collector = Repo::submission()->getCollector()->filterBySectionIds([(int) $sectionId])->filterByContextIds([Application::CONTEXT_ID_ALL]);
        $count = Repo::submission()->getCount($collector);
        if ($count) {
            throw new Exception('Tried to delete a section that has one or more submissions assigned to it.');
        }

        $subEditorsDao = DAORegistry::getDAO('SubEditorsDAO'); /** @var SubEditorsDAO $subEditorsDao */
        $subEditorsDao->deleteBySubmissionGroupId($sectionId, ASSOC_TYPE_SECTION, $contextId);

        if (isset($contextId) && !$this->sectionExists($sectionId, $contextId)) {
            return false;
        }
        $this->update('DELETE FROM section_settings WHERE section_id = ?', [(int) $sectionId]);
        $this->update('DELETE FROM sections WHERE section_id = ?', [(int) $sectionId]);
    }

    /**
     * Delete sections by journal ID
     * NOTE: This does not delete dependent entries EXCEPT from subeditor_submission_group. It is intended
     * to be called only when deleting a journal.
     *
     * @param int $journalId Journal ID
     */
    public function deleteByJournalId($journalId)
    {
        $this->deleteByContextId($journalId);
    }

    /**
     * Retrieve an array associating all section editor IDs with
     * arrays containing the sections they edit.
     *
     * @param int $journalId Journal ID
     *
     * @return array editorId => array(sections they edit)
     */
    public function getEditorSections($journalId)
    {
        $result = $this->retrieve(
            'SELECT s.*, se.user_id AS editor_id FROM subeditor_submission_group ssg, sections s WHERE ssg.assoc_id = s.section_id AND ssg.assoc_type = ? AND s.journal_id = ssg.context_id AND s.journal_id = ?',
            [(int) ASSOC_TYPE_SECTION, (int) $journalId]
        );

        $returner = [];
        foreach ($result as $row) {
            $section = $this->_fromRow((array) $row);
            if (!isset($returner[$row->editor_id])) {
                $returner[$row->editor_id] = [$section];
            } else {
                $returner[$row->editor_id][] = $section;
            }
        }
        return $returner;
    }

    /**
     * Retrieve all sections in which articles are currently published in
     * the given issue.
     *
     * @param int $issueId Issue ID
     *
     * @return array
     */
    public function getByIssueId($issueId)
    {
        $issue = Repo::issue()->get($issueId);
        $allowedStatuses = [PKPSubmission::STATUS_PUBLISHED];
        if (!$issue->getPublished()) {
            $allowedStatuses[] = PKPSubmission::STATUS_SCHEDULED;
        }
        $collector = Repo::submission()->getCollector();
        $collector
            ->filterByContextIds([$issue->getJournalId()])
            ->filterByIssueIds([$issueId])
            ->filterByStatus($allowedStatuses)
            ->orderBy($collector::ORDERBY_SEQUENCE, $collector::ORDER_DIR_ASC);
        $submissions = Repo::submission()->getMany($collector);
        $sectionIds = [];
        foreach ($submissions as $submission) {
            $sectionIds[] = $submission->getCurrentPublication()->getData('sectionId');
        }
        if (empty($sectionIds)) {
            return [];
        }
        $sectionIds = array_unique($sectionIds);
        $result = $this->retrieve(
            'SELECT s.*, COALESCE(o.seq, s.seq) AS section_seq
				FROM sections s
				LEFT JOIN custom_section_orders o ON (s.section_id = o.section_id AND o.issue_id = ?)
				WHERE s.section_id IN (' . substr(str_repeat('?,', count($sectionIds)), 0, -1) . ')
				ORDER BY section_seq',
            array_merge([(int) $issueId], $sectionIds)
        );

        $sections = [];
        foreach ($result as $row) {
            $sections[] = $this->_fromRow((array) $row);
        }
        return $sections;
    }

    /**
     * Retrieve all sections for a journal.
     *
     * @param int $journalId Journal ID
     * @param DBResultRange $rangeInfo optional
     *
     * @return DAOResultFactory containing Sections ordered by sequence
     */

    public function getByJournalId($journalId, $rangeInfo = null)
    {
        return $this->getByContextId($journalId, $rangeInfo);
    }

    /**
     * Retrieve all sections for a journal.
     *
     * @param int $journalId Journal ID
     * @param DBResultRange $rangeInfo optional
     * @param bool $submittableOnly optional. Whether to return only sections
     *  that can be submitted to by anyone.
     *
     * @return DAOResultFactory containing Sections ordered by sequence
     */
    public function getByContextId($journalId, $rangeInfo = null, $submittableOnly = false)
    {
        $result = $this->retrieveRange(
            'SELECT * FROM sections WHERE journal_id = ? ' . ($submittableOnly ? ' AND editor_restricted = 0' : '') . ' ORDER BY seq',
            [(int) $journalId],
            $rangeInfo
        );

        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Retrieve all sections.
     *
     * @param DBResultRange $rangeInfo optional
     *
     * @return DAOResultFactory containing Sections ordered by journal ID and sequence
     */
    public function getAll($rangeInfo = null)
    {
        $result = $this->retrieveRange(
            'SELECT * FROM sections ORDER BY journal_id, seq',
            [],
            $rangeInfo
        );

        return new DAOResultFactory($result, $this, '_fromRow');
    }

    /**
     * Check if the section is empty.
     *
     * @param int $sectionId Section ID
     * @param int $journalId Journal ID
     *
     * @return bool
     */
    public function sectionEmpty($sectionId, $journalId)
    {
        $result = $this->retrieve(
            'SELECT p.publication_id FROM publications p JOIN submissions s ON (s.submission_id = p.submission_id) WHERE p.section_id = ? AND s.context_id = ?',
            [(int) $sectionId, (int) $journalId]
        );
        $row = $result->current();
        return $row ? false : true;
    }

    /**
     * Check if a section exists with the specified ID.
     *
     * @param int $sectionId Section ID
     * @param int $journalId Journal ID
     *
     * @return bool
     */
    public function sectionExists($sectionId, $journalId)
    {
        $result = $this->retrieve(
            'SELECT COUNT(*) AS row_count FROM sections WHERE section_id = ? AND journal_id = ?',
            [(int) $sectionId, (int) $journalId]
        );
        $row = $result->current();
        return $row ? (bool) $row->row_count : false;
    }

    /**
     * Sequentially renumber sections in their sequence order.
     *
     * @param int $journalId Journal ID
     */
    public function resequenceSections($journalId)
    {
        $result = $this->retrieve('SELECT section_id FROM sections WHERE journal_id = ? ORDER BY seq', [(int) $journalId]);

        for ($i = 1; $row = $result->current(); $i++) {
            $this->update('UPDATE sections SET seq = ? WHERE section_id = ?', [$i, $row->section_id]);
            $result->next();
        }
    }

    /**
     * Get the ID of the last inserted section.
     *
     * @return int
     */
    public function getInsertId()
    {
        return $this->_getInsertId('sections', 'section_id');
    }

    /**
     * Delete the custom ordering of an issue's sections.
     *
     * @param int $issueId
     */
    public function deleteCustomSectionOrdering($issueId)
    {
        $this->update(
            'DELETE FROM custom_section_orders WHERE issue_id = ?',
            [(int) $issueId]
        );
    }

    /**
     * Delete a section from the custom section order table.
     *
     * @param int $issueId
     * @param int $sectionId
     */
    public function deleteCustomSection($issueId, $sectionId)
    {
        $seq = $this->getCustomSectionOrder($issueId, $sectionId);

        $this->update(
            'DELETE FROM custom_section_orders WHERE issue_id = ? AND section_id = ?',
            [(int) $issueId, (int) $sectionId]
        );

        // Reduce the section order of every successive section by one
        $this->update(
            'UPDATE custom_section_orders SET seq = seq - 1 WHERE issue_id = ? AND seq > ?',
            [(int) $issueId, (float) $seq]
        );
    }

    /**
     * Sequentially renumber custom section orderings in their sequence order.
     *
     * @param int $issueId
     */
    public function resequenceCustomSectionOrders($issueId)
    {
        $result = $this->retrieve('SELECT section_id FROM custom_section_orders WHERE issue_id = ? ORDER BY seq', [(int) $issueId]);

        for ($i = 1; $row = $result->current(); $i++) {
            $this->update('UPDATE custom_section_orders SET seq = ? WHERE section_id = ? AND issue_id = ?', [$i, $row->section_id, (int) $issueId]);
            $result->next();
        }
    }

    /**
     * Check if an issue has custom section ordering.
     *
     * @param int $issueId
     *
     * @return bool
     */
    public function customSectionOrderingExists($issueId)
    {
        $result = $this->retrieve('SELECT COUNT(*) AS row_count FROM custom_section_orders WHERE issue_id = ?', [(int) $issueId]);
        $row = $result->current();
        return $row && $row->row_count != 0;
    }

    /**
     * Get the custom section order of a section.
     *
     * @param int $issueId
     * @param int $sectionId
     *
     * @return int?
     */
    public function getCustomSectionOrder($issueId, $sectionId)
    {
        $result = $this->retrieve(
            'SELECT seq FROM custom_section_orders WHERE issue_id = ? AND section_id = ?',
            [(int) $issueId, (int) $sectionId]
        );
        $row = $result->current();
        return $row ? $row->seq : null;
    }

    /**
     * Import the current section orders into the specified issue as custom
     * issue orderings.
     *
     * @param int $issueId
     */
    public function setDefaultCustomSectionOrders($issueId)
    {
        $issueSections = $this->getByIssueId($issueId);
        $i = 1;
        foreach ($issueSections as $section) {
            $this->insertCustomSectionOrder($issueId, $section->getId(), $i);
            $i++;
        }
    }

    /**
     * INTERNAL USE ONLY: Insert a custom section ordering
     *
     * @param int $issueId
     * @param int $sectionId
     * @param int $seq
     */
    public function insertCustomSectionOrder($issueId, $sectionId, $seq)
    {
        $this->update(
            'INSERT INTO custom_section_orders (section_id, issue_id, seq) VALUES (?, ?, ?)',
            [(int) $sectionId,(int) $issueId, (float) $seq]
        );
    }

    /**
     * Update a custom section ordering
     *
     * @param int $issueId
     * @param int $sectionId
     * @param int $seq
     */
    public function updateCustomSectionOrder($issueId, $sectionId, $seq)
    {
        $this->update(
            'UPDATE custom_section_orders SET seq = ? WHERE issue_id = ? AND section_id = ?',
            [(float) $seq, (int) $issueId, (int) $sectionId]
        );
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\journal\SectionDAO', '\SectionDAO');
}
