<?php
/**
 * @file classes/services/QueryBuilders/ContextQueryBuilder.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContextQueryBuilder
 * @ingroup query_builders
 *
 * @brief Journal list query builder
 */
namespace APP\Services\QueryBuilders;

use Illuminate\Database\Capsule\Manager as Capsule;

class ContextQueryBuilder extends \PKP\Services\QueryBuilders\PKPContextQueryBuilder {
	/** @copydoc \PKP\Services\QueryBuilders\PKPContextQueryBuilder::$db */
	protected $db = 'journals';

	/** @copydoc \PKP\Services\QueryBuilders\PKPContextQueryBuilder::$dbSettings */
	protected $dbSettings = 'journal_settings';

	/** @copydoc \PKP\Services\QueryBuilders\PKPContextQueryBuilder::$dbIdColumn */
	protected $dbIdColumn = 'journal_id';
}
