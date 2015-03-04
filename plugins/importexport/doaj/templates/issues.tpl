{**
 * @file plugins/importexport/doaj/templates/issues.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Select issues for export.
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.common.export.selectIssue"}
{assign var="pageCrumbTitle" value="plugins.importexport.common.export.selectIssue"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">{literal}
	function toggleChecked() {
		var elements = document.getElementById('issuesForm').elements;
		for (var i=0; i < elements.length; i++) {
			if (elements[i].name == 'issueId[]') {
				elements[i].checked = !elements[i].checked;
			}
		}
	}
{/literal}</script>

<br/>

<div id="issues">
	<form action="{plugin_url path="process"}" method="post" id="issuesForm">
		<input type="hidden" name="target" value="issue" />
		<table width="100%" class="listing">
			<tr>
				<td colspan="5" class="headseparator">&nbsp;</td>
			</tr>
			<tr class="heading" valign="bottom">
				<td width="5%">&nbsp;</td>
				<td width="55%">{translate key="issue.issue"}</td>
				<td width="15%">{translate key="editor.issues.published"}</td>
				<td width="15%">{translate key="editor.issues.numArticles"}</td>
				<td width="10%" align="right">{translate key="common.action"}</td>
			</tr>
			<tr>
				<td colspan="5" class="headseparator">&nbsp;</td>
			</tr>

			{iterate from=issues item=issue}
				{assign var=doajRegistered value=$issue->getData('doaj::registered')}
				<tr valign="top">
					<td><input type="checkbox" name="issueId[]" value="{$issue->getId()}"/></td>
					<td><a href="{url page="issue" op="view" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a></td>
					<td>{$issue->getDatePublished()|date_format:"$dateFormatShort"|default:"&mdash;"}</td>
					<td>{$issue->getNumArticles()|escape}</td>
					<td align="right"><nobr>
						{if !$doajRegistered}
							<a href="{plugin_url path="process" issueId=$issue->getId() target="issue" markRegistered=true}" class="action">{translate key="plugins.importexport.common.markRegistered"}</a>
						{/if}
						<a href="{plugin_url path="process" issueId=$issue->getId() target="issue" export=true}" class="action">{translate key="common.export"}</a>
					</nobr></td>
				</tr>
				<tr>
					<td colspan="5" class="{if $issues->eof()}end{/if}separator">&nbsp;</td>
				</tr>
			{/iterate}
			{if $issues->wasEmpty()}
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
		<p>
			<input type="submit" name="export" value="{translate key="common.export"}" title="{translate key="plugins.importexport.common.exportDescription"}" class="button{if !$hasCredentials}  defaultButton{/if}"/>
			<input type="submit" name="markRegistered" value="{translate key="plugins.importexport.common.markRegistered"}" title="{translate key="plugins.importexport.common.markRegisteredDescription"}" class="button"/>
			&nbsp;
			<input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" />
		</p>
	</form>
</div>

{include file="common/footer.tpl"}
