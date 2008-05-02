{**
 * submissions.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Editor submissions page(s).
 *
 * $Id$
 *}
{assign var="pageTitle" value="common.queue.long.$pageToDisplay"}
{url|assign:"currentUrl" page="editor"}{include file="common/header.tpl"}

<ul class="menu">
	<li{if $pageToDisplay == "submissionsUnassigned"} class="current"{/if}><a href="{url op="submissions" path="submissionsUnassigned"}">{translate key="common.queue.short.submissionsUnassigned"}</a></li>
	<li{if $pageToDisplay == "submissionsInReview"} class="current"{/if}><a href="{url op="submissions" path="submissionsInReview"}">{translate key="common.queue.short.submissionsInReview"}</a></li>
	<li{if $pageToDisplay == "submissionsInEditing"} class="current"{/if}><a href="{url op="submissions" path="submissionsInEditing"}">{translate key="common.queue.short.submissionsInEditing"}</a></li>
	<li{if $pageToDisplay == "submissionsArchives"} class="current"{/if}><a href="{url op="submissions" path="submissionsArchives"}">{translate key="common.queue.short.submissionsArchives"}</a></li>
</ul>
<ul class="filter">
	<li>&#187; {if $filterEditor == "filterEditor"}<a href="{url op="submissions" path=$pageToDisplay|to_array:"allEditors"}">{translate key="editor.submissions.showAll"}</a>{else}<a href="{url op="submissions" path=$pageToDisplay|to_array:"filterEditor"}">{translate key="editor.submissions.filterEditor"}</a>{/if}</li>
</ul>
<br />

{if !$dateFrom}
{assign var="dateFrom" value="--"}
{/if}

{if !$dateTo}
{assign var="dateTo" value="--"}
{/if}

<form method="post" name="submit" action="{url op="submissions" path=$pageToDisplay}">
	{if $section}<input type="hidden" name="section" value="{$section|escape:"quotes"}"/>{/if}
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" value="{$search|escape}" />
	<br/>
	<select name="dateSearchField" size="1" class="selectMenu">
		{html_options_translate options=$dateFieldOptions selected=$dateSearchField}
	</select>
	{translate key="common.between"}
	{html_select_date prefix="dateFrom" time=$dateFrom all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="-5" end_year="+1"}
	{translate key="common.and"}
	{html_select_date prefix="dateTo" time=$dateTo all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="-5" end_year="+1"}
	<br/>
	<input type="submit" value="{translate key="common.search"}" class="button" />
</form>
&nbsp;

{include file="editor/$pageToDisplay.tpl"}
<form action="#">
{translate key="section.section"}: <select name="section" onchange="location.href='{url|escape path=$pageToDisplay searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth section="SECTION_ID" escape=false}'.replace('SECTION_ID', this.options[this.selectedIndex].value)" size="1" class="selectMenu">{html_options options=$sectionOptions selected=$section}</select>
</form>

{if ($pageToDisplay == "submissionsInReview")}
<br />
<h4>{translate key="common.notes"}</h4>
<p>{translate key="editor.submissionReview.notes"}</p>
{/if}

{include file="common/footer.tpl"}
