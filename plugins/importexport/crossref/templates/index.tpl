{**
 * @file plugins/importexport/crossref/templates/index.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * DataCite plug-in home page.
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.crossref.displayName"}
{include file="common/header.tpl"}
{/strip}

{translate key="plugins.importexport.crossref.registrationIntro"}
{capture assign="settingsUrl"}{plugin_url path="settings"}{/capture}

<br />

<h3>{translate key="plugins.importexport.common.export"}</h3>
{if !empty($configurationErrors) || !$currentJournal->getSetting('publisherInstitution')|escape}
	<p>{translate key="plugins.importexport.common.export.unAvailable"}</p>
{else}
	<ul class="plain">
		<li>&#187; <a href="{plugin_url path="all"}">{translate key="plugins.importexport.crossref.export.unregistered"}</a></li>
		<li>&#187; <a href="{plugin_url path="issues"}">{translate key="plugins.importexport.common.export.issues"}</a></li>
		<li>&#187; <a href="{plugin_url path="articles"}">{translate key="plugins.importexport.common.export.articles"}</a></li>
	</ul>
{/if}

<h3>{translate key="plugins.importexport.common.settings"}</h3>
<br />
{translate key="plugins.importexport.crossref.settings.description" settingsUrl=$settingsUrl}
<br />

{include file="common/footer.tpl"}
