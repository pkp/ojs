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
{assign var="pageId" value="admin.languages"}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/admin/saveLanguageSettings">

<div class="formSectionTitle">{translate key="admin.languages.languageSettings"}</div>
<div class="formSection">
<table class="form">
<tr>
	<td class="formLabel">{translate key="admin.languages.primaryLocale"}:</td>
	<td class="formField"><select name="primaryLocale">
	{foreach from=$installedLocales item=localeKey}
		<option value="{$localeKey}"{if $localeKey == $primaryLocale} selected="selected"{/if}>{$localeNames.$localeKey}</option>
	{/foreach}
	</select></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="admin.languages.primaryLocaleInstructions"}</td>
</tr>
<tr valign="top">
	<td class="formLabel">{translate key="admin.languages.supportedLocales"}:</td>
	<td>{foreach from=$installedLocales item=localeKey}
		<input type="checkbox" name="supportedLocales[]" value="{$localeKey}"{if in_array($localeKey, $supportedLocales)} checked="checked"{/if}>{$localeNames.$localeKey}<br />
	{/foreach}</td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="admin.languages.supportedLocalesInstructions"}</td>
</tr>
<tr valign="top">
	<td class="formLabel">{translate key="admin.languages.languageOptions"}:</td>
	<td class="formField">
		<table class="plain">
		<tr>
			<td><input type="checkbox" name="profileLocalesEnabled" value="1"{if $profileLocalesEnabled} checked="checked"{/if} /></td>
			<td>{translate key="admin.languages.profileLocales"}</td>
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/admin'" /></td>
</tr>
</table>
</div>

</form>

<br />

<form method="post" action="{$pageUrl}/admin/installLocale">

<div class="formSectionTitle">{translate key="admin.languages.installLanguages"}</div>
<div class="formSection">
<div class="formSubSectionTitle">{translate key="admin.languages.installedLocales"}</div>
<ul>
<table class="plain">
{foreach from=$installedLocales item=localeKey}
<tr>
	<td><li>{$localeNames.$localeKey} ({$localeKey})</li></td>
	<td><a href="{$pageUrl}/admin/reloadLocale?locale={$localeKey}" onclick="return confirm('{translate|escape:"javascript" key="admin.languages.confirmReload"}')" class="tableButton">{translate key="admin.languages.reload"}</a>{if $localeKey != $primaryLocale} <a href="{$pageUrl}/admin/uninstallLocale?locale={$localeKey}" onclick="return confirm('{translate|escape:"javascript" key="admin.languages.confirmUninstall"}')" class="tableButton">{translate key="admin.languages.uninstall"}</a>{/if}</td>
</tr>
{/foreach}
</table>
</ul>

<div class="formSubSectionTitle">{translate key="admin.languages.installNewLocales"}</div>
<div class="formSectionDesc">{translate key="admin.languages.installNewLocalesInstructions"}</div>
<div class="formSectionIndent">
{foreach from=$uninstalledLocales item=localeKey}
<input type="checkbox" name="installLocale[]" value="{$localeKey}" /> {$localeNames.$localeKey} ({$localeKey})<br />
{foreachelse}
{assign var="noLocalesToInstall" value="1"}
<b>{translate key="admin.languages.noLocalesAvailable"}</b>
{/foreach}
</div>

{if not $noLocalesToInstall}
<table class="form">
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="admin.languages.installLocales"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/admin'" /></td>
</tr>
</table>
{/if}
</div>

</form>

{include file="common/footer.tpl"}
