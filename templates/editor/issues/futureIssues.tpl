{**
 * futureIssues.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Listings of future (unpublished) issues.
 *
 * $Id$
 *}
{strip}
{* 20111201 BLH Display 'Unpublished Content' as Page Title for UCLA Encyclopedia of Egyptology*}
{if $journalPath == 'nelc_uee'}
	{assign var="pageTitle" value="editor.issues.unpublishedContent"}
{else}
	{assign var="pageTitle" value="editor.issues.futureIssues"}
{/if}
{url|assign:"currentUrl" page="editor" op="futureIssues"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	{* 20111201 BLH Hide 'Create Issue' link for UEE *}
	{if $journalPath != 'nelc_uee' || $isSiteAdmin}
		<li><a href="{url op="createIssue"}">{translate key="editor.navigation.createIssue"}</a></li>
	{/if}
	{* 20111201 BLH Diplay 'Unpublished Content' & 'Published Content' for UCLA Encyclopedia of Egyptology*}
	{* 20120502 LS Display only 'Published Content' for UEE *}
	{if $journalPath == 'nelc_uee'}
		<li><a href="{url op="backIssues"}">{translate key="editor.navigation.publishedContent"}</a></li>
	{else}
		<li class="current"><a href="{url op="futureIssues"}">{translate key="editor.navigation.futureIssues"}</a></li>
		<li><a href="{url op="backIssues"}">{translate key="editor.navigation.issueArchive"}</a></li>
	{/if}
</ul>

<br/>

<div id="issues">
<table width="100%" class="listing">
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="80%">{translate key="issue.issue"}</td>
		<td width="15%">{translate key="editor.issues.numArticles"}</td>
		{* 20111026 BLH Removed for non-siteAdmins - shouldn't be able to delete whole unpublished issues *}
		{if $isSiteAdmin}
		<td width="5%" align="right">{translate key="common.action"}</td>
		{/if}
	</tr>
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
	{iterate from=issues item=issue}
	<tr valign="top">
		<td><a href="{url op="issueToc" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a></td>
		<td>{$issue->getNumArticles()|escape}</td>
		{* 20111026 BLH Removed for non-siteAdmins - shouldn't be able to delete whole unpublished issues *}
		{if $isSiteAdmin}
		<td align="right"><a href="{url op="removeIssue" path=$issue->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="editor.issues.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
		{/if}
	</tr>
	<tr>
		<td colspan="3" class="{if $issues->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $issues->wasEmpty()}
	<tr>
		<td colspan="3" class="nodata">{translate key="issue.noIssues"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$issues}</td>
		<td colspan="2" align="right">{page_links anchor="issues" name="issues" iterator=$issues}</td>
	</tr>
{/if}
</table>
</div>
{include file="common/footer.tpl"}

