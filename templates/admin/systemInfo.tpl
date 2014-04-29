{**
 * templates/admin/systemInfo.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display system information.
 *
 *}
{strip}
{assign var="pageTitle" value="admin.systemInformation"}
{include file="common/header.tpl"}
{/strip}
<div id="systemVersion">
<h3>{translate key="admin.systemVersion"}</h3>
<div id="currentVersion">
<h4>{translate key="admin.currentVersion"}</h4>
<p>{$currentVersion->getVersionString()} ({$currentVersion->getDateInstalled()|date_format:$datetimeFormatLong})</p>

{if $latestVersionInfo}
		<p>{translate key="admin.version.latest"}: {$latestVersionInfo.release|escape} ({$latestVersionInfo.date|date_format:$dateFormatLong})</p>
	{if $currentVersion->compare($latestVersionInfo.version) < 0}
		<p><strong>{translate key="admin.version.updateAvailable"}</strong>: <a href="{$latestVersionInfo.package|escape}">{translate key="admin.version.downloadPackage"}</a> | {if $latestVersionInfo.patch}<a href="{$latestVersionInfo.patch|escape}">{translate key="admin.version.downloadPatch"}</a>{else}{translate key="admin.version.downloadPatch"}{/if} | <a href="{$latestVersionInfo.info|escape}">{translate key="admin.version.moreInfo"}</a></p>
	{else}
		<p><strong>{translate key="admin.version.upToDate"}</strong></p>
	{/if}
{else}
<p><a href="{url versionCheck=1}">{translate key="admin.version.checkForUpdates"}</a></p>
{/if}
</div>
<div id="versionHistory">
<h4>{translate key="admin.versionHistory"}</h4>
<table class="listing" width="100%">
	<tr>
		<td colspan="6" class="headseparator">&nbsp;</td>
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
		<td colspan="6" class="headseparator">&nbsp;</td>
	</tr>
	{foreach name="versions" from=$versionHistory item=version}
	<tr valign="top">
		<td>{$version->getVersionString()|escape}</td>
		<td>{$version->getMajor()|escape}</td>
		<td>{$version->getMinor()|escape}</td>
		<td>{$version->getRevision()|escape}</td>
		<td>{$version->getBuild()|escape}</td>
		<td align="right">{$version->getDateInstalled()|date_format:$dateFormatShort}</td>
	</tr>
	<tr>
		<td colspan="6" class="{if $smarty.foreach.versions.last}end{/if}separator">&nbsp;</td>
	</tr>
{/foreach}
</table>
</div>
</div>

<div id="systemConfiguration">
<h3>{translate key="admin.systemConfiguration"}</h3>
<p>{translate key="admin.systemConfigurationDescription"}</p>

{foreach from=$configData key=sectionName item=sectionData}
<h4>{$sectionName|escape}</h4>

{if !empty($sectionData)}{* Empty tables cause validation problems *}
<table class="data" width="100%">
{foreach from=$sectionData key=settingName item=settingValue}
<tr valign="top">
	<td width="30%" class="label">{$settingName|escape}</td>
	<td width="70%">{if $settingValue === true}{translate key="common.on"}{elseif $settingValue === false}{translate key="common.off"}{else}{$settingValue|escape}{/if}</td>
</tr>
{/foreach}
</table>
{/if}{* !empty($sectionData) *}

{/foreach}
</div>
<div class="separator"></div>
<div id="serverInformation">
<h3>{translate key="admin.serverInformation"}</h3>
<p>{translate key="admin.serverInformationDescription"}</p>

<table class="data" width="100%">
{foreach from=$serverInfo key=settingName item=settingValue}
<tr valign="top">
	<td width="30%" class="label">{translate key=$settingName|escape}</td>
	<td width="70%" class="value">{$settingValue|escape}</td>
</tr>
{/foreach}
</table>

<a href="{url op="phpinfo"}" target="_blank">{translate key="admin.phpInfo"}</a>
</div>
{include file="common/footer.tpl"}

