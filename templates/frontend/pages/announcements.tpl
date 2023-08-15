{**
 * templates/frontend/pages/announcements.tpl
 *
 * Copyright (c) 2013-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * About the Journal Subscriptions.
 *
 *}

{extends file="layouts/backend.tpl"}
{block name="page"}
    <pkp-header></pkp-header>

    <h1 class="app__pageHeading">
        {translate key=$pageTitle}
    </h1>

    <ul>
        {foreach from=$announcements item=announcement}
            <li style="margin: 8px 0;">
                {$announcement->getId()}  / {$announcement->getLocalizedData('title')} / {$announcement->getDatePosted()} / {$announcement->getLocalizedData('keyword')} / <pkp-button element="a" href="http://localhost:8000/index.php/first-journal/exercise/announcements/{$announcement->getId()|strip_unsafe_html}" is-link>View</pkp-button>
            </li>
        {/foreach}
    </ul>
{/block}
