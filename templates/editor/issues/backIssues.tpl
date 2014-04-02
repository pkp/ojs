{**
 * templates/editor/issues/backIssues.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Listings of back issues
 *
 *}
{strip}
{assign var="pageTitle" value="editor.issues.backIssues"}
{assign var="page" value=$rangeInfo->getPage()}
{url|assign:"currentUrl" page="editor" op="backIssues"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
$(document).ready(function() { setupTableDND("#dragTable", "moveIssue"); });
{/literal}
</script>

<ul class="menu">
        <li><a href="{url op="createIssue"}">{translate key="editor.navigation.createIssue"}</a></li>
        <li><a href="{url op="futureIssues"}">{translate key="editor.navigation.futureIssues"}</a></li>
        <li class="current"><a href="{url op="backIssues"}">{translate key="editor.navigation.issueArchive"}</a></li>
</ul>

<br/>

{if $usesCustomOrdering}
	{url|assign:"resetUrl" op="resetIssueOrder"}
	<p>{translate key="editor.issues.resetIssueOrder" url=$resetUrl}</p>
{/if}

<div id="issues">
<table width="100%" class="listing" id="dragTable">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="60%">{translate key="issue.issue"}</td>
		<td width="15%">{translate key="editor.issues.published"}</td>
		<td width="15%">{translate key="editor.issues.numArticles"}</td>
		<td width="5%">{translate key="common.order"}</td>
		<td width="5%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>

	{iterate from=issues item=issue}
	<tr valign="top" class="data" id="issue-{$issue->getId()}">
		<td class="drag"><a href="{url op="issueToc" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a></td>
		<td class="drag">{$issue->getDatePublished()|date_format:"$dateFormatShort"|default:"&mdash;"}</td>
		<td class="drag">{$issue->getNumArticles()|escape}</td>
		<td><a href="{url op="moveIssue" d=u id=$issue->getId() issuesPage=$page }">&uarr;</a>	<a href="{url op="moveIssue" d=d id=$issue->getId() issuesPage=$page }">&darr;</a></td>
		<td align="right"><a href="{url op="removeIssue" path=$issue->getId() issuesPage=$page }" onclick="return confirm('{translate|escape:"jsparam" key="editor.issues.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
{/iterate}
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{if $issues->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="issue.noIssues"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$issues}</td>
		<td colspan="3" align="right">{page_links anchor="issues" name="issues" iterator=$issues}</td>
	</tr>
{/if}
</table>

<form action="{url op="setCurrentIssue"}" method="post">
	{translate key="journal.currentIssue"}&nbsp;&nbsp;
	<select name="issueId" class="selectMenu">
		<option value="">{translate key="common.none"}</option>
		{html_options options=$allIssues|truncate:40:"..." selected=$currentIssueId}
	</select>
	<input type="submit" value="{translate key="common.record"}" class="button defaultButton" />
</form>
</div>
{include file="common/footer.tpl"}

