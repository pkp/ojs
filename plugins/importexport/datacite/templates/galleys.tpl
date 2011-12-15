{**
 * @file plugins/importexport/datacite/templates/galleys.tpl
 *
 * Copyright (c) 2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Select galleys for export.
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.common.export.selectGalley"}
{assign var="pageCrumbTitle" value="plugins.importexport.common.export.selectGalley"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">{literal}
	function toggleChecked() {
		var elements = document.galleys.elements;
		for (var i=0; i < elements.length; i++) {
			if (elements[i].name == 'galleyId[]') {
				elements[i].checked = !elements[i].checked;
			}
		}
	}
{/literal}</script>

<br/>

<div id="galleys">
	<form action="{plugin_url path="exportGalleys"}" method="post" name="galleys">
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

			{assign var="noGalleys" value="true"}
			{iterate from=galleys item=galleyData}
				{assign var=galley value=$galleyData.galley}
				{if $galley->getPubId('doi')}
					{assign var="noGalleys" value="false"}
					{assign var=language value=$galleyData.language}
					{assign var=article value=$galleyData.article}
					{assign var=issue value=$galleyData.issue}
					{capture assign="updateOrRegister"}{strip}
						{if $galley->getData('datacite::registeredDoi')}
							{translate key="plugins.importexport.common.update"}
						{else}
							{translate key="plugins.importexport.common.register"}
						{/if}
					{/strip}{/capture}
					<tr valign="top">
						<td><input type="checkbox" name="galleyId[]" value="{$galley->getId()}"/></td>
						<td><a href="{url page="issue" op="view" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_tags}</a></td>
						<td><a href="{url page="article" op="view" path=$article->getId()|to_array:$galley->getId()}" class="action">{$article->getLocalizedTitle()|cat:' ('|cat:$galley->getLabel()|cat:', '|cat:$language->getName()|cat:')'|strip_unsafe_html}</a></td>
						<td>{$article->getAuthorString()|escape}</td>
						<td align="right"><nobr>
							<a href="{plugin_url path="registerGalley"|to_array:$galley->getId()}{if $testMode}?testMode=1{/if}" class="action">{$updateOrRegister}</a>
							<a href="{plugin_url path="exportGalley"|to_array:$galley->getId()}{if $testMode}?testMode=1{/if}" class="action">{translate key="common.export"}</a>
						</nobr></td>
					</tr>
					<tr>
						<td colspan="5" class="{if $galleys->eof()}end{/if}separator">&nbsp;</td>
					</tr>
				{/if}
			{/iterate}
			{if $noGalleys == "true"}
				<tr>
					<td colspan="5" class="nodata">{translate key="plugins.importexport.common.export.noGalleys"}</td>
				</tr>
				<tr>
					<td colspan="5" class="endseparator">&nbsp;</td>
				</tr>
			{else}
				<tr>
					<td colspan="2" align="left">{page_info iterator=$galleys}</td>
					<td colspan="3" align="right">{page_links anchor="galleys" name="galleys" iterator=$galleys}</td>
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
