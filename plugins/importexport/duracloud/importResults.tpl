{**
 * plugins/importexport/duracloud/importResults.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Results of export of import from DuraCloud
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.duracloud.displayName"}
{include file="common/header.tpl"}
{/strip}

<br/>

<h3>{translate key="plugins.importexport.duracloud.import.results"}</h3>

<p>{translate key="plugins.importexport.duracloud.import.results.description"}</p>
<ul>
	{foreach from=$results key=contentId item=issue}
		<li>
			{if $issue !== false}{* Successful import *}
				{translate key="plugins.importexport.duracloud.import.results.success" contentId=$contentId|escape issueIdentification=$issue->getIssueIdentification()|escape}
			{else}{* Failure *}
				{translate key="plugins.importexport.duracloud.import.results.failure" contentId=$contentId|escape}
			{/if}
		</li>
	{/foreach}
</ul>

<p><a href="{plugin_url}">{translate key="common.continue"}</a></p>

{include file="common/footer.tpl"}
