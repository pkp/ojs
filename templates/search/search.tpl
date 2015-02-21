{**
 * templates/search/search.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A unified search interface.
 *}
{strip}
{assign var="pageTitle" value="navigation.search"}
{include file="common/header.tpl"}
{/strip}

<div id="search">
	<script type="text/javascript">
		$(function() {ldelim}
			// Attach the form handler.
			$('#searchForm').pkpHandler('$.pkp.pages.search.SearchFormHandler');
		{rdelim});
	</script>
	<form method="post" id="searchForm" action="{url op="search"}">
		<table class="data">
			<tr valign="top">
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
				<tr valign="top">
					<td class="label"><label for="searchJournal">{translate key="search.withinJournal"}</label></td>
					<td class="value"><select name="searchJournal" id="searchJournal" class="selectMenu">{html_options options=$journalOptions selected=$searchJournal}</select></td>
				</tr>
			{/if}
			{if $hasActiveFilters}
				<tr valign="top">
					<td colspan="2" class="label"><h4>{translate key="search.activeFilters"}</h4></td>
				</tr>
				{include file="search/searchFilter.tpl" displayIf="activeFilter" filterName="authors" filterValue=$authors key="search.author"}
				{include file="search/searchFilter.tpl" displayIf="activeFilter" filterName="title" filterValue=$title key="article.title"}
				{include file="search/searchFilter.tpl" displayIf="activeFilter" filterName="abstract" filterValue=$abstract key="search.abstract"}
				{include file="search/searchFilter.tpl" displayIf="activeFilter" filterName="galleyFullText" filterValue=$galleyFullText key="search.fullText"}
				{include file="search/searchFilter.tpl" displayIf="activeFilter" filterName="suppFiles" filterValue=$suppFiles key="article.suppFiles"}
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
					{if empty($authors) || empty($title) || empty($abstract) || empty($galleyFullText) || empty($suppFiles)}
						<tr valign="top">
							<td colspan="2" class="label"><h4>{translate key="search.searchCategories"}</h4></td>
						</tr>
						{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterName="authors" filterValue=$authors key="search.author"}
						{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterName="title" filterValue=$title key="article.title"}
						{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterName="abstract" filterValue=$abstract key="search.abstract"}
						{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterName="galleyFullText" filterValue=$galleyFullText key="search.fullText"}
						{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterName="suppFiles" filterValue=$suppFiles key="article.suppFiles"}
					{/if}
					{if $dateFrom == '--' || $dateTo == '--'}
						<tr valign="top">
							<td colspan="2" class="formSubLabel"><h4>{translate key="search.date"}</h4></td>
						</tr>
						{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterType="date" filterName="dateFrom" filterValue=$dateFrom startYear=$startYear endYear=$endYear key="search.dateFrom"}
						{include file="search/searchFilter.tpl" displayIf="emptyFilter" filterType="date" filterName="dateTo" filterValue=$dateTo startYear=$startYear endYear=$endYear key="search.dateTo"}
					{/if}
					{if empty($discipline) || empty($subject) || empty($type) || empty($coverage)}
						<tr valign="top">
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

{call_hook name="Templates::Search::SearchResults::PreResults"}

{if $currentJournal}
	{assign var=numCols value=3}
{else}
	{assign var=numCols value=4}
{/if}

<div id="results">
	<table width="100%" class="listing">
		<tr><td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td></tr>
		<tr class="heading" valign="bottom">
			{if !$currentJournal}<td width="20%">{translate key="journal.journal"}</td>{/if}
			<td width="{if !$currentJournal}20%{else}40%{/if}">{translate key="issue.issue"}</td>
			<td width="60%" colspan="2">{translate key="article.title"}</td>
		</tr>
		<tr><td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td></tr>

		{iterate from=results item=result}
			{assign var=publishedArticle value=$result.publishedArticle}
			{assign var=article value=$result.article}
			{assign var=issue value=$result.issue}
			{assign var=issueAvailable value=$result.issueAvailable}
			{assign var=journal value=$result.journal}
			{assign var=section value=$result.section}
			<tr valign="top">
				{if !$currentJournal}
					<td><a href="{url journal=$journal->getPath()}">{$journal->getLocalizedTitle()|escape}</a></td>
				{/if}
				<td><a href="{url journal=$journal->getPath() page="issue" op="view" path=$issue->getBestIssueId($journal)}">{$issue->getIssueIdentification()|escape}</a></td>
				<td width="30%">{$article->getLocalizedTitle()|strip_unsafe_html}</td>
				<td width="30%" align="right">
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
					{call_hook name="Templates::Search::SearchResults::AdditionalArticleLinks" articleId=$publishedArticle->getId()}
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
				<td colspan="2" align="right">{page_links anchor="results" iterator=$results name="search" query=$query searchJournal=$searchJournal authors=$authors title=$title abstract=$abstract galleyFullText=$galleyFullText suppFiles=$suppFiles discipline=$discipline subject=$subject type=$type coverage=$coverage indexTerms=$indexTerms dateFromMonth=$dateFromMonth dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateToMonth=$dateToMonth dateToDay=$dateToDay dateToYear=$dateToYear orderBy=$orderBy orderDir=$orderDir}</td>
			</tr>
		{/if}
	</table>

	{capture assign="syntaxInstructions"}{call_hook name="Templates::Search::SearchResults::SyntaxInstructions"}{/capture}
		{if empty($syntaxInstructions)}
			{translate key="search.syntaxInstructions"}
		{else}
			{* Must be properly escaped in the controller as we potentially get HTML here! *}
			{$syntaxInstructions}
		{/if}
</div>

{include file="common/footer.tpl"}

