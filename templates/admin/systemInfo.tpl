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

<h3>{translate key="admin.systemVersion"}</h3>
<h4>{translate key="admin.currentVersion"}</h4>
<p>{$currentVersion->getVersionString()} ({$currentVersion->getDateInstalled()|date_format:$datetimeFormatLong})</p>

<h4>{translate key="admin.versionHistory"}</h4>
<table class="listing" width="100%">
	<tr>
		<td colspan="6" class="headseparator"></td>
	</tr>
	<tr valign="top" class="heading">
		<td width="30%">{translate key="admin.version"}</td>
		<td width="10%">{translate key="admin.versionMajor"}</td>
		<td width="10%">{translate key="admin.versionMinor"}</td>
		<td width="10%">{translate key="admin.versionRevision"}</td>
		<td width="20%">{translate key="admin.versionBuild"}</td>
		<td width="20%" align="right">{translate key="admin.dateInstalled"}</td>
	</tr>
	<tr>
		<td colspan="6" class="headseparator"></td>
	</tr>
	{foreach name="versions" from=$versionHistory item=version}
	<tr valign="top">
		<td>{$version->getVersionString()}</td>
		<td>{$version->getMajor()}</td>
		<td>{$version->getMinor()}</td>
		<td>{$version->getRevision()}</td>
		<td>{$version->getBuild()}</td>
		<td>{$version->getDateInstalled()|date_format:$dateFormatShort}</td>
	</tr>
	<tr>
		<td colspan="6" class="{if $smarty.foreach.versions.last}end{/if}separator"></td>
	</tr>
{/foreach}
</table>

<br />

<h3>{translate key="admin.systemConfiguration"}</h3>
<a class="action" href="{$pageUrl}/admin/editSystemConfig">{translate key="common.edit"}</a>
<p>{translate key="admin.systemConfigurationDescription"}</p>

{foreach from=$configData key=sectionName item=sectionData}
<h4>{$sectionName}</h4>

<table class="data" width="100%">
{foreach from=$sectionData key=settingName item=settingValue}
<tr valign="top">
	<td width="30%" class="label">{$settingName}</td>
	<td width="70%">{if $settingValue === true}{translate key="common.on"}{elseif $settingValue === false}{translate key="common.off"}{else}{$settingValue}{/if}</td>
</tr>
{/foreach}
</table>

{/foreach}

<div class="separator"></div>

<h3>{translate key="admin.serverInformation"}</h3>
<p>{translate key="admin.serverInformationDescription"}</p>

<table class="data" width="100%">
{foreach from=$serverInfo key=settingName item=settingValue}
<tr valign="top">
	<td width="30%" class="label">{translate key=$settingName}</td>
	<td width="70%" class="value">{$settingValue}</td>
</tr>
{/foreach}
</table>

<a href="{$pageUrl}/admin/phpInfo" target="_blank">{translate key="admin.phpInfo"}</a>

{include file="common/footer.tpl"}
