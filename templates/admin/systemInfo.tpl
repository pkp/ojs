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
{assign var="pageId" value="admin.systemInfo"}
{include file="common/header.tpl"}

<div class="blockTitle">{translate key="admin.systemVersion"}</div>
<div class="block">
<div class="formSubSectionTitle">{translate key="admin.currentVersion"}</div>
<div class="formSectionIndent"><span class="highlight">{$currentVersion->getVersionString()}</span> ({$currentVersion->getDateInstalled()|date_format:$datetimeFormatLong})</div>
<br />

<div class="formSubSectionTitle">{translate key="admin.versionHistory"}</div>
<div class="formSectionIndent">
<table class="infoTableAlt">
<tr>
	<td class="infoLabel">{translate key="admin.version"}</td>
	<td class="infoLabel">{translate key="admin.versionMajor"}</td>
	<td class="infoLabel">{translate key="admin.versionMinor"}</td>
	<td class="infoLabel">{translate key="admin.versionRevision"}</td>
	<td class="infoLabel">{translate key="admin.versionBuild"}</td>
	<td class="infoLabel">{translate key="admin.dateInstalled"}</td>
</tr>
{foreach from=$versionHistory item=version}
<tr>
	<td><b>{$version->getVersionString()}</b></td>
	<td>{$version->getMajor()}</td>
	<td>{$version->getMinor()}</td>
	<td>{$version->getRevision()}</td>
	<td>{$version->getBuild()}</td>
	<td>{$version->getDateInstalled()|date_format:$datetimeFormatShort}</td>
</tr>
{/foreach}
</table>
</div>
</div>

<br />

<div class="blockTitle">{translate key="admin.systemConfiguration"} <a href="{$pageUrl}/admin/editSystemConfig" class="tableButton">{translate key="common.edit"}</a></div>
<div class="block">
<div class="formSectionInstructions">{translate key="admin.systemConfigurationDescription"}</div>
<br />

{foreach from=$configData key=sectionName item=sectionData}
<div class="formSubSectionTitle">{$sectionName}</div>

<div class="formSectionIndent">
<table class="infoTableAlt">
{foreach from=$sectionData key=settingName item=settingValue}
<tr>
	<td class="infoLabel">{$settingName}</td>
	<td>{if $settingValue === true}On{elseif $settingValue === false}Off{else}{$settingValue}{/if}</td>
</tr>
{/foreach}
</table>
</div>

{/foreach}
</div>

<br />

<div class="blockTitle">{translate key="admin.serverInformation"}</div>
<div class="block">
<div class="formSectionInstructions">{translate key="admin.serverInformationDescription"}</div>
<br />

<div class="formSectionIndent">
<table class="infoTableAlt">
{foreach from=$serverInfo key=settingName item=settingValue}
<tr>
	<td class="infoLabel">{translate key=$settingName}</td>
	<td>{$settingValue}</td>
</tr>
{/foreach}
</table>
</div>

&#187; <a href="{$pageUrl}/admin/phpInfo" target="_blank">{translate key="admin.phpInfo"}</a>
</div>

{include file="common/footer.tpl"}
