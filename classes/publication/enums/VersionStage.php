<?php

/**
 * @file publication/enums/VersionStage.php
 *
 * Copyright (c) 2023-2024 Simon Fraser University
 * Copyright (c) 2023-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class VersionStage
 *
 * @brief Enumeration for publication version stages
 *
 * see also https://www.niso.org/standards-committees/jav-revision
 */

namespace APP\publication\enums;

enum VersionStage: string
{
    case AUTHOR_ORIGINAL = 'AO';
    case PUBLISHED_MANUSCRIPT_UNDER_REVIEW = 'PMUR';
    case VERSION_OF_RECORD = 'VoR';

    public function labelKey(): string
    {
        return match ($this) {
            self::AUTHOR_ORIGINAL => 'publication.versionStage.authorOriginal',
            self::PUBLISHED_MANUSCRIPT_UNDER_REVIEW => 'publication.versionStage.publishedManuscriptUnderReview',
            self::VERSION_OF_RECORD => 'publication.versionStage.versionOfRecord',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::AUTHOR_ORIGINAL => 1,
            self::PUBLISHED_MANUSCRIPT_UNDER_REVIEW => 2,
            self::VERSION_OF_RECORD => 3,
        };
    }

    public function label(?string $locale = null): string
    {
        return __($this->labelKey(), locale: $locale);
    }
}
