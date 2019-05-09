{**
 * plugins/importexport/sample/issues.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of issues to potentially export
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.sample.selectIssue.long"}
{include file="common/header.tpl"}
{/strip}

<br/>

<div id="issues">
<table class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td>{translate key="issue.issue"}</td>
		<td>{translate key="editor.issues.published"}</td>
		<td>{translate key="editor.issues.numArticles"}</td>
		<td width="5%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>

	{iterate from=issues item=issue}
	<tr>
		<td><a href="{url page="issue" op="view" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a></td>
		<td>{$issue->getDatePublished()|date_format:"$dateFormatShort"|default:"&mdash;"}</td>
		<td>{$issue->getNumArticles()|escape}</td>
		<td align="right"><a href="{plugin_url path="exportIssue"|to_array:$issue->getId()}" class="action">{translate key="common.export"}</a></td>
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
		<td colspan="1" align="left">{page_info iterator=$issues}</td>
		<td colspan="3" align="right">{page_links anchor="issues" name="issues" iterator=$issues}</td>
	</tr>
{/if}
</table>
</div>
{include file="common/footer.tpl"}
