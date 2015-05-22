{**
 * plugins/generic/externalFeed/settingsForm.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * External feed plugin settings
 *
 *}
{assign var="pageTitle" value="plugins.generic.externalFeed.manager.settings"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{plugin_url path="feeds"}">{translate key="plugins.generic.externalFeed.manager.feeds"}</a></li>
	<li class="current"><a href="{plugin_url path="settings"}">{translate key="plugins.generic.externalFeed.manager.settings"}</a></li>
</ul>

<br />

<table width="100%" class="listing">
	<tr>
		<td class="headseparator">&nbsp;</td>
	</tr>
	<tr>
		<td>{translate key="plugins.generic.externalFeed.settings.description"}</td>
	</tr>
	<tr>
		<td class="headseparator">&nbsp;</td>
	</tr>
</table>

<form method="post" action="{plugin_url path="settings"}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

<h4>{translate key="plugins.generic.externalFeed.settings.styleSheet"}</h4>

<p>{translate key="plugins.generic.externalFeed.settings.stylesheetDescription"}: <a href="{$defaultStyleSheetUrl}" target="_blank">{translate key="plugins.generic.externalFeed.settings.defaultStyleSheet"}</a></p>

<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label"><label for="externalFeedStyleSheet">{translate key="plugins.generic.externalFeed.settings.useStyleSheet"}</label></td>
	<td width="80%" class="value"><input type="file" name="externalFeedStyleSheet" id="externalFeedStyleSheet" class="uploadField" /> <input type="submit" name="uploadStyleSheet" value="{translate key="common.upload"}" class="button" /></td>
</tr>
</table>

{if $externalFeedStyleSheet}
{translate key="common.fileName"}: <a href="{$publicFilesDir}/{$externalFeedStyleSheet.uploadName|escape:"url"}" class="file">{$externalFeedStyleSheet.name|escape}</a> {$externalFeedStyleSheet.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteStyleSheet" value="{translate key="common.delete"}" class="button" />
<br/>
{/if}

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
{include file="common/footer.tpl"}
