{**
 * futureIssues.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Listings of future (unpublished) issues for Layout Editor.
 *
 * $Id$
 *}
{assign var="pageTitle" value="editor.issues.futureIssues"}
{url|assign:"currentUrl" page="layoutEditor" op="futureIssues"}
{include file="common/header.tpl"}

<ul class="menu">
        <li class="current"><a href="{url op="futureIssues"}">{translate key="editor.navigation.futureIssues"}</a></li>
        <li><a href="{url op="backIssues"}">{translate key="editor.navigation.issueArchive"}</a></li>
</ul>

<br />

<a name="issues"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="80%">{translate key="issue.issue"}</td>
		<td width="20%">{translate key="editor.issues.numArticles"}</td>
	</tr>
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
	{iterate from=issues item=issue}
	<tr valign="top">
		<td><a href="{url op="issueToc" path=$issue->getIssueId()}" class="action">{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a></td>
		<td>{$issue->getNumArticles()}</td>
	</tr>
	<tr>
		<td colspan="2" class="{if $issues->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $issues->wasEmpty()}
	<tr>
		<td colspan="2" class="nodata">{translate key="issue.noIssues"}</td>
	</tr>
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$issues}</td>
		<td align="right">{page_links anchor="issues" name="issues" iterator=$issues}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
