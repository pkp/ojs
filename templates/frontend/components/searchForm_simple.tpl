{**
 * templates/frontend/components/searchForm_simple.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Simple display of a search form with just text input and search button
 *
 * @uses $searchQuery string Previously input search query
 *}
{if !$currentJournal || $currentJournal->getData('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
	<form class="pkp_search" action="{url page="search" op="search"}" method="post" role="search">
		{csrf}
		{capture assign="queryFilter"}{call_hook name="Templates::Search::SearchResults::FilterInput" filterName="simpleQuery" filterValue=$query}{/capture}
		{if empty($queryFilter)}
			<input name="query" value="{$searchQuery|escape}" type="text" aria-label="{translate|escape key="common.searchQuery"}">
		{else}
			{$queryFilter}
		{/if}
		<button type="submit">
			{translate key="common.search"}
		</button>
		<div class="search_controls" aria-hidden="true">
			<a href="{url page="search" op="search"}" class="headerSearchPrompt search_prompt" aria-hidden="true">
				{translate key="common.search"}
			</a>
			<a href="#" class="search_cancel headerSearchCancel" aria-hidden="true"></a>
			<span class="search_loading" aria-hidden="true"></span>
		</div>
</form>
{/if}
