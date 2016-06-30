{**
 * templates/frontend/pages/search.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to search and view search results.
 * @todo The search template needs a pretty heavy rewrite. There's a lot of
 *  logic that could be performed in the handler and all of the filters and
 *  options are in need of a better presentation.
 * @todo Wrap the filters in fieldsets and ensure better accessibility
 *
 * @uses $instantSearch bool Are we displaying results for an instant search?
 * @uses $siteSearch bool Can we search multiple sites?
 * @uses $journalOptions array List of journals we can search
 * @uses $searchJournal int The currently selected journal for searching
 * @uses $activeFilters array Key value pair of filters used for this query
 * @uses $inactiveFilters array Key value pair of filters NOT used for this query
 *}
{include file="frontend/components/header.tpl" pageTitle="common.search"}

{* InstantSearch - return only results *}
{* @todo implement this *}
{if $instantSearch}

{* Full search page *}
{else}

<div class="page page_search">

	<h1 class="page_title">
		{translate key="common.search"}
	</h1>

	<script>
		$(function() {ldelim}
			// Attach the search form handler.
			$('#searchForm').pkpHandler(
				'$.pkp.pages.search.SearchFormHandler',
				{ldelim}
					instantSearch: {if $instantSearchEnabled}true{else}false{/if}
				{rdelim}
			);
		{rdelim});
	</script>
	<form method="post" id="searchForm" action="{url op="search"}">

		{* Main search input *}
		<h2 class="pkp_screen_reader">
			{translate key="search.searchFor"}
		</h2>
		<fieldset class="search_all">
			{* Repeat the label text just so that screen readers have a clear
			   label/input relationship *}
			<label class="pkp_screen_reader" for="query">
				{translate key="search.searchFor"}
			</label>
			<input type="text" id="query" name="query" value="{$query|escape}" class="query">

			{* Multiple sites *}
			{if $siteSearch}
				<div class="sites">
					<label for="searchJournal">
						{translate key="search.withinJournal"}
					</label>
					<select name="searchJournal" id="searchJournal">
						{html_options options=$journaloptions selected=$searchJournal}
					</select>
				</div>
			{/if}

			<div class="submit">
				<input type="submit" value="{translate key="common.search"}">
			</div>
		</fieldset>

		<div class="filters">

			{* Display active filters *}
			{if $hasActiveFilters}
				<h3>
					{translate key="search.activeFilters"}
				</h3>
				{include file="frontend/components/searchFilter.tpl" displayIf="activeFilter" filterName="authors" filterValue=$authors key="search.author"}
				{include file="frontend/components/searchFilter.tpl" displayIf="activeFilter" filterName="title" filterValue=$title key="article.title"}
				{include file="frontend/components/searchFilter.tpl" displayIf="activeFilter" filterName="abstract" filterValue=$abstract key="search.abstract"}
				{include file="frontend/components/searchFilter.tpl" displayIf="activeFilter" filterName="galleyFullText" filterValue=$galleyFullText key="search.fullText"}
				{include file="frontend/components/searchFilter.tpl" displayIf="activeFilter" filterType="date" filterName="dateFrom" filterValue=$dateFrom startYear=$startYear endYear=$endYear key="search.dateFrom"}
				{include file="frontend/components/searchFilter.tpl" displayIf="activeFilter" filterType="date" filterName="dateTo" filterValue=$dateTo startYear=$startYear endYear=$endYear key="search.dateTo"}
				{include file="frontend/components/searchFilter.tpl" displayIf="activeFilter" filterName="discipline" filterValue=$discipline key="search.discipline"}
				{include file="frontend/components/searchFilter.tpl" displayIf="activeFilter" filterName="subject" filterValue=$subject key="search.subject"}
				{include file="frontend/components/searchFilter.tpl" displayIf="activeFilter" filterName="type" filterValue=$type key="search.typeMethodApproach"}
				{include file="frontend/components/searchFilter.tpl" displayIf="activeFilter" filterName="coverage" filterValue=$coverage key="search.coverage"}
				{include file="frontend/components/searchFilter.tpl" displayIf="activeFilter" filterName="indexTerms" filterValue=$indexTerms key="search.indexTermsLong"}
			{/if}

			{* Display inactive filters *}
			{if $hasEmptyFilters}
				{capture assign="emptyFilters"}
						{if empty($authors) || empty($title) || empty($abstract) || empty($galleyFullText)}
							{include file="frontend/components/searchFilter.tpl" displayIf="emptyFilter" filterName="authors" filterValue=$authors key="search.author"}
							{include file="frontend/components/searchFilter.tpl" displayIf="emptyFilter" filterName="title" filterValue=$title key="article.title"}
							{include file="frontend/components/searchFilter.tpl" displayIf="emptyFilter" filterName="abstract" filterValue=$abstract key="search.abstract"}
							{include file="frontend/components/searchFilter.tpl" displayIf="emptyFilter" filterName="galleyFullText" filterValue=$galleyFullText key="search.fullText"}
						{/if}
						{if $dateFrom == '--' || $dateTo == '--'}
							<h3>
								{translate key="search.date"}
							</h3>
							{include file="frontend/components/searchFilter.tpl" displayIf="emptyFilter" filterType="date" filterName="dateFrom" filterValue=$dateFrom startYear=$startYear endYear=$endYear key="search.dateFrom"}
							{include file="frontend/components/searchFilter.tpl" displayIf="emptyFilter" filterType="date" filterName="dateTo" filterValue=$dateTo startYear=$startYear endYear=$endYear key="search.dateTo"}
						{/if}
						{if empty($discipline) || empty($subject) || empty($type) || empty($coverage)}
							<h3>
								{translate key="search.indexTerms"}
							</h3>
							{include file="frontend/components/searchFilter.tpl" displayIf="emptyFilter" filterName="discipline" filterValue=$discipline key="search.discipline"}
							{include file="frontend/components/searchFilter.tpl" displayIf="emptyFilter" filterName="subject" filterValue=$subject key="search.subject"}
							{include file="frontend/components/searchFilter.tpl" displayIf="emptyFilter" filterName="type" filterValue=$type key="search.typeMethodApproach"}
							{include file="frontend/components/searchFilter.tpl" displayIf="emptyFilter" filterName="coverage" filterValue=$coverage key="search.coverage"}
							{include file="frontend/components/searchFilter.tpl" displayIf="emptyFilter" filterName="indexTerms" filterValue=$indexTerms key="search.indexTermsLong"}
						{/if}
					<input type="submit" value="{translate key="common.search"}">
				{/capture}

				{$emptyFilters}
			{/if}

		</div><!-- .filters -->

		{* Pre-results *}
		{* @todo Find out what pre-results are and treat them appropriately *}
		<div class="preresults">
			{call_hook name="Templates::Search::SearchResults::PreResults"}

			<div class="preresults_ordering">
				{translate key="search.results.orderBy"}:&nbsp;
				<select id="searchResultOrder" name="searchResultOrder" class="selectMenu">
					{html_options options=$searchResultOrderOptions selected=$orderBy}
				</select>
				<select id="searchResultOrderDir" name="searchResultOrderDir" class="selectMenu">
					{html_options options=$searchResultOrderDirOptions selected=$orderDir}
				</select>

				<script type="text/javascript">
					// Get references to the required elements.
					var $orderBySelect = $('#content #searchResultOrder');
					var $orderDirSelect = $('#content #searchResultOrderDir');

					function searchResultReorder(useDefaultOrderDir) {ldelim}
						var reorderUrl = '{strip}
								{url query=$query searchJournal=$searchJournal
									authors=$authors title=$title abstract=$abstract galleyFullText=$galleyFullText
									discipline=$discipline subject=$subject type=$type coverage=$coverage
									dateFromMonth=$dateFromMonth dateFromDay=$dateFromDay dateFromYear=$dateFromYear
									dateToMonth=$dateToMonth dateToDay=$dateToDay dateToYear=$dateToYear escape=false}
							{/strip}';
						var orderBy = $orderBySelect.val();
						if (useDefaultOrderDir) {ldelim}
							var orderDir = '';
						{rdelim} else {ldelim}
							var orderDir = $orderDirSelect.val();
						{rdelim}
						reorderUrl += '&orderBy=' + orderBy + '&orderDir=' + orderDir;
						window.location = reorderUrl;
					{rdelim}

					$orderBySelect.change(function() {ldelim} searchResultReorder(true); {rdelim});
					$orderDirSelect.change(function() {ldelim} searchResultReorder(false); {rdelim});
				</script>
			</div>
		</div>

		{* Search results, finally! *}
		<ul class="results">
			{iterate from=results item=result}
				{assign var=publishedArticle value=$result.publishedArticle}
				{assign var=article value=$result.article}
				{assign var=issue value=$result.issue}
				{assign var=issueAvailable value=$result.issueAvailable}
				{assign var=journal value=$result.journal}
				{assign var=section value=$result.section}
				{assign var=galleys value=$publishedArticle->getLocalizedGalleys()}
				{if $publishedArticle->getAccessStatus() == $smarty.const.ARTICLE_ACCESS_OPEN || $issueAvailable}
					{assign var=hasArticleAccess value=true}
				{else}
					{assign var=hasArticleAccess value=false}
				{/if}
				{if !$section->getHideAuthor() && ($article->getHideAuthor() == $smarty.const.AUTHOR_TOC_DEFAULT || $article->getHideAuthor() == $smarty.const.AUTHOR_TOC_SHOW)}
					{assign var="showAuthor" value=true}
				{else}
					{assign var="showAuthor" value=false}
				{/if}
				<li{if $galleys} class="has_galleys"{/if}>
					<a class="title" href="{url journal=$journal->getPath() page="article" op="view" path=$publishedArticle->getBestArticleId()}">
						{$article->getLocalizedTitle()|strip_unsafe_html}
					</a>
					{if $showAuthor}
						<span class="authors">
							{$article->getAuthorString()}
						</span>
					{/if}
					{if !$currentJournal}
						<a class="journal" href="{url journal=$journal->getPath()}">{$journal->getLocalizedName()|escape}</a>
					{/if}
					<a class="issue" href="{url journal=$journal->getPath() page="issue" op="view" path=$issue->getBestIssueId()}">{$issue->getIssueIdentification()|escape}</a>
					{if $galleys}
						<ul class="galley_links">
							{foreach from=$galleys item=galley}
								<li>
									{* @todo the galley link template expects a few arguments which
									   may not be available in this context: $restrictOnlyPdf and
									   $purchaseArticleEnable. Needs testing *}
									{include file="frontend/objects/galley_link.tpl" parent=$publishedArticle hasAccess=$hasArticleAccess journalOverride=$journal}
								</li>
							{/foreach}

							{if $simDocsEnabled}
								<li>
									<a class="obj_galley_link" href="{url op="similarDocuments" articleId=$publishedArticle->getId()}">
										{translate key="search.results.similarDocuments"}
									</a>
								</li>
							{/if}
						</ul>
					{/if}
					{call_hook name="Templates::Search::SearchResults::AdditionalArticleInfo" articleId=$publishedArticle->getId()}
				</li>
			{/iterate}
		</ul>

		{* No results found *}
		{if $results->wasEmpty()}
			{if $error}
				{include file="frontend/components/notification.tpl" type="error" message=$error|escape}
			{else}
				{include file="frontend/components/notification.tpl" type="notice" messageKey="search.noResults"}
			{/if}

		{* Results pagination *}
		{else}
			{page_info iterator=$results}
			{page_links anchor="results" iterator=$results name="search" query=$query searchJournal=$searchJournal authors=$authors title=$title abstract=$abstract galleyFullText=$galleyFullText discipline=$discipline subject=$subject type=$type coverage=$coverage indexTerms=$indexTerms dateFromMonth=$dateFromMonth dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateToMonth=$dateToMonth dateToDay=$dateToDay dateToYear=$dateToYear orderBy=$orderBy orderDir=$orderDir}
			</tr>
		{/if}

		<div class="instructions">
			{capture assign="syntaxInstructions"}
				{call_hook name="Templates::Search::SearchResults::SyntaxInstructions"}
			{/capture}
			{if $syntaxInstructions}
				{$syntaxInstructions}
			{else}
				{translate key="search.syntaxInstructions"}
			{/if}
		</div>
	</form>
</div><!-- .page -->

{/if}

{include file="common/frontend/footer.tpl"}
