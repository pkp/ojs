{**
 * plugins/importexport/duracloud/exportResults.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Results of export of issues to DuraCloud
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.duracloud.displayName"}
{include file="common/header.tpl"}
{/strip}

<br/>

<h3>{translate key="plugins.importexport.duracloud.export.results"}</h3>

<p>{translate key="plugins.importexport.duracloud.export.results.description"}</p>
<ul>
	{foreach from=$results key=issueId item=result}
		<li>
			{assign var=issue value=$issues.$issueId}
			{if $result !== false}{* Successful export *}
				{translate key="plugins.importexport.duracloud.export.results.success" issueIdentification=$issue->getIssueIdentification()|escape targetLocation=$result targetLocationEscaped=$result|escape}
			{else}{* Failure *}
				{translate key="plugins.importexport.duracloud.export.results.failure" issueIdentification=$issue->getIssueIdentification()|escape}
			{/if}
		</li>
	{/foreach}
</ul>

<p><a href="{plugin_url}">{translate key="common.continue"}</a></p>

{include file="common/footer.tpl"}
