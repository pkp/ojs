<?php
/**
 * @file classes/services/QueryBuilders/ContextQueryBuilder.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContextQueryBuilder
 * @ingroup query_builders
 *
 * @brief Journal list query builder
 */

namespace APP\Services\QueryBuilders;

class ContextQueryBuilder extends \PKP\services\QueryBuilders\PKPContextQueryBuilder
{
    /** @copydoc \PKP\services\QueryBuilders\PKPContextQueryBuilder::$db */
    protected $db = 'journals';

    /** @copydoc \PKP\services\QueryBuilders\PKPContextQueryBuilder::$dbSettings */
    protected $dbSettings = 'journal_settings';

    /** @copydoc \PKP\services\QueryBuilders\PKPContextQueryBuilder::$dbIdColumn */
    protected $dbIdColumn = 'journal_id';
}
