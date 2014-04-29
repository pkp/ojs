{**
 * templates/search/searchFilter.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Search filter template.
 *
 * Parameters:
 *   $filterType string Can be "date" or "text" (default: text)
 *   $filterName string
 *   $filterValue string
 *   $key string The translation key for the field name.
 *   $displayIf string Can be "emptyFilter" or "activeFilter".
 *   $startYear string Required for filter type "date".
 *   $endYear string Required for filter type "date".
 *}
{if empty($filterValue) || ($filterType == "date" && $filterValue == "--")}
	{assign var="isEmptyFilter" value=1}
{else}
	{assign var="isEmptyFilter" value=0}
{/if}
{if ($displayIf == "emptyFilter" && $isEmptyFilter) || ($displayIf == "activeFilter" && !$isEmptyFilter)}
	<tr>
		<td class="label">
			<label for="{$filterName}">{translate key=$key}</label>
		</td>
		<td class="value">
			{if $filterType == "date"}
				{html_select_date prefix=$filterName time=$filterValue all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="$startYear" end_year="$endYear"}
				{if $filterName == "dateTo"}
					<input type="hidden" name="dateToHour" value="23" />
					<input type="hidden" name="dateToMinute" value="59" />
					<input type="hidden" name="dateToSecond" value="59" />
				{/if}
			{else}
				{capture assign="filterInput"}{call_hook name="Templates::Search::SearchResults::FilterInput" filterName=$filterName filterValue=$filterValue}{/capture}
				{if empty($filterInput)}
					<input type="text" name="{$filterName}" id="{$filterName}" size="40" maxlength="255" value="{$filterValue|escape}" class="textField">
				{else}
					{$filterInput}
				{/if}
			{/if}
			{if $displayIf == "activeFilter"}
				&nbsp;
				{* Temporarily remove the filter *}
				{if $filterType == "date"}
					{assign var="monthVar" value=$filterName|cat:"Month"}
					{assign var="dayVar" value=$filterName|cat:"Day"}
					{assign var="yearVar" value=$filterName|cat:"Year"}
					{assign var="originalMonth" value=$monthVar}
					{assign var="originalDay" value=$monthVar}
					{assign var="originalYear" value=$monthVar}
					{assign var=$monthVar value=""}
					{assign var=$dayVar value=""}
					{assign var=$yearVar value=""}
				{else}
					{assign var=$filterName value=""}
				{/if}
				{* Display a link to the same search query without this filter *}
				<a href="{url query=$query searchJournal=$searchJournal abstract=$abstract authors=$authors title=$title
							galleyFullText=$galleyFullText suppFiles=$suppFiles discipline=$discipline subject=$subject
							type=$type coverage=$coverage indexTerms=$indexTerms
							dateFromMonth=$dateFromMonth dateFromDay=$dateFromDay dateFromYear=$dateFromYear
							dateToMonth=$dateToMonth dateToDay=$dateToDay dateToYear=$dateToYear
							orderBy=$orderBy orderDir=$orderDir}">
					{translate key="search.deleteFilter"}
				</a>
				{* Restore the filter *}
				{if $filterType == "date"}
					{assign var=$monthVar value=$originalMonth}
					{assign var=$dayVar value=$originalDay}
					{assign var=$yearVar value=$originalYear}
				{else}
					{assign var=$filterName value=$filterValue}
				{/if}
			{/if}
		</td>
	</tr>
{/if}
