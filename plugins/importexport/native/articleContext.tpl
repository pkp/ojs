{**
 * plugins/importexport/native/articleContext.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Prompt for "context" for article import: section and issue
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.native.import.articles"}
{include file="common/header.tpl"}
{/strip}

<p>{translate key="plugins.importexport.native.import.articles.description"}</p>
<div id="articleContext">
<form action="{plugin_url path="import"}" method="post">
<input type="hidden" name="temporaryFileId" value="{$temporaryFileId|escape}"/>

{translate key="section.section"}&nbsp;&nbsp;
<select name="sectionId" id="sectionId" size="1" class="selectMenu">{html_options options=$sectionOptions selected=$sectionId}</select>

<br />
&nbsp;

<div id="issues">
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
		<td><input {if !$notFirst}checked="checked" {/if}name="issueId" type="radio" value="{$issue->getId()}"/></td>
		{assign var=notFirst value=1}
		<td><a href="{url page="issue" op="view" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a></td>
		<td>{$issue->getDatePublished()|date_format:"$dateFormatShort"|default:"&mdash;"}</td>
		<td>{$issue->getNumArticles()|escape}</td>
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
		<td colspan="2" align="right">{page_links anchor="issues" name="issues" iterator=$issues temporaryFileId=$temporaryFileId}</td>
	</tr>
{/if}
</table>
<p><input {if $issues->wasEmpty()}disabled="disabled" {/if}type="submit" value="{translate key="common.import"}" class="button defaultButton"/></p>
</form>
</div>
</div>
{include file="common/footer.tpl"}
