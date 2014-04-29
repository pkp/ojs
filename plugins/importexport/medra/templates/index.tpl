{**
 * @file plugins/importexport/medra/templates/index.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * mEDRA plug-in home page.
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.medra.displayName"}
{include file="common/header.tpl"}
{/strip}

<br/>

<h3>{translate key="plugins.importexport.common.settings"}</h3>
{if !empty($configurationErrors)}
	{foreach from=$configurationErrors item=configurationError}
		{if $configurationError == $smarty.const.DOI_EXPORT_CONFIGERROR_DOIPREFIX}
			{translate key="plugins.importexport.common.error.DOIsNotAvailable"}<br /><br />
		{elseif $configurationError == $smarty.const.DOI_EXPORT_CONFIGERROR_SETTINGS}
			{translate key="plugins.importexport.common.error.pluginNotConfigured"}
		{/if}
	{/foreach}
{/if}
{capture assign="settingsUrl"}{plugin_url path="settings"}{/capture}
{translate key="plugins.importexport.medra.settings.description" settingsUrl=$settingsUrl}
<br />
<br />
{translate key="plugins.importexport.medra.intro"}

{if empty($configurationErrors)}
	<h3>{translate key="plugins.importexport.common.export"}</h3>

	<ul class="plain">
		<li>&#187; <a href="{plugin_url path="all"}">{translate key="plugins.importexport.common.export.unregistered"}</a></li>
		<li>&#187; <a href="{plugin_url path="issues"}">{translate key="plugins.importexport.common.export.issues"}</a></li>
		<li>&#187; <a href="{plugin_url path="articles"}">{translate key="plugins.importexport.common.export.articles"}</a></li>
		<li>&#187; <a href="{plugin_url path="galleys"}">{translate key="plugins.importexport.common.export.galleys"}</a></li>
	</ul>
	<p/>
	<p>{translate key="plugins.importexport.medra.workOrProduct"}</p>
{/if}

<br /><br />
{include file="common/footer.tpl"}
