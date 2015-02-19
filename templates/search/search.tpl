{**
 * templates/search/search.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A unified search interface.
 *}
{* If this is an "instant search" request then we'll only produce the result list.
   Otherwise a complete search form will be returned. *}
{if !$instantSearch}
	{strip}
	{assign var="pageTitle" value="navigation.search"}
	{include file="common/header.tpl"}
	{/strip}

	<div id="search">
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
			<table class="data">
				<tr>
					<td class="label"><label for="query">{translate key="search.searchAllCategories"}</label></td>
					<td class="value">
						{capture assign="queryFilter"}{call_hook name="Templates::Search::SearchResults::FilterInput" filterName="query" filterValue=$query}{/capture}
						{if empty($queryFilter)}
							<input type="text" id="query" name="query" size="40" maxlength="255" value="{$query|escape}" class="textField" />
						{else}
							{$queryFilter}
						{/if}
						&nbsp;
						<input type="submit" value="{translate key="common.search"}" class="button defaultButton" />
					</td>
				</tr>
				{if $siteSearch}
					<tr>
						<td class="label"><label for="searchJournal">{translate key="search.withinJournal"}</label></td>
						<td class="value"><select name="searchJournal" id="searchJournal" class="selectMenu">{html_options options=$journalOptions selected=$searchJournal}</select></td>
					</tr>
				{/if}
				{if $hasActiveFilters}
					<tr>
						<td colspan="2" class="label"><h4>{translate key="search.activeFilters"}</h4></td>
					</tr>
					{include file="search/searchFilter.tpl" displayIf="activeFilter" filterName="authors" filterValue=$authors key="search.author"}
					{include file="search/searchFilter.tpl" displayIf="activeFilter" filterName="title" filterValue=$title key="article.title"}
					{include file="search/searchFilter.tpl" displayIf="activeFilter" filterName="abstract" filterValue=$abstract key="search.abstract"}
					{include file="search/searchFilter.tpl" displayIf="activeFilter" filterName="galleyFullText" filterValue=$galleyFullText key="search.fullText"}
					{include file="search/searchFilter.tpl" displayIf="activeFilter" filterType="date" filterName="dateFrom" filterValue=$dateFrom startYear=$startYear endYear=$endYear key="search.dateFrom"}
					{include file="search/searchFilter.tpl" displayIf="activeFilter" filterType="date" filterName="dateTo" filterValue=$dateTo startYear=$startYear endYear=$endYear key="search.dateTo"}
					{include file="search/searchFilter.tpl" displayIf="activeFilter" filterName="discipline" filterValue=$discipline key="search.discipline"}
					{include file="search/searchFilter.tpl" displayIf="activeFilter" filterName="subject" filterValue=$subject key="search.subject"}
					{include file="search/searchFilter.tpl" displayIf="activeFilter" filterName="type" filterValue=$type key="search.typeMethodApproach"}
					{include file="search/searchFilter.tpl" displayIf="activeFilter" filterName="coverage" filterValue=$coverage key="search.coverage"}
					{include file="search/searchFilter.tpl" displayIf="activeFilter" filterName="indexTerms" filterValue=$indexTerms key="search.indexTermsLong"}
				{/if}
			</table>
			<br/>
			{if $hasEmptyFilters}
				{capture assign="emptyFilters"}
					<table class="data">
						{if empty($authors) || empty($title) || empty($abstract) || empty($galleyFullText)}
							<tr>
								<td colspan="2" class="label"><h4>{translate key="search.searchCategories"}</h4></td>
							</tr>
							{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterName="authors" filterValue=$authors key="search.author"}
							{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterName="title" filterValue=$title key="article.title"}
							{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterName="abstract" filterValue=$abstract key="search.abstract"}
							{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterName="galleyFullText" filterValue=$galleyFullText key="search.fullText"}
						{/if}
						{if $dateFrom == '--' || $dateTo == '--'}
							<tr>
								<td colspan="2" class="formSubLabel"><h4>{translate key="search.date"}</h4></td>
							</tr>
							{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterType="date" filterName="dateFrom" filterValue=$dateFrom startYear=$startYear endYear=$endYear key="search.dateFrom"}
							{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterType="date" filterName="dateTo" filterValue=$dateTo startYear=$startYear endYear=$endYear key="search.dateTo"}
						{/if}
						{if empty($discipline) || empty($subject) || empty($type) || empty($coverage)}
							<tr>
								<td colspan="2" class="label"><h4>{translate key="search.indexTerms"}</h4></td>
							</tr>
							{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterName="discipline" filterValue=$discipline key="search.discipline"}
							{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterName="subject" filterValue=$subject key="search.subject"}
							{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterName="type" filterValue=$type key="search.typeMethodApproach"}
							{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterName="coverage" filterValue=$coverage key="search.coverage"}
							{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterName="indexTerms" filterValue=$indexTerms key="search.indexTermsLong"}
						{/if}
					</table>
					<p><input type="submit" value="{translate key="common.search"}" class="button defaultButton" /></p>
				{/capture}
				{include file="controllers/extrasOnDemand.tpl" id="emptyFilters" moreDetailsText="search.advancedSearchMore" lessDetailsText="search.advancedSearchLess" extraContent=$emptyFilters}
			{/if}
		</form>
	</div>
	<br />

	<div id="preResults" class="pkp_pages_search_preResults">
		{call_hook name="Templates::Search::SearchResults::PreResults"}

		<div id="searchOrdering" class="pkp_pages_search_preResults_ordering">
			{translate key="search.results.orderBy"}:&nbsp;
			<select id="searchResultOrder" name="searchResultOrder" class="selectMenu">
				{html_options options=$searchResultOrderOptions selected=$orderBy}
			</select>
			&nbsp;
			<select id="searchResultOrderDir" name="searchResultOrderDir" class="selectMenu">
				{html_options options=$searchResultOrderDirOptions selected=$orderDir}
			</select>
			&nbsp;

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
		<div style="clear: both"> </div>
	</div>

	<div id="results">
		<table class="listing">
{/if} {* See "instant search" comment at the top of the file *}
			{if $currentJournal}
				{assign var=numCols value=3}
			{else}
				{assign var=numCols value=4}
			{/if}
			<tr><td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td></tr>
			<tr class="heading" valign="bottom">
				{if !$currentJournal}<td>{translate key="context.context"}</td>{/if}
				<td width="{if !$currentJournal}20%{else}40%{/if}">{translate key="issue.issue"}</td>
				<td colspan="2">{translate key="article.title"}</td>
			</tr>
			<tr><td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td></tr>

			{iterate from=results item=result}
				{assign var=publishedArticle value=$result.publishedArticle}
				{assign var=article value=$result.article}
				{assign var=issue value=$result.issue}
				{assign var=issueAvailable value=$result.issueAvailable}
				{assign var=journal value=$result.journal}
				{assign var=section value=$result.section}
				<tr>
					{if !$currentJournal}
						<td><a href="{url journal=$journal->getPath()}">{$journal->getLocalizedName()|escape}</a></td>
					{/if}
					<td><a href="{url journal=$journal->getPath() page="issue" op="view" path=$issue->getBestIssueId($journal)}">{$issue->getIssueIdentification()|escape}</a></td>
					<td>{$article->getLocalizedTitle()|strip_unsafe_html}</td>
					<td align="right">
						{if $publishedArticle->getAccessStatus() == $smarty.const.ARTICLE_ACCESS_OPEN|| $issueAvailable}
							{assign var=hasAccess value=1}
						{else}
							{assign var=hasAccess value=0}
						{/if}
						{if $publishedArticle->getLocalizedAbstract() != ""}
							{assign var=hasAbstract value=1}
						{else}
							{assign var=hasAbstract value=0}
						{/if}
						{if !$hasAccess || $hasAbstract}
							<a href="{url journal=$journal->getPath() page="article" op="view" path=$publishedArticle->getBestArticleId($journal)}" class="file">
								{if !$hasAbstract}
									{translate key="article.details"}
								{else}
									{translate key="article.abstract"}
								{/if}
							</a>
						{/if}
						{if $hasAccess}
							{foreach from=$publishedArticle->getLocalizedGalleys() item=galley name=galleyList}
								&nbsp;<a href="{url journal=$journal->getPath() page="article" op="view" path=$publishedArticle->getBestArticleId($journal)|to_array:$galley->getBestGalleyId($journal)}" class="file">{$galley->getGalleyLabel()|escape}</a>
							{/foreach}
						{/if}
						{if $simDocsEnabled}
							{strip}
								&nbsp;
								<a href="{url op="similarDocuments" articleId=$publishedArticle->getId()}" class="file">
									{translate key="search.results.similarDocuments"}
								</a>
							{/strip}
						{/if}
					</td>
				</tr>
				<tr>
					<td colspan="{$numCols|escape}" style="padding-left: 30px;font-style: italic;">
						{foreach from=$article->getAuthors() item=authorItem name=authorList}
							{$authorItem->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
						{/foreach}
					</td>
				</tr>
				{call_hook name="Templates::Search::SearchResults::AdditionalArticleInfo" articleId=$publishedArticle->getId() numCols=$numCols|escape}
				<tr><td colspan="{$numCols|escape}" class="{if $results->eof()}end{/if}separator">&nbsp;</td></tr>
			{/iterate}
			{if $results->wasEmpty()}
				<tr>
					<td colspan="{$numCols|escape}" class="nodata">
						{if $error}
							{$error|escape}
						{else}
							{translate key="search.noResults"}
						{/if}
					</td>
				</tr>
				<tr><td colspan="{$numCols|escape}" class="endseparator">&nbsp;</td></tr>
			{else}
				<tr>
					<td {if !$currentJournal}colspan="2" {/if}align="left">{page_info iterator=$results}</td>
					<td colspan="2" align="right">{page_links anchor="results" iterator=$results name="search" query=$query searchJournal=$searchJournal authors=$authors title=$title abstract=$abstract galleyFullText=$galleyFullText discipline=$discipline subject=$subject type=$type coverage=$coverage indexTerms=$indexTerms dateFromMonth=$dateFromMonth dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateToMonth=$dateToMonth dateToDay=$dateToDay dateToYear=$dateToYear orderBy=$orderBy orderDir=$orderDir}</td>
				</tr>
			{/if}
{if !$instantSearch} {* See "instant search" comment at the top of the file *}
		</table>

		{capture assign="syntaxInstructions"}{call_hook name="Templates::Search::SearchResults::SyntaxInstructions"}{/capture}
		<p>
			{if empty($syntaxInstructions)}
				{translate key="search.syntaxInstructions"}
			{else}
				{* Must be properly escaped in the controller as we potentially get HTML here! *}
				{$syntaxInstructions}
			{/if}
		</p>
	</div>

	{include file="common/footer.tpl"}
{/if}
