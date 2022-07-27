<?php

/**
 * @file classes/core/Request.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Request
 * @ingroup core
 *
 * @brief @verbatim Class providing operations associated with HTTP requests.
 * Requests are assumed to be in the format http://host.tld/index.php/<journal_id>/<page_name>/<operation_name>/<arguments...>
 * <journal_id> is assumed to be "index" for top-level site requests. @endverbatim
 */

namespace APP\core;

use APP\journal\Journal;
use PKP\core\PKPRequest;
use PKP\plugins\HookRegistry;

class Request extends PKPRequest
{
    /**
     * Deprecated
     *
     * @see PKPPageRouter::getRequestedContextPath()
     */
    public function getRequestedJournalPath()
    {
        static $journal;

        if (!isset($journal)) {
            $journal = $this->_delegateToRouter('getRequestedContextPath', 1);
            HookRegistry::call('Request::getRequestedJournalPath', [&$journal]);
        }

        return $journal;
    }

    /**
     * @see PKPPageRouter::getContext()
     */
    public function &getJournal()
    {
        $returner = $this->_delegateToRouter('getContext', 1);
        return $returner;
    }

    /**
     * Deprecated
     *
     * @see PKPPageRouter::getRequestedContextPath()
     *
     * @param null|mixed $contextLevel
     */
    public function getRequestedContextPath($contextLevel = null)
    {
        // Emulate the old behavior of getRequestedContextPath for
        // backwards compatibility.
        if (is_null($contextLevel)) {
            return $this->_delegateToRouter('getRequestedContextPaths');
        } else {
            return [$this->_delegateToRouter('getRequestedContextPath', $contextLevel)];
        }
    }

    /**
     * Deprecated
     *
     * @see PKPPageRouter::getContext()
     */
    public function &getContext($level = 1): ?Journal
    {
        $returner = $this->_delegateToRouter('getContext', $level);
        return $returner;
    }

    /**
     * Deprecated
     *
     * @see PKPPageRouter::getContextByName()
     */
    public function &getContextByName($contextName)
    {
        $returner = $this->_delegateToRouter('getContextByName', $contextName);
        return $returner;
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

    /**
     * Deprecated
     *
     * @see PageRouter::redirectHome()
     */
    public function redirectHome()
    {
        return $this->_delegateToRouter('redirectHome');
    }
}
