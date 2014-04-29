{**
 * templates/admin/languages.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to edit site language settings.
 *
 *}
{strip}
{assign var="pageTitle" value="common.languages"}
{include file="common/header.tpl"}
{/strip}

<form method="post" action="{url op="saveLanguageSettings"}">
<div id="languageSettings">
<h3>{translate key="admin.languages.languageSettings"}</h3>

<table class="form">
<tr valign="top">
	<td width="20%" class="label">{translate key="locale.primary"}</td>
	<td width="80%" class="value">
		<select name="primaryLocale" id="primaryLocale" size="1" class="selectMenu">
		{foreach from=$installedLocales item=localeKey}
			<option value="{$localeKey|escape}"{if $localeKey == $primaryLocale} selected="selected"{/if}>{$localeNames.$localeKey|escape}</option>
		{/foreach}
		</select>
		<br />
		<span class="instruct">{translate key="admin.languages.primaryLocaleInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="locale.supported"}</td>
	<td>
		<table width="100%">
		{foreach from=$installedLocales item=localeKey}
		<tr valign="top">
			<td width="5%"><input type="checkbox" name="supportedLocales[]" id="supportedLocales-{$localeKey|escape}" value="{$localeKey|escape}"{if in_array($localeKey, $supportedLocales)} checked="checked"{/if} /></td>
			<td width="95%">
				<label for="supportedLocales-{$localeKey|escape}">{$localeNames.$localeKey|escape}</label>
				{if !$localesComplete[$localeKey]}
					<span class="formError">*</span>
					{assign var=incompleteLocaleFound value=1}
				{/if}
			</td>
		</tr>
		{/foreach}
		</table>
		<span class="instruct">{translate key="admin.languages.supportedLocalesInstructions"}</span>
		{if $incompleteLocaleFound}
			<br/>
			<span class="formError">*</span>&nbsp;{translate key="admin.locale.maybeIncomplete"}
		{/if}{* $incompleteLocaleFound *}
	</td>
</tr>
</table>
</div>
<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="admin" escape=false}'" /></p>

</form>

<div class="separator"></div>

<form method="post" action="{url op="installLocale"}">
<div id="installLanguages">
<h3>{translate key="admin.languages.installLanguages"}</h3>
<h4>{translate key="admin.languages.installedLocales"}</h4>
<table class="data" width="100%">
{foreach from=$installedLocales item=localeKey}
<tr valign="top">
	<td width="30%">&bull;&nbsp;{$localeNames.$localeKey|escape} ({$localeKey|escape})</td>
	<td width="70%"><a href="{url op="reloadLocale" locale=$localeKey}" onclick="return confirm('{translate|escape:"jsparam" key="admin.languages.confirmReload"}')" class="action">{translate key="admin.languages.reload"}</a>{if $localeKey != $primaryLocale}&nbsp;|&nbsp;<a href="{url op="uninstallLocale" locale=$localeKey}" onclick="return confirm('{translate|escape:"jsparam" key="admin.languages.confirmUninstall"}')" class="action">{translate key="admin.languages.uninstall"}</a>{/if}</td>
</tr>
{/foreach}
</table>
</div>
<div id="installNewLocales">
<h4>{translate key="admin.languages.installNewLocales"}</h4>
<p>{translate key="admin.languages.installNewLocalesInstructions"}</p>
{assign var=incompleteLocaleFound value=0}
{foreach from=$uninstalledLocales item=localeKey}
<input type="checkbox" name="installLocale[]" id="installLocale-{$localeKey|escape}" value="{$localeKey|escape}" /> <label for="installLocale-{$localeKey|escape}">{$localeNames.$localeKey|escape} ({$localeKey|escape})</label>
{if !$localesComplete[$localeKey]}
	<span class="formError">*</span>
	{assign var=incompleteLocaleFound value=1}
{/if}
<br />
{foreachelse}
{assign var="noLocalesToInstall" value="1"}
<span class="nodata">{translate key="admin.languages.noLocalesAvailable"}</span>
{/foreach}
{if $incompleteLocaleFound}
	<br />
	<span class="formError">*</span>&nbsp;{translate key="admin.locale.maybeIncomplete"}
{/if}{* $incompleteLocaleFound *}

{if not $noLocalesToInstall}
<p><input type="submit" value="{translate key="admin.languages.installLocales"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="admin" escape=false}'" /></p>
{/if}
</div>
</form>

<div class="separator"></div>
<div id="downloadLocales">
<h3>{translate key="admin.languages.downloadLocales"}</h3>

{if $downloadAvailable}

<table class="data" width="100%">
	{foreach from=$downloadableLocales item=downloadableLocale}
		<tr valign="top">
			<td width="30%">&bull;&nbsp;{$downloadableLocale.name|escape} ({$downloadableLocale.key})</td>
			<td width="70%">
				<a href="{url op="downloadLocale" locale=$downloadableLocale.key}" class="action">{translate key="admin.languages.download"}</a>
			</td>
		</tr>
	{foreachelse}
		<tr valign="top">
			<td colspan="4" class="nodata">{translate key="common.none"}</td>
		</tr>
	{/foreach}
</table>
{else}{* not $downloadAvailable *}
	{translate key="admin.languages.downloadUnavailable"}
{/if}{* $downloadAvailable *}
</div>
{include file="common/footer.tpl"}

