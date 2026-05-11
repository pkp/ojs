<?php

/**
 * @file classes/publication/enums/UpdateType.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UpdateType
 *
 * @brief Enumeration for the type of update represented by a publication version.
 *
 * Adding/removing a case requires a migration on the publications.update_type
 * column AND updating the in: rule on lib/pkp/schemas/publication.json.
 */

namespace APP\publication\enums;

enum UpdateType: string
{
    case ADDENDUM = 'addendum';
    case CLARIFICATION = 'clarification';
    case CORRECTION = 'correction';
    case CORRIGENDUM = 'corrigendum';
    case ERRATUM = 'erratum';
    case EXPRESSION_OF_CONCERN = 'expression_of_concern';
    case NEW_EDITION = 'new_edition';
    case NEW_VERSION = 'new_version';
    case PARTIAL_RETRACTION = 'partial_retraction';
    case REMOVAL = 'removal';
    case RETRACTION = 'retraction';
    case WITHDRAWAL = 'withdrawal';

    public function labelKey(): string
    {
        return match ($this) {
            self::ADDENDUM => 'publication.updateType.addendum',
            self::CLARIFICATION => 'publication.updateType.clarification',
            self::CORRECTION => 'publication.updateType.correction',
            self::CORRIGENDUM => 'publication.updateType.corrigendum',
            self::ERRATUM => 'publication.updateType.erratum',
            self::EXPRESSION_OF_CONCERN => 'publication.updateType.expressionOfConcern',
            self::NEW_EDITION => 'publication.updateType.newEdition',
            self::NEW_VERSION => 'publication.updateType.newVersion',
            self::PARTIAL_RETRACTION => 'publication.updateType.partialRetraction',
            self::REMOVAL => 'publication.updateType.removal',
            self::RETRACTION => 'publication.updateType.retraction',
            self::WITHDRAWAL => 'publication.updateType.withdrawal',
        };
    }

    public function label(?string $locale = null): string
    {
        return __($this->labelKey(), locale: $locale);
    }
}
