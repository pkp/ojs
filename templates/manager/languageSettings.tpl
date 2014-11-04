{**
 * templates/manager/languageSettings.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to edit journal language settings.
 *
 *}
{strip}
{assign var="pageTitle" value="common.languages"}
{include file="common/header.tpl"}
{/strip}

<p><span class="instruct">{translate key="manager.languages.languageInstructions"}</span></p>

{include file="common/formErrors.tpl"}

{if count($availableLocales) > 1}
<form method="post" action="{url op="saveLanguageSettings"}">

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" rowspan="2" class="label">{fieldLabel name="primaryLocale" required="true" key="locale.primary"}</td>
	<td width="80%" colspan="3" class="value">
		<select id="primaryLocale" name="primaryLocale" size="1" class="selectMenu">
			{html_options options=$availableLocales selected=$primaryLocale}
		</select>
	</td>
</tr>
<tr valign="top">
	<td colspan="3" class="value"><span class="instruct">{translate key="manager.languages.primaryLocaleInstructions"}</span></td>
</tr>
<tr valign="top">
	<td class="label" rowspan="2">{fieldLabel suppressId="true" name="supportedLocales" key="locale.supported"}</td>
	<td colspan="3" class="value">
		<table class="data" width="100%">
		<tr valign="top">
			<td width="20%">&nbsp;</td>
			<td align="center" width="10%">{translate key="manager.language.ui"}</td>
			<td align="center" width="20%">{translate key="manager.language.submissions"}</td>
			<td align="center" width="10%">{translate key="manager.language.forms"}</td>
			<td>&nbsp;</td>
		</tr>
		{foreach from=$availableLocales key=localeKey item=localeName}
			<tr>
				<td>{$localeName|escape}</td>
				<td align="center"><input type="checkbox" name="supportedLocales[]" value="{$localeKey|escape}"{if in_array($localeKey, $supportedLocales)} checked="checked"{/if}/></td>
				<td align="center"><input type="checkbox" name="supportedSubmissionLocales[]" value="{$localeKey|escape}"{if in_array($localeKey, $supportedSubmissionLocales)} checked="checked"{/if}/></td>
				<td align="center"><input type="checkbox" name="supportedFormLocales[]" value="{$localeKey|escape}"{if in_array($localeKey, $supportedFormLocales)} checked="checked"{/if}/></td>
				<td><a href="{url op="reloadLocalizedDefaultSettings" localeToLoad=$localeKey}" onclick="return confirm('{translate|escape:"jsparam" key="manager.language.confirmDefaultSettingsOverwrite"}')" class="action">{translate key="manager.language.reloadLocalizedDefaultSettings"}</a></td>
			</tr>
		{/foreach}
		</table>
	</td>
</tr>
<tr valign="top">
	<td colspan="3" class="value"><span class="instruct">{translate key="manager.languages.supportedLocalesInstructions"}</span></td>
</tr>
</table>

<br />
<br />
<table id="languageDisplay" class="data" width="100%">
<tr valign="top">
  <td colspan="2" class="value"><span class="instruct">{translate key="manager.languages.locale.display.intro"}</span></td>
</tr>
<tr valign="top">
  <td width="20%" class="label">{fieldLabel name="localeDisplayTitle" required="true" key="manager.languages.locale.display.title"}</td>
  <td width="80%" class="value">
      <input type="radio" name="localeDisplayTitle" value="original"{if ($localeDisplayTitle == 'original')} checked="checked"{/if}/>{translate key="manager.languages.locale.display.title.original"}<br />
      <input type="radio" name="localeDisplayTitle" value="both"{if ($localeDisplayTitle == 'both')} checked="checked"{/if}/>{translate key="manager.languages.locale.display.title.both"}<br />
      <input type="radio" name="localeDisplayTitle" value="legacy"{if ($localeDisplayTitle == 'legacy')} checked="checked"{/if}/>{translate key="manager.languages.locale.display.title.legacy"}<br />
  </td>
</tr>
<tr valign="top">
  <td width="20%" class="label">{fieldLabel name="localeDisplayFile" required="true" key="manager.languages.locale.display.file"}</td>
  <td width="80%" class="value">
      <input type="radio" name="localeDisplayFile" value="show"{if ($localeDisplayFile == 'show')} checked="checked"{/if}/>{translate key="manager.languages.locale.display.file.show"}<br />
      <input type="radio" name="localeDisplayFile" value="hide"{if ($localeDisplayFile == 'hide')} checked="checked"{/if}/>{translate key="manager.languages.locale.display.file.hide"}<br />
      <input type="radio" name="localeDisplayFile" value="legacy"{if ($localeDisplayFile == 'legacy')} checked="checked"{/if}/>{translate key="manager.languages.locale.display.file.legacy"}<br />
  </td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager"}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{else}
<div class="separator"></div>
<p><span class="instruct">{translate key="manager.languages.noneAvailable"}</span></p>
{/if}

{include file="common/footer.tpl"}

