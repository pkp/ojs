<?php

/**
 * @defgroup section Section
 * Implements sections.
 */

/**
 * @file classes/section/Section.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Section
 *
 * @ingroup section
 *
 * @see DAO
 *
 * @brief Basic class describing a section.
*/

namespace APP\section;

class Section extends \PKP\section\PKPSection
{
    /* Because abbrev is required, there must be at least one abbrev. */
    public function getLocalizedAbbrev(): string
    {
        return $this->getLocalizedData('abbrev');
    }

    public function getAbbrev(?string $locale): string|array|null
    {
        return $this->getData('abbrev', $locale);
    }

    public function setAbbrev(string|array $abbrev, string $locale = null): void
    {
        $this->setData('abbrev', $abbrev, $locale);
    }

    public function getLocalizedPolicy(): ?string
    {
        return $this->getLocalizedData('policy');
    }

    public function getPolicy(?string $locale): string|array|null
    {
        return $this->getData('policy', $locale);
    }

    public function setPolicy(string|array $policy, string $locale = null): void
    {
        $this->setData('policy', $policy, $locale);
    }

    /**
     * Get ID of primary review form.
     */
    public function getReviewFormId(): ?int
    {
        return $this->getData('reviewFormId');
    }

    /**
     * Set ID of primary review form.
     */
    public function setReviewFormId(?int $reviewFormId): void
    {
        $this->setData('reviewFormId', $reviewFormId);
    }

    /**
     * Get "will/will not be indexed" setting of section.
     */
    public function getMetaIndexed(): bool
    {
        return $this->getData('metaIndexed');
    }

    /**
     * Set "will/will not be indexed" setting of section.
     */
    public function setMetaIndexed(bool $metaIndexed): void
    {
        $this->setData('metaIndexed', $metaIndexed);
    }

    /**
     * Get peer-reviewed setting of section.
     */
    public function getMetaReviewed(): bool
    {
        return $this->getData('metaReviewed');
    }

    /**
     * Set peer-reviewed setting of section.
     */
    public function setMetaReviewed(bool $metaReviewed): void
    {
        $this->setData('metaReviewed', $metaReviewed);
    }

    /**
     * Get boolean indicating whether abstracts are not required
     */
    public function getAbstractsNotRequired(): bool
    {
        return $this->getData('abstractsNotRequired');
    }

    /**
     * Set boolean indicating whether abstracts are not required
     */
    public function setAbstractsNotRequired(bool $abstractsNotRequired): void
    {
        $this->setData('abstractsNotRequired', $abstractsNotRequired);
    }

    /**
     * Return boolean indicating if title should be hidden in issue ToC.
     */
    public function getHideTitle(): bool
    {
        return $this->getData('hideTitle');
    }

    /**
     * Set if title should be hidden in issue ToC.
     */
    public function setHideTitle(bool $hideTitle): void
    {
        $this->setData('hideTitle', $hideTitle);
    }

    /**
     * Return boolean indicating if author should be hidden in issue ToC.
     */
    public function getHideAuthor(): bool
    {
        return $this->getData('hideAuthor');
    }

    /**
     * Set if author should be hidden in issue ToC.
     */
    public function setHideAuthor(bool $hideAuthor): void
    {
        $this->setData('hideAuthor', $hideAuthor);
    }
    /**
     * Get abstract word count limit.
     */
    public function getAbstractWordCount(): ?int
    {
        return $this->getData('wordCount');
    }

    /**
     * Set abstract word count limit.
     */
    public function setAbstractWordCount(int $wordCount): void
    {
        $this->setData('wordCount', $wordCount);
    }

    /**
     * Get localized string identifying type of items in this section.
     */
    public function getLocalizedIdentifyType(): ?string
    {
        return $this->getLocalizedData('identifyType');
    }

    /**
     * Get string identifying type of items in this section.
     */
    public function getIdentifyType(?string $locale): string|array|null
    {
        return $this->getData('identifyType', $locale);
    }

    /**
     * Set string identifying type of items in this section.
     */
    public function setIdentifyType(string|array $identifyType, string $locale = null): void
    {
        $this->setData('identifyType', $identifyType, $locale);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\section\Section', '\Section');
}
