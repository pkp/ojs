{**
 * templates/frontend/pages/search.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to search and view search results.
 *
 * @uses $query Value of the primary search query
 * @uses $authors Value of the authors search filter
 * @uses $dateFrom Value of the date from search filter (published after).
 *  Value is a single string: YYYY-MM-DD HH:MM:SS
 * @uses $dateTo Value of the date to search filter (published before).
 *  Value is a single string: YYYY-MM-DD HH:MM:SS
 * @uses $yearStart Earliest year that can be used in from/to filters
 * @uses $yearEnd Latest year that can be used in from/to filters
 *}
{include file="frontend/components/header.tpl" pageTitle="common.search"}

<div class="page page_search">

	{include file="frontend/components/breadcrumbs.tpl" currentTitleKey="common.search"}

	<form class="cmp_form" method="post" action="{url op="search"}">
		{csrf}

		{* Repeat the label text just so that screen readers have a clear
		   label/input relationship *}
		<div class="search_input">
			<label class="pkp_screen_reader" for="query">
				{translate key="search.searchFor"}
			</label>
			{block name=searchQuery}
				<input type="text" id="query" name="query" value="{$query|escape}" class="query" placeholder="{translate|escape key="common.search"}">
			{/block}
		</div>

		<fieldset class="search_advanced">
			<legend>
				{translate key="search.advancedFilters"}
			</legend>
			<div class="date_range">
				<div class="from">
					<label class="label">
						{translate key="search.dateFrom"}
					</label>
					{html_select_date prefix="dateFrom" time=$dateFrom start_year=$yearStart end_year=$yearEnd year_empty="" month_empty="" day_empty="" field_order="YMD"}
				</div>
				<div class="to">
					<label class="label">
						{translate key="search.dateTo"}
					</label>
					{html_select_date prefix="dateTo" time=$dateTo start_year=$yearStart end_year=$yearEnd year_empty="" month_empty="" day_empty="" field_order="YMD"}
				</div>
			</div>
			<div class="author">
				<label class="label" for="authors">
					{translate key="search.author"}
				</label>
				{block name=searchAuthors}
					<input type="text" for="authors" name="authors" value="{$authors|escape}">
				{/block}
			</div>
			{call_hook name="Templates::Search::SearchResults::AdditionalFilters"}
		</fieldset>

		<div class="submit">
			<button class="submit" type="submit">{translate key="common.search"}</button>
		</div>
	</form>

	{call_hook name="Templates::Search::SearchResults::PreResults"}

	{* Search results, finally! *}
	<div class="search_results">
		{iterate from=results item=result}
			{include file="frontend/objects/article_summary.tpl" article=$result.publishedArticle journal=$result.journal showDatePublished=true hideGalleys=true}
		{/iterate}
	</div>

	{* No results found *}
	{if $results->wasEmpty()}
		{if $error}
			{include file="frontend/components/notification.tpl" type="error" message=$error|escape}
		{else}
			{include file="frontend/components/notification.tpl" type="notice" messageKey="search.noResults"}
		{/if}

	{* Results pagination *}
	{else}
		<div class="cmp_pagination">
			{page_info iterator=$results}
			{page_links anchor="results" iterator=$results name="search" query=$query searchJournal=$searchJournal authors=$authors title=$title abstract=$abstract galleyFullText=$galleyFullText discipline=$discipline subject=$subject type=$type coverage=$coverage indexTerms=$indexTerms dateFromMonth=$dateFromMonth dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateToMonth=$dateToMonth dateToDay=$dateToDay dateToYear=$dateToYear orderBy=$orderBy orderDir=$orderDir}
		</div>
	{/if}
</div><!-- .page -->

{include file="frontend/components/footer.tpl"}
