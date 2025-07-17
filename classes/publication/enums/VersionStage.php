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
    case ACCEPTED_MANUSCRIPT = 'AM';
    case SUBMITTED_MANUSCRIPT = 'SM';
    case PROOF = 'PF';
    case VERSION_OF_RECORD = 'VoR';

    public function labelKey(): string
    {
        return match ($this) {
            self::AUTHOR_ORIGINAL => 'publication.versionStage.authorOriginal',
            self::ACCEPTED_MANUSCRIPT => 'publication.versionStage.acceptedManuscript',
            self::SUBMITTED_MANUSCRIPT => 'publication.versionStage.submittedManuscript',
            self::PROOF => 'publication.versionStage.proof',
            self::VERSION_OF_RECORD => 'publication.versionStage.versionOfRecord',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::AUTHOR_ORIGINAL => 1,
            self::SUBMITTED_MANUSCRIPT => 2,
            self::ACCEPTED_MANUSCRIPT => 3,
            self::PROOF => 4,
            self::VERSION_OF_RECORD => 5,
        };
    }

    public function label(?string $locale = null): string
    {
        return __($this->labelKey(), locale: $locale);
    }

    public static function getFromLower(string $lowerVersionStage): string
    {
        return match ($lowerVersionStage) {
            'ao' => self::AUTHOR_ORIGINAL->value,
            'am' => self::ACCEPTED_MANUSCRIPT->value,
            'sm' => self::SUBMITTED_MANUSCRIPT->value,
            'pf' => self::PROOF->value,
            'vor' => self::VERSION_OF_RECORD->value,
        };
    }
}
