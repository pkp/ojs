{**
 * templates/header/search.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common search box.
 *}
{if !$currentJournal || $currentJournal->getSetting('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
	<form class="pkp_search" action="{url page="search" op="search"}" method="post">
		<input name="query" value="{$searchQuery|escape}" type="text">
		<button>{translate key="common.search"}</button>
	</form>
{/if}
