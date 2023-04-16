<?php

/**
 * @file classes/core/Request.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Request
 *
 * @ingroup core
 *
 * @brief @verbatim Class providing operations associated with HTTP requests.
 * Requests are assumed to be in the format http://host.tld/index.php/<journal_id>/<page_name>/<operation_name>/<arguments...>
 * <journal_id> is assumed to be "index" for top-level site requests. @endverbatim
 */

namespace APP\core;

use APP\journal\Journal;
use PKP\core\PKPRequest;

class Request extends PKPRequest
{
    /**
     * @see PKPPageRouter::getContext()
     */
    public function getJournal(): ?Journal
    {
        return $this->getContext();
    }

    /**
     * Deprecated
     *
     * @see PKPPageRouter::getContext()
     */
    public function getContext(): ?Journal
    {
        return parent::getContext();
    }

    /**
     * Deprecated
     *
     * @see PKPPageRouter::url()
     *
     * @param null|mixed $journalPath
     * @param null|mixed $page
     * @param null|mixed $op
     * @param null|mixed $path
     * @param null|mixed $params
     * @param null|mixed $anchor
     */
    public function url(
        $journalPath = null,
        $page = null,
        $op = null,
        $path = null,
        $params = null,
        $anchor = null,
        $escape = false
    ) {
        return $this->_delegateToRouter(
            'url',
            $journalPath,
            $page,
            $op,
            $path,
            $params,
            $anchor,
            $escape
        );
    }
}
