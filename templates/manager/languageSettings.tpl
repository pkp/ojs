{**
 * languageSettings.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to edit journal language settings.
 *
 * $Id$
 *}

{assign var="pageTitle" value="common.languages"}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/manager/saveLanguageSettings">

<div class="form">
{translate key="manager.languages.langugeInstructions"}<br /><br />

{include file="common/formErrors.tpl"}

{if count($availableLocales) > 1}
<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="primaryLocale" required="true"}{translate key="manager.languages.primaryLocale"}:{/formLabel}</td>
	<td class="formField"><select name="primaryLocale">
	{foreach from=$availableLocales key=localeKey item=localeName}
		<option value="{$localeKey}"{if $localeKey == $primaryLocale} selected="selected"{/if}>{$localeName}</option>
	{/foreach}
	</select></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.languages.primaryLocaleInstructions"}</td>
</tr>
<tr valign="top">
	<td class="formLabel">{formLabel name="supportedLocales"}{translate key="manager.languages.supportedLocales"}:{/formLabel}</td>
	<td>{foreach from=$availableLocales key=localeKey item=localeName}
		<input type="checkbox" name="supportedLocales[]" value="{$localeKey}"{if in_array($localeKey, $supportedLocales)} checked="checked"{/if}>{$localeName}<br />
	{/foreach}</td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.languages.supportedLocalesInstructions"}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="alternateLocale1"}{translate key="manager.languages.alternateLocale1"}:{/formLabel}</td>
	<td class="formField"><select name="alternateLocale1">
	<option value="">{translate key="common.notApplicable"}</option>
	{foreach from=$availableLocales key=localeKey item=localeName}
		<option value="{$localeKey}"{if $localeKey == $alternateLocale1} selected="selected"{/if}>{$localeName}</option>
	{/foreach}
	</select></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="alternateLocale2"}{translate key="manager.languages.alternateLocale2"}:{/formLabel}</td>
	<td class="formField"><select name="alternateLocale2">
	<option value="">{translate key="common.notApplicable"}</option>
	{foreach from=$availableLocales key=localeKey item=localeName}
		<option value="{$localeKey}"{if $localeKey == $alternateLocale2} selected="selected"{/if}>{$localeName}</option>
	{/foreach}
	</select></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.languages.alternateLocaleInstructions"}</td>
</tr>
<tr valign="top">
	<td class="formLabel">{translate key="manager.languages.alternativeLanguageOptions"}:</td>
	<td class="formField">
		<table class="plain">
		<tr>
			<td><input type="checkbox" name="journalTitleAltLanguages" value="1" /></td>
			<td>{translate key="manager.languages.journalTitleAltLanguages"}</td>
		</tr>
		<tr>
			<td><input type="checkbox" name="articleAltLanguages" value="1" /></td>
			<td>{translate key="manager.languages.articleAltLanguages"}</td>
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/manager'" /></td>
</tr>
</table>
{else}
<span class="errorText">{translate key="manager.languages.noneAvailable"}</span>
{/if}

</div>
</form>

{include file="common/footer.tpl"}
