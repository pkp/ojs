{**
 * @file plugins/importexport/datacite/templates/issues.tpl
 *
 * Copyright (c) 2011 John Willinsky
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
		var elements = document.issues.elements;
		for (var i=0; i < elements.length; i++) {
			if (elements[i].name == 'issueId[]') {
				elements[i].checked = !elements[i].checked;
			}
		}
	}
{/literal}</script>

<br/>

<div id="issues">
	<form action="{plugin_url path="exportIssues"}" method="post" name="issues">
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

			{assign var="noIssues" value="true"}
			{iterate from=issues item=issue}
				{if $issue->getPubId('doi')}
					{assign var="noIssues" value="false"}
					{capture assign="updateOrRegister"}{strip}
						{if $issue->getData('datacite::registeredDoi')}
							{translate key="plugins.importexport.common.update"}
						{else}
							{translate key="plugins.importexport.common.register"}
						{/if}
					{/strip}{/capture}
					<tr valign="top">
						<td><input type="checkbox" name="issueId[]" value="{$issue->getId()}"/></td>
						<td><a href="{url page="issue" op="view" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a></td>
						<td>{$issue->getDatePublished()|date_format:"$dateFormatShort"|default:"&mdash;"}</td>
						<td>{$issue->getNumArticles()|escape}</td>
						<td align="right"><nobr>
							<a href="{plugin_url path="registerIssue"|to_array:$issue->getId()}{if $testMode}?testMode=1{/if}" class="action">{$updateOrRegister}</a>
							<a href="{plugin_url path="exportIssue"|to_array:$issue->getId()}{if $testMode}?testMode=1{/if}" class="action">{translate key="common.export"}</a>
						</nobr></td>
					</tr>
					<tr>
						<td colspan="5" class="{if $issues->eof()}end{/if}separator">&nbsp;</td>
					</tr>
				{/if}
			{/iterate}
			{if $noIssues == "true"}
				<tr>
					<td colspan="5" class="nodata">{translate key="plugins.importexport.common.export.noIssues"}</td>
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
		<p>
			{if $testMode}<input type="hidden" name="testMode" value="1" />{/if}
			<input type="submit" name="register" value="{translate key="plugins.importexport.common.register"}" class="button defaultButton"/>
			&nbsp;
			<input type="submit" name="export" value="{translate key="common.export"}" class="button"/>
			&nbsp;
			<input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" />
		</p>
		<p>
			{translate key="plugins.importexport.common.register.warning"}
		</p>
	</form>
</div>

{include file="common/footer.tpl"}
