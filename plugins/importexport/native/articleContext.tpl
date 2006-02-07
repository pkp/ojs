{**
 * articleContext.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Prompt for "context" for article import: section and issue
 *
 * $Id$
 *}

{assign var="pageTitle" value="plugins.importexport.native.import.articles"}
{include file="common/header.tpl"}

<p>{translate key="plugins.importexport.native.import.articles.description"}</p>

<form action="{plugin_url path="import"}" method="post">
<input type="hidden" name="temporaryFileId" value="{$temporaryFileId|escape}"/>

{translate key="section.section"}&nbsp;&nbsp;
<select name="sectionId" id="sectionId" size="1" class="selectMenu">{html_options options=$sectionOptions selected=$sectionId}</select>

<br />
&nbsp;

<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">&nbsp;</td>
		<td width="65%">{translate key="issue.issue"}</td>
		<td width="15%">{translate key="editor.issues.published"}</td>
		<td width="15%">{translate key="editor.issues.numArticles"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	
	{iterate from=issues item=issue}
	<tr valign="top">
		<td><input {if !$notFirst}checked {/if}name="issueId" type="radio" value="{$issue->getIssueId()}"/></td>
		{assign var=notFirst value=1}
		<td><a href="{url page="issue" op="issueToc" path=$issue->getIssueId()}" class="action">{$issue->getIssueIdentification()|escape}</a></td>
		<td>{$issue->getDatePublished()|date_format:"$dateFormatShort"}</td>
		<td>{$issue->getNumArticles()}</td>
	</tr>
	<tr>
		<td colspan="4" class="{if $issues->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $issues->wasEmpty()}
	<tr>
		<td colspan="4" class="nodata">{translate key="issue.noIssues"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$issues}</td>
		<td colspan="2" align="right">{page_links name="issues" iterator=$issues temporaryFileId=$temporaryFileId}</td>
	</tr>
{/if}
</table>
<p><input {if $issues->wasEmpty()}disabled="disabled" {/if}type="submit" value="{translate key="common.import"}" class="button defaultButton"/></p>
</form>

{include file="common/footer.tpl"}
