{**
 * @file plugins/importexport/crossref/templates/articles.tpl
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Articles listing page.
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.crossref.manageArticleDOIs"}
{assign var="pageCrumbTitle" value="plugins.importexport.crossref.manageArticleDOIs"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">{literal}
	function toggleChecked() {
		var elements = document.getElementById('articlesForm').elements;
		for (var i=0; i < elements.length; i++) {
			if (elements[i].name == 'articleId[]') {
				elements[i].checked = !elements[i].checked;
			}
		}
	}
{/literal}</script>

<br />

<div id="articles">
	<p><a href="{plugin_url path="issues"}">{translate key="plugins.importexport.crossref.manageIssues"}</a></p>
	<br />
	<ul class="menu">
		<li><a href="{plugin_url path="articles"}"{if !$filter} class="current"{/if}>{translate key="plugins.importexport.crossref.status.all"}</a></li>
		<li><a href="{plugin_url path="articles" filter=$smarty.const.CROSSREF_STATUS_NOT_DEPOSITED}"{if $filter == $smarty.const.CROSSREF_STATUS_NOT_DEPOSITED} class="current"{/if}>{translate key="plugins.importexport.crossref.status.non"}</a></li>
		<li><a href="{plugin_url path="articles" filter=$smarty.const.CROSSREF_STATUS_FAILED}"{if $filter == $smarty.const.CROSSREF_STATUS_FAILED} class="current"{/if}>{translate key="plugins.importexport.crossref.status.failed"}</a></li>
		<li><a href="{plugin_url path="articles" filter=$smarty.const.CROSSREF_STATUS_SUBMITTED}"{if $filter == $smarty.const.CROSSREF_STATUS_SUBMITTED} class="current"{/if}>{translate key="plugins.importexport.crossref.status.submitted"}</a></li>
		<li><a href="{plugin_url path="articles" filter=$smarty.const.CROSSREF_STATUS_COMPLETED}"{if $filter == $smarty.const.CROSSREF_STATUS_COMPLETED} class="current"{/if}>{translate key="plugins.importexport.crossref.status.completed"}</a></li>
		<li><a href="{plugin_url path="articles" filter=$smarty.const.CROSSREF_STATUS_REGISTERED}"{if $filter == $smarty.const.CROSSREF_STATUS_REGISTERED} class="current"{/if}>{translate key="plugins.importexport.crossref.status.registered"}</a></li>
	</ul>
	<br />
	<form action="{plugin_url path="process"}" method="post" id="articlesForm">
		<input type="hidden" name="target" value="article" />
		<table width="100%" class="listing">
			<tr>
				<td colspan="7" class="headseparator">&nbsp;</td>
			</tr>
			<tr class="heading" valign="bottom">
				<td width="5%">&nbsp;</td>
				<td width="5%">{translate key="common.id"}</td>
				<td width="15%">{translate key="issue.issue"}</td>
				<td width="30%">{translate key="article.title"}</td>
				<td width="25%">{translate key="article.authors"}</td>
				<td width="10%">DOI</td>
				<td width="10%">{translate key="common.status"}</td>
			</tr>
			<tr>
				<td colspan="7" class="headseparator">&nbsp;</td>
			</tr>

			{iterate from=articles item=articleData}
				{assign var=article value=$articleData.article}
				{assign var=status value=$article->getData($depositStatusSettingName)|default:$smarty.const.CROSSREF_STATUS_NOT_DEPOSITED}
				{if $article->getPubId('doi')}
					{if ($filter && $filter == $status) || !$filter}
						{assign var=issue value=$articleData.issue}
						<tr valign="top">
							<td><input type="checkbox" name="articleId[]" value="{$article->getId()}"{if $status == $smarty.const.CROSSREF_STATUS_NOT_DEPOSITED} checked="checked"{/if} /></td>
							<td>{$article->getId()|escape}</td>
							<td><a href="{url page="issue" op="view" path=$issue->getId()}" class="action">{$issue->getIssueIdentification()|strip_tags}</a></td>
							<td><a href="{url page="article" op="view" path=$article->getId()}" class="action">{$article->getLocalizedTitle()|strip_unsafe_html|truncate:60:"..."}</a></td>
							<td>{$article->getAuthorString()|truncate:40:"..."|escape}</td>
							<td>
								{if $isEditor}
									<a href="{url page="editor" op="viewMetadata" path=$article->getId()}" class="action">{$article->getPubId('doi')|escape}</a></td>
								{else}
									{$article->getPubId('doi')|escape}
								{/if}
							<td>
								{if $status == $smarty.const.CROSSREF_STATUS_NOT_DEPOSITED}
									{translate key="plugins.importexport.crossref.status.non"}
								{else}
									<input type="hidden" name="filter" value="{$filter|escape}" />
									<a href="https://api.crossref.org{$article->getData($depositStatusUrlSettingName)|escape}" target="_blank">{$statusMapping[$status]|escape}</a></td>
								{/if}
						</tr>
					{/if}
				{/if}
				<tr>
					<td colspan="7" class="{if $articles->eof()}end{/if}separator">&nbsp;</td>
				</tr>
			{/iterate}
			{if $articles->wasEmpty()}
				<tr>
					<td colspan="7" class="nodata">
						{if !$filter}
							{translate key="plugins.importexport.common.export.noArticles"}
						{else}
							{translate key="plugins.importexport.crossref.articles.$filter"}
						{/if}
					</td>
				</tr>
				<tr>
					<td colspan="7" class="endseparator">&nbsp;</td>
				</tr>
			{else}
				<tr>
					<td colspan="3" align="left">{page_info iterator=$articles}</td>
					<td colspan="4" align="right">{page_links anchor="articles" name="articles" iterator=$articles filter=$filter}</td>
				</tr>
			{/if}
		</table>
		<p>
			{if $hasCredentials}
				<input type="submit" name="register" value="{translate key="plugins.importexport.common.register"}" title="{translate key="plugins.importexport.common.registerDescription.multi"}" class="button defaultButton"/>
				&nbsp;
				<input type="submit" name="checkStatus" value="{translate key="plugins.importexport.crossref.checkStatus"}" title="{translate key="plugins.importexport.crossref.checkStatusDescription"}" class="button"/>
				&nbsp;
			{/if}
			<input type="submit" name="export" value="{translate key="plugins.importexport.crossref.downloadXML"}" title="{translate key="plugins.importexport.common.exportDescription"}" class="button{if !$hasCredentials}  defaultButton{/if}"/>
			&nbsp;
			<input type="submit" name="markRegistered" value="{translate key="plugins.importexport.common.markRegistered"}" title="{translate key="plugins.importexport.common.markRegisteredDescription"}" class="button"/>
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
	{translate key="plugins.importexport.crossref.statusLegend"}
</div>

{include file="common/footer.tpl"}
