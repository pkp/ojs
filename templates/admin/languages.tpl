{**
 * languages.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to edit site language settings.
 *
 * $Id$
 *}

{assign var="pageTitle" value="common.languages"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$pageUrl}/admin/settings">{translate key="admin.siteSettings"}</a></li>
	<li><a href="{$pageUrl}/admin/journals">{translate key="admin.hostedJournals"}</a></li>
	<li class="current"><a href="{$pageUrl}/admin/languages">{translate key="common.languages"}</a></li>
</ul>

<ul class="menu">
	<li><a href="{$pageUrl}/admin/systemInfo">{translate key="admin.systemInformation"}</a></li>
	<li><a href="{$pageUrl}/admin/expireSessions" onclick="return confirm('{translate|escape:"javascript" key="admin.confirmExpireSessions"}')">{translate key="admin.expireSessions"}</a></li>
	<li><a href="{$pageUrl}/admin/clearTemplateCache" onclick="return confirm('{translate|escape:"javascript" key="admin.confirmClearTemplateCache"}')">{translate key="admin.clearTemplateCache"}</a></li>
</ul>

<br/>

<form method="post" action="{$pageUrl}/admin/saveLanguageSettings">

<h3>{translate key="admin.languages.languageSettings"}</h3>

<table class="form">
<tr valign="top">
	<td width="20%" class="label">{translate key="locale.primary"}</td>
	<td width="80%" class="value"><select name="primaryLocale" class="selectMenu">
	{foreach from=$installedLocales item=localeKey}
		<option value="{$localeKey}"{if $localeKey == $primaryLocale} selected="selected"{/if}>{$localeNames.$localeKey}</option>
	{/foreach}
	</select></td>
</tr>
<tr valign="top">
	<td></td>
	<td class="value"><span class="instruct">{translate key="admin.languages.primaryLocaleInstructions"}</span></td>
</tr>
<tr valign="top">
	<td class="label">{translate key="locale.supported"}</td>
	<td>{foreach from=$installedLocales item=localeKey}
		<input type="checkbox" name="supportedLocales[]" value="{$localeKey}"{if in_array($localeKey, $supportedLocales)} checked="checked"{/if}>&nbsp;&nbsp;{$localeNames.$localeKey}<br />
	{/foreach}</td>
</tr>
<tr valign="top">
	<td></td>
	<td class="value"><span class="instruct">{translate key="admin.languages.supportedLocalesInstructions"}</span></td>
</tr>
<tr valign="top">
	<td class="label">{translate key="admin.languages.languageOptions"}</td>
	<td class="value">
		<input type="checkbox" name="profileLocalesEnabled" value="1"{if $profileLocalesEnabled} checked="checked"{/if} />&nbsp;&nbsp;
		{translate key="admin.languages.profileLocales"}
	</td>
</tr>
</table>
<p><input type="submit" value="{translate key="common.save"}" class="button" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/admin'" /></p>

</form>

<br />

<form method="post" action="{$pageUrl}/admin/installLocale">

<h3>{translate key="admin.languages.installLanguages"}</h3>
<h4>{translate key="admin.languages.installedLocales"}</h4>
<ul>
<table class="data" width="100%">
{foreach from=$installedLocales item=localeKey}
<tr valign="top">
	<td width="20%"><li>{$localeNames.$localeKey} ({$localeKey})</li></td>
	<td width="80%"><a href="{$pageUrl}/admin/reloadLocale?locale={$localeKey}" onclick="return confirm('{translate|escape:"javascript" key="admin.languages.confirmReload"}')" class="action">{translate key="admin.languages.reload"}</a>{if $localeKey != $primaryLocale} <a href="{$pageUrl}/admin/uninstallLocale?locale={$localeKey}" onclick="return confirm('{translate|escape:"javascript" key="admin.languages.confirmUninstall"}')" class="action">{translate key="admin.languages.uninstall"}</a>{/if}</td>
</tr>
{/foreach}
</table>
</ul>

<h4>{translate key="admin.languages.installNewLocales"}</h4>
<p>{translate key="admin.languages.installNewLocalesInstructions"}</p>
{foreach from=$uninstalledLocales item=localeKey}
<input type="checkbox" name="installLocale[]" value="{$localeKey}" />&nbsp;&nbsp;{$localeNames.$localeKey} ({$localeKey})<br />
{foreachelse}
{assign var="noLocalesToInstall" value="1"}
<span class="nodata">{translate key="admin.languages.noLocalesAvailable"}</span>
{/foreach}

{if not $noLocalesToInstall}
<p><input type="submit" value="{translate key="admin.languages.installLocales"}" class="button" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/admin'" /></p>
</tr>
</table>
{/if}

</form>

{include file="common/footer.tpl"}
