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

<p><span class="instruct">{translate key="manager.languages.langugeInstructions"}</span></p>

{include file="common/formErrors.tpl"}

{if count($availableLocales) > 1}
<form method="post" action="{$pageUrl}/manager/saveLanguageSettings">

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="primaryLocale" required="true" key="locale.primary"}</td>
	<td width="80%" colspan="2" class="value"><select name="primaryLocale" class="selectMenu">
	{foreach from=$availableLocales key=localeKey item=localeName}
		<option value="{$localeKey}"{if $localeKey == $primaryLocale} selected="selected"{/if}>{$localeName}</option>
	{/foreach}
	</select></td>
</tr>
<tr valign="top">
	<td></td>
	<td colspan="2" class="value"><span class="instruct">{translate key="manager.languages.primaryLocaleInstructions"}</span></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="supportedLocales" key="locale.supported"}</td>
	<td colspan="2" class="value">{foreach from=$availableLocales key=localeKey item=localeName}
		<input type="checkbox" name="supportedLocales[]" value="{$localeKey}"{if in_array($localeKey, $supportedLocales)} checked="checked"{/if}>&nbsp;&nbsp;{$localeName}<br />
	{/foreach}</td>
</tr>
<tr valign="top">
	<td></td>
	<td colspan="2" class="value"><span class="instruct">{translate key="manager.languages.supportedLocalesInstructions"}</span></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="alternateLocale1" key="manager.languages.alternateLocale1"}</td>
	<td colspan="2" class="value"><select name="alternateLocale1" class="selectMenu">
	<option value="">{translate key="common.notApplicable"}</option>
	{foreach from=$availableLocales key=localeKey item=localeName}
		<option value="{$localeKey}"{if $localeKey == $alternateLocale1} selected="selected"{/if}>{$localeName}</option>
	{/foreach}
	</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="alternateLocale2" key="manager.languages.alternateLocale2"}</td>
	<td colspan="2" class="value"><select name="alternateLocale2" class="selectMenu">
	<option value="">{translate key="common.notApplicable"}</option>
	{foreach from=$availableLocales key=localeKey item=localeName}
		<option value="{$localeKey}"{if $localeKey == $alternateLocale2} selected="selected"{/if}>{$localeName}</option>
	{/foreach}
	</select></td>
</tr>
<tr valign="top">
	<td></td>
	<td colspan="2" class="value"><span class="instruct">{translate key="manager.languages.alternateLocaleInstructions"}</span></td>
</tr>
<tr valign="top">
	<td rowspan="2" class="label">{translate key="manager.languages.alternativeLanguageOptions"}</td>
	<td width="5%"><input type="checkbox" name="journalTitleAltLanguages" value="1" /></td>
	<td width="75%">{translate key="manager.languages.journalTitleAltLanguages"}</td>
</tr>
<tr valign="top">
	<td><input type="checkbox" name="articleAltLanguages" value="1" /></td>
	<td>{translate key="manager.languages.articleAltLanguages"}</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/manager'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{else}
<div class="separator"></div>
<p><span class="instruct">{translate key="manager.languages.noneAvailable"}</span></p>
{/if}

{include file="common/footer.tpl"}
