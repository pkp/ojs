{**
 * systemInfo.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display system information.
 *
 * $Id$
 *}

{assign var="pageTitle" value="admin.systemInformation"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$pageUrl}/admin/settings">{translate key="admin.siteSettings"}</a></li>
	<li><a href="{$pageUrl}/admin/journals">{translate key="admin.hostedJournals"}</a></li>
	<li><a href="{$pageUrl}/admin/languages">{translate key="common.languages"}</a></li>
</ul>

<ul class="menu">
	<li class="current"><a href="{$pageUrl}/admin/systemInfo">{translate key="admin.systemInformation"}</a></li>
	<li><a href="{$pageUrl}/admin/expireSessions" onclick="return confirm('{translate|escape:"javascript" key="admin.confirmExpireSessions"}')">{translate key="admin.expireSessions"}</a></li>
	<li><a href="{$pageUrl}/admin/clearTemplateCache" onclick="return confirm('{translate|escape:"javascript" key="admin.confirmClearTemplateCache"}')">{translate key="admin.clearTemplateCache"}</a></li>
</ul>

<br/>

<h3>{translate key="admin.systemVersion"}</h3>
<h4>{translate key="admin.currentVersion"}</h4>
<p>{$currentVersion->getVersionString()} ({$currentVersion->getDateInstalled()|date_format:$datetimeFormatLong})</p>

<h4>{translate key="admin.versionHistory"}</h4>
<table class="listing" width="100%">
<tr valign="top">
	<td width="30%" class="heading">{translate key="admin.version"}</td>
	<td width="10%" class="heading">{translate key="admin.versionMajor"}</td>
	<td width="10%" class="heading">{translate key="admin.versionMinor"}</td>
	<td width="10%" class="heading">{translate key="admin.versionRevision"}</td>
	<td width="20%" class="heading">{translate key="admin.versionBuild"}</td>
	<td width="20%" class="heading">{translate key="admin.dateInstalled"}</td>
</tr>
{foreach from=$versionHistory item=version}
<tr valign="top">
	<td>{$version->getVersionString()}</td>
	<td>{$version->getMajor()}</td>
	<td>{$version->getMinor()}</td>
	<td>{$version->getRevision()}</td>
	<td>{$version->getBuild()}</td>
	<td>{$version->getDateInstalled()|date_format:$dateFormatShort}</td>
</tr>
{/foreach}
</table>

<br />

<h3>{translate key="admin.systemConfiguration"}</h3>
<a class="action" href="{$pageUrl}/admin/editSystemConfig">{translate key="common.edit"}</a>
<p>{translate key="admin.systemConfigurationDescription"}</p>
<br />

{foreach from=$configData key=sectionName item=sectionData}
<h4>{$sectionName}</h4>

<table class="data" width="100%">
{foreach from=$sectionData key=settingName item=settingValue}
<tr valign="top">
	<td width="20%" class="label">{$settingName}</td>
	<td width="100%">{if $settingValue === true}{translate key="common.on"}{elseif $settingValue === false}{translate key="common.off"}{else}{$settingValue}{/if}</td>
</tr>
{/foreach}
</table>

{/foreach}

<br />

<h4>{translate key="admin.serverInformation"}</h4>
<p>{translate key="admin.serverInformationDescription"}</p>

<table class="data" width="100%">
{foreach from=$serverInfo key=settingName item=settingValue}
<tr valign="top">
	<td width="20%" class="label">{translate key=$settingName}</td>
	<td width="80%" class="value">{$settingValue}</td>
</tr>
{/foreach}
</table>

&#187; <a href="{$pageUrl}/admin/phpInfo" target="_blank">{translate key="admin.phpInfo"}</a>

{include file="common/footer.tpl"}
