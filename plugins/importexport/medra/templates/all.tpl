{**
 * @file plugins/importexport/medra/templates/all.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Select all unregistered objects for export.
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.common.export.selectUnregistered"}
{assign var="pageCrumbTitle" value="plugins.importexport.common.export.selectUnregistered"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">{literal}
	function toggleChecked() {
		var elements = document.getElementById('exportAllForm').elements;
		for (var i=0; i < elements.length; i++) {
			if (elements[i].name == 'issueId[]' || elements[i].name == 'articleId[]' || elements[i].name == 'galleyId[]') {
				elements[i].checked = !elements[i].checked;
			}
		}
	}
{/literal}</script>

<br/>

<div id="allUnregistered">
	<p>{translate key="plugins.importexport.medra.workOrProduct"}</p>
	<form action="{plugin_url path="process"}" method="post" id="exportAllForm">
		<input type="hidden" name="target" value="all" />
		<table width="100%" class="listing">
			<tr>
				<td colspan="5" class="headseparator">&nbsp;</td>
			</tr>
			<tr class="heading" valign="bottom">
				<td width="5%">&nbsp;</td>
				<td width="5%">&nbsp;</td>
				<td width="25%">{translate key="issue.issue"}</td>
				<td width="40%">{translate key="article.title"}</td>
				<td width="25%">{translate key="article.authors"}</td>
			</tr>
			<tr>
				<td colspan="5" class="headseparator">&nbsp;</td>
			</tr>

			{assign var=noObjects value=true}
			{foreach from=$issues item=issue}
				{if $issue->getPubId('doi')}
					{assign var=noObjects value=false}
					<tr valign="top">
						<td><input type="checkbox" name="issueId[]" value="{$issue->getId()}" checked="checked" /></td>
						<td>{fieldLabel name="issueId[]" key="issue.issue"}</td>
						<td><a href="{url page="issue" op="view" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a></td>
						<td>&mdash;</td>
						<td>&mdash;</td>
					</tr>
					<tr>
						<td colspan="5" class="separator">&nbsp;</td>
					</tr>
				{/if}
			{/foreach}
			{foreach from=$articles item=articleData}
				{assign var=article value=$articleData.article}
				{if $article->getPubId('doi')}
					{assign var=noObjects value=false}
					{assign var=issue value=$articleData.issue}
					<tr valign="top">
						<td><input type="checkbox" name="articleId[]" value="{$article->getId()}" checked="checked" /></td>
						<td>{fieldLabel name="articleId[]" key="article.article"}</td>
						<td><a href="{url page="issue" op="view" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_tags}</a></td>
						<td><a href="{url page="article" op="view" path=$article->getId()}" class="action">{$article->getLocalizedTitle()|strip_unsafe_html}</a></td>
						<td>{$article->getAuthorString()|escape}</td>
					</tr>
					<tr>
						<td colspan="5" class="separator">&nbsp;</td>
					</tr>
				{/if}
			{/foreach}
			{foreach from=$galleys item=galleyData name=galleys}
				{assign var=galley value=$galleyData.galley}
				{if $galley->getPubId('doi')}
					{assign var=noObjects value=false}
					{assign var=language value=$galleyData.language}
					{assign var=article value=$galleyData.article}
					{assign var=issue value=$galleyData.issue}
					<tr valign="top">
						<td><input type="checkbox" name="galleyId[]" value="{$galley->getId()}" checked="checked" /></td>
						<td>{fieldLabel name="galleyId[]" key="submission.galley"}</td>
						<td><a href="{url page="issue" op="view" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_tags}</a></td>
						<td><a href="{url page="article" op="view" path=$article->getId()|to_array:$galley->getId()}" class="action">{$article->getLocalizedTitle()|cat:' ('|cat:$galley->getLabel()|cat:', '|cat:$language->getName()|cat:')'|strip_unsafe_html}</a></td>
						<td>{$article->getAuthorString()|escape}</td>
					</tr>
					<tr>
						<td colspan="5" class="{if $smarty.foreach.galleys.last}end{/if}separator">&nbsp;</td>
					</tr>
				{/if}
			{/foreach}
			{if $noObjects}
				<tr>
					<td colspan="5" class="nodata">{translate key="plugins.importexport.common.export.noUnregistered"}</td>
				</tr>
				<tr>
					<td colspan="5" class="endseparator">&nbsp;</td>
				</tr>
			{/if}
		</table>
		<p>
			{if !empty($testMode)}<input type="hidden" name="testMode" value="1" />{/if}
			{if $hasCredentials}
				<input type="submit" name="register" value="{translate key="plugins.importexport.common.register"}" title="{translate key="plugins.importexport.common.registerDescription.multi"}" class="button defaultButton"/>
				&nbsp;
			{/if}
			<input type="submit" name="export" value="{translate key="common.export"}" title="{translate key="plugins.importexport.common.exportDescription"}" class="button{if !$hasCredentials}  defaultButton{/if}"/>
			&nbsp;
			<input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" />
		</p>
		<p>
			{if $hasCredentials}
				{translate key="plugins.importexport.common.register.warning"}
			{else}
				{capture assign="settingsUrl"}{plugin_url path="settings"}{/capture}
				{translate key="plugins.importexport.common.register.noCredentials" settingsUrl=$settingsUrl}
			{/if}
		</p>
	</form>
</div>

{include file="common/footer.tpl"}
