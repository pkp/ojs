{**
 * index.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Editor index.
 *
 * $Id$
 *}
{assign var="pageTitle" value="editor.home"}
{assign var="pageCrumbTitle" value="user.role.editor"}
{include file="common/header.tpl"}

<h3>{translate key="article.submissions"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="submissions" path="submissionsUnassigned"}">{translate key="common.queue.short.submissionsUnassigned"}</a>&nbsp;({if $submissionsCount[0]}{$submissionsCount[0]}{else}0{/if})</li>
	<li>&#187; <a href="{url op="submissions" path="submissionsInReview"}">{translate key="common.queue.short.submissionsInReview"}</a>&nbsp;({if $submissionsCount[1]}{$submissionsCount[1]}{else}0{/if})</li>
	<li>&#187; <a href="{url op="submissions" path="submissionsInEditing"}">{translate key="common.queue.short.submissionsInEditing"}</a>&nbsp;({if $submissionsCount[2]}{$submissionsCount[2]}{else}0{/if})</li>
	<li>&#187; <a href="{url op="submissions" path="submissionsArchives"}">{translate key="common.queue.short.submissionsArchives"}</a></li>
	{call_hook name="Templates::Editor::Index::Submissions"}
</ul>

<div class="separator">&nbsp;</div>

&nbsp;<br />

{if !$dateFrom}
{assign var="dateFrom" value="--"}
{/if}

{if !$dateTo}
{assign var="dateTo" value="--"}
{/if}

<form method="post" name="submit" action="{url path="search"}">
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

{if $displayResults}

<a name="submissions"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="6" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="30%">{translate key="article.authors"}</td>
		<td width="40%">{translate key="article.title"}</td>
		<td width="15%">{translate key="common.status"}</td>
	</tr>
	<tr>
		<td colspan="6" class="headseparator">&nbsp;</td>
	</tr>
	
	{iterate from=submissions item=submission}
	{assign var="highlightClass" value=$submission->getHighlightClass()}
	{assign var="fastTracked" value=$submission->getFastTracked()}
	<tr valign="top"{if $highlightClass || $fastTracked} class="{$highlightClass|escape} {if $fastTracked}fastTracked{/if}"{/if}>
		<td>{$submission->getArticleId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()|escape}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submission" path=$submission->getArticleId()}" class="action">{$submission->getArticleTitle()|strip_unsafe_html|truncate:60:"..."}</a></td>
		<td align="right">
			{assign var="status" value=$submission->getSubmissionStatus()}
			{if $status == STATUS_ARCHIVED}
				{translate key="submissions.archived"}
			{elseif $status == STATUS_PUBLISHED}
				{print_issue_id articleId=$submission->getArticleId()}	
			{elseif $status == STATUS_DECLINED}
				{translate key="submissions.declined"}&nbsp;&nbsp;<a href="{url op="deleteSubmission" path=$articleId}" onclick="return confirm('{translate|escape:"jsparam" key="editor.submissionArchive.confirmDelete"}')" class="action">{translate key="common.delete"}</a>
			{elseif $status==STATUS_QUEUED_UNASSIGNED}{translate key="submissions.queuedUnassigned"}
			{elseif $status==STATUS_QUEUED_EDITING}{translate key="submissions.queuedEditing"}
			{elseif $status==STATUS_QUEUED_REVIEW}{translate key="submissions.queuedReview"}
			{else}{* SUBMISSION_QUEUED -- between cracks? *}
				{translate key="submissions.queued"}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="6" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="6" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="6" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="4" align="left">{page_info iterator=$submissions}</td>
		<td align="right" colspan="2">{page_links anchor="submissions" name="submissions" iterator=$submissions searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth section=$section}</td>
	</tr>
{/if}
</table>

{else}

<div class="separator">&nbsp;</div>

{/if}{* displayResults *}

<h3>{translate key="editor.navigation.issues"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="createIssue"}">{translate key="editor.navigation.createIssue"}</a></li>
	<li>&#187; <a href="{url op="notifyUsers"}">{translate key="editor.notifyUsers"}</a></li>
	<li>&#187; <a href="{url op="futureIssues"}">{translate key="editor.navigation.futureIssues"}</a></li>
	<li>&#187; <a href="{url op="backIssues"}">{translate key="editor.navigation.issueArchive"}</a></li>
	{call_hook name="Templates::Editor::Index::Issues"}
</ul>

{include file="common/footer.tpl"}
