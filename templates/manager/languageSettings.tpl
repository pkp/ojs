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

<p><span class="instruct">{translate key="manager.languages.languageInstructions"}</span></p>

{include file="common/formErrors.tpl"}

{if count($availableLocales) > 1}
<form method="post" action="{url op="saveLanguageSettings"}">

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="primaryLocale" required="true" key="locale.primary"}</td>
	<td width="80%" colspan="2" class="value"><select id="primaryLocale" name="primaryLocale" size="1" class="selectMenu">
	{foreach from=$availableLocales key=localeKey item=localeName}
		<option value="{$localeKey}"{if $localeKey == $primaryLocale} selected="selected"{/if}>{$localeName|escape}</option>
	{/foreach}
	</select></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td colspan="2" class="value"><span class="instruct">{translate key="manager.languages.primaryLocaleInstructions"}</span></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel suppressId="true" name="supportedLocales" key="locale.supported"}</td>
	<td colspan="2" class="value">{foreach from=$availableLocales key=localeKey item=localeName}
		<input type="checkbox" name="supportedLocales[]" id="supportedLocales-{$localeKey}" value="{$localeKey}"{if in_array($localeKey, $supportedLocales)} checked="checked"{/if}/> <label for="supportedLocales-{$localeKey}">{$localeName|escape}</label><br />
	{/foreach}</td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td colspan="2" class="value"><span class="instruct">{translate key="manager.languages.supportedLocalesInstructions"}</span></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="alternateLocale1" key="manager.languages.alternateLocale1"}</td>
	<td colspan="2" class="value"><select id="alternateLocale1" name="alternateLocale1" size="1" class="selectMenu">
	<option value="">{translate key="common.notApplicable"}</option>
	{foreach from=$availableLocales key=localeKey item=localeName}
		<option value="{$localeKey}"{if $localeKey == $alternateLocale1} selected="selected"{/if}>{$localeName|escape}</option>
	{/foreach}
	</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="alternateLocale2" key="manager.languages.alternateLocale2"}</td>
	<td colspan="2" class="value"><select id="alternateLocale2" name="alternateLocale2" size="1" class="selectMenu">
	<option value="">{translate key="common.notApplicable"}</option>
	{foreach from=$availableLocales key=localeKey item=localeName}
		<option value="{$localeKey}"{if $localeKey == $alternateLocale2} selected="selected"{/if}>{$localeName|escape}</option>
	{/foreach}
	</select></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td colspan="2" class="value"><span class="instruct">{translate key="manager.languages.alternateLocaleInstructions"}</span></td>
</tr>
<tr valign="top">
	<td rowspan="2" class="label">{translate key="manager.languages.alternativeLanguageOptions"}</td>
	<td width="5%"><input type="checkbox" name="journalTitleAltLanguages" id="journalTitleAltLanguages" value="1" /></td>
	<td width="75%"><label for="journalTitleAltLanguages">{translate key="manager.languages.journalTitleAltLanguages"}</label></td>
</tr>
<tr valign="top">
	<td><input type="checkbox" name="articleAltLanguages" id="articleAltLanguages" value="1" /></td>
	<td><label for="articleAltLanguages">{translate key="manager.languages.articleAltLanguages"}</label></td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$url page="manager"}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{else}
<div class="separator"></div>
<p><span class="instruct">{translate key="manager.languages.noneAvailable"}</span></p>
{/if}

{include file="common/footer.tpl"}
