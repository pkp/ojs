{**
 * templates/frontend/pages/search.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
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

<main class="container main__content" id="immersion_content_main">
	<section>
		<header>
			<h1>
				<span>{translate key="common.search"}</span>
			</h1>
		</header>

		<div class="row">
			<aside class="col-md-4 search">
				{capture name="searchFormUrl"}{url op="search" escape=false}{/capture}
				{assign var=formUrlParameters value=[]}{* Prevent Smarty warning *}
				{$smarty.capture.searchFormUrl|parse_url:$smarty.const.PHP_URL_QUERY|parse_str:$formUrlParameters}
				<form class="search__form" method="get" action="{$smarty.capture.searchFormUrl|strtok:"?"|escape}">
					{foreach from=$formUrlParameters key=paramKey item=paramValue}
						<input type="hidden" name="{$paramKey|escape}" value="{$paramValue|escape}"/>
					{/foreach}
					<div class="form-group form-group-query">
						<label for="query">
							{translate key="common.searchQuery"}
						</label>
						<input type="search" class="form-control search__control" id="query" name="query" value="{$query|escape}">
					</div>
					<div class="form-group form-group-authors">
						<label for="authors">
							{translate key="search.author"}
						</label>
						<input type="text" class="form-control search__control" id="authors" name="authors" value="{$authors|escape}">
					</div>
					<div class="form-group form-group-date-from">
						<label for="dateFromYear">
							{translate key="search.dateFrom"}
						</label>
						<div class="form-control-date form-row">
							{html_select_date class="col form-control search__select" prefix="dateFrom" time=$dateFrom start_year=$yearStart end_year=$yearEnd year_empty="" month_empty="" day_empty="" field_order="YMD"}
						</div>
					</div>
					<div class="form-group form-group-date-to">
						<label for="dateToYear">
							{translate key="search.dateTo"}
						</label>
						<div class="form-control-date form-row">
							{html_select_date class="form-control search__select" prefix="dateTo" time=$dateTo start_year=$yearStart end_year=$yearEnd year_empty="" month_empty="" day_empty="" field_order="YMD"}
						</div>
					</div>
					<div class="form-group form-group-buttons">
						<button class="btn btn-primary" type="submit">{translate key="common.search"}</button>
					</div>
				</form>
			</aside>

			<section class="col-md-8">
				{* Search results, finally! *}
				<div class="search_results">
					{iterate from=results item=result}
						{include file="frontend/objects/article_summary.tpl" article=$result.publishedSubmission journal=$result.journal showDatePublished=true hideGalleys=true}
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
			</section>

		</div><!-- row -->
	</section>
</main><!-- .page -->

{include file="frontend/components/footer.tpl"}
