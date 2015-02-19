{**
 * @file plugins/importexport/datacite/templates/suppFiles.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Select supplementary files for export.
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.datacite.export.selectSuppFile"}
{assign var="pageCrumbTitle" value="plugins.importexport.datacite.export.selectSuppFile"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">{literal}
	function toggleChecked() {
		var elements = document.getElementById('suppFilesForm').elements;
		for (var i=0; i < elements.length; i++) {
			if (elements[i].name == 'suppFileId[]') {
				elements[i].checked = !elements[i].checked;
			}
		}
	}
{/literal}</script>

<br/>

<div id="suppFiles">
	<form action="{plugin_url path="process"}" method="post" id="suppFilesForm">
		<input type="hidden" name="target" value="suppFile" />
		<table width="100%" class="listing">
			<tr>
				<td colspan="5" class="headseparator">&nbsp;</td>
			</tr>
			<tr class="heading" valign="bottom">
				<td width="5%">&nbsp;</td>
				<td width="25%">{translate key="issue.issue"}</td>
				<td width="40%">{translate key="article.title"}</td>
				<td width="25%">{translate key="article.authors"}</td>
				<td width="5%" align="right">{translate key="common.action"}</td>
			</tr>
			<tr>
				<td colspan="5" class="headseparator">&nbsp;</td>
			</tr>

			{assign var="noSuppFiles" value=true}
			{iterate from=suppFiles item=suppFileData}
				{assign var=suppFile value=$suppFileData.suppFile}
				{assign var=article value=$suppFileData.article}
				{assign var=issue value=$suppFileData.issue}
				{if $suppFile->getData('datacite::registeredDoi')}
					{capture assign="updateOrRegister"}{translate key="plugins.importexport.common.update"}{/capture}
					{capture assign="updateOrRegisterDescription"}{translate key="plugins.importexport.common.updateDescription"}{/capture}
				{else}
					{capture assign="updateOrRegister"}{translate key="plugins.importexport.common.register"}{/capture}
					{capture assign="updateOrRegisterDescription"}{translate key="plugins.importexport.common.registerDescription"}{/capture}
				{/if}
				<tr valign="top">
					<td><input type="checkbox" name="suppFileId[]" value="{$suppFile->getId()}"/></td>
					<td><a href="{url page="issue" op="view" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_tags}</a></td>
					<td><a href="{url page="rt" op="suppFileMetadata" path=$article->getId()|to_array:0:$suppFile->getId()}" class="action">{$article->getLocalizedTitle()|cat:' ('|cat:$suppFile->getSuppFileTitle()|cat:')'|strip_unsafe_html}</a></td>
					<td>{$suppFile->getSuppFileCreator()|default:$article->getAuthorString()|escape}</td>
					<td align="right"><nobr>
						{if $hasCredentials}
							<a href="{plugin_url path="process" suppFileId=$suppFile->getId() params=$testMode target="suppFile" register=true}" title="{$updateOrRegisterDescription}" class="action">{$updateOrRegister}</a>
						{/if}
						<a href="{plugin_url path="process" suppFileId=$suppFile->getId() params=$testMode target="suppFile" export=true}" title="{translate key="plugins.importexport.common.exportDescription"}" class="action">{translate key="common.export"}</a>
					</nobr></td>
				</tr>
				<tr>
					<td colspan="5" class="{if $suppFiles->eof()}end{/if}separator">&nbsp;</td>
				</tr>
			{/iterate}
			{if $suppFiles->wasEmpty()}
				<tr>
					<td colspan="5" class="nodata">{translate key="plugins.importexport.datacite.export.noSuppFiles"}</td>
				</tr>
				<tr>
					<td colspan="5" class="endseparator">&nbsp;</td>
				</tr>
			{else}
				<tr>
					<td colspan="2" align="left">{page_info iterator=$suppFiles}</td>
					<td colspan="3" align="right">{page_links anchor="suppFiles" name="suppFiles" iterator=$suppFiles}</td>
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
