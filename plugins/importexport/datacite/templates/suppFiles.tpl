{**
 * @file plugins/importexport/datacite/templates/suppFiles.tpl
 *
 * Copyright (c) 2011 John Willinsky
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
		var elements = document.suppFiles.elements;
		for (var i=0; i < elements.length; i++) {
			if (elements[i].name == 'suppFileId[]') {
				elements[i].checked = !elements[i].checked;
			}
		}
	}
{/literal}</script>

<br/>

<div id="suppFiles">
	<form action="{plugin_url path="exportSuppFiles"}" method="post" name="suppFiles">
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

			{assign var="noSuppFiles" value="true"}
			{iterate from=suppFiles item=suppFileData}
				{assign var=suppFile value=$suppFileData.suppFile}
				{if $suppFile->getPubId('doi')}
					{assign var="noSuppFiles" value="false"}
					{assign var=article value=$suppFileData.article}
					{assign var=issue value=$suppFileData.issue}
					<tr valign="top">
						<td><input type="checkbox" name="suppFileId[]" value="{$suppFile->getId()}"/></td>
						<td><a href="{url page="issue" op="view" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_tags}</a></td>
						<td><a href="{url page="rt" op="suppFileMetadata" path=$article->getId()|to_array:0:$suppFile->getId()}" class="action">{$article->getLocalizedTitle()|cat:' ('|cat:$suppFile->getSuppFileTitle()|cat:')'|strip_unsafe_html}</a></td>
						<td>{$suppFile->getSuppFileCreator()|default:$article->getAuthorString()|escape}</td>
						<td align="right"><a href="{plugin_url path="exportSuppFile"|to_array:$suppFile->getId()}" class="action">{translate key="common.export"}</a></td>
					</tr>
					<tr>
						<td colspan="5" class="{if $suppFiles->eof()}end{/if}separator">&nbsp;</td>
					</tr>
				{/if}
			{/iterate}
			{if $noSuppFiles == "true"}
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
			<input type="submit" name="export" value="{translate key="common.export"}" class="button defaultButton"/>
			&nbsp;
			<input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" />
		</p>
	</form>
</div>

{include file="common/footer.tpl"}
