{**
 * temlates/rtadmin/addthis.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RT Administration settings.
 *
 *}
{strip}
{assign var="pageTitle" value="rt.admin.sharing"}
{include file="common/header.tpl"}
{/strip}

<form method="post" action='{url op="saveSharingSettings"}'>

<p>{translate key="rt.admin.sharing.description"}</p>

<div class="separator">&nbsp;</div>

<h3>{translate key="rt.admin.sharing.basic"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="20%"></td>
		<td width="80%"><input type="checkbox" name="sharingEnabled" id="sharingEnabled" {if isset($sharingEnabled) && $sharingEnabled} checked="checked" {/if} /> <label for="sharingEnabled">{translate key="rt.admin.sharing.enabled"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="sharingUserName">{translate key="rt.admin.sharing.userNameLabel"}</label></td>
		<td><input type="text" name="sharingUserName" id="sharingUserName" value="{$sharingUserName|escape}"/></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="sharingButtonStyle">{translate key="rt.admin.sharing.buttonStyleLabel"}</label></td>
		<td>
		{html_options name="sharingButtonStyle" id="sharingButtonStyle" values=$sharingButtonStyleOptions|@array_values output=$sharingButtonStyleOptions|@array_values selected=$sharingButtonStyle}
		</td>
	</tr>
	<tr>
		<td class="label"></td>
		<td><input type="checkbox" name="sharingDropDownMenu" id="sharingDropDownMenu" {if isset($sharingDropDownMenu) && $sharingDropDownMenu} checked="checked" {/if} /> <label for="sharingDropDownMenu">{translate key="rt.admin.sharing.dropDownMenuLabel"}</label></td>
	</tr>
</table>

<h3>{translate key="rt.admin.sharing.advanced"}</h3>
<p>{translate key="rt.admin.sharing.customizationLink"}</p>
<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="20%"><label for="sharingBrand">{translate key="rt.admin.sharing.brandLabel"}</label></td>
		<td width="80%"><input type="text" name="sharingBrand" id="sharingBrand" value="{$sharingBrand|escape}"/></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="sharingDropDown">{translate key="rt.admin.sharing.dropDownLabel"}</label></td>
		<td><textarea rows="4" cols="20" name="sharingDropDown" id="sharingDropDown">{$sharingDropDown|escape}</textarea></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="sharingLanguage">{translate key="rt.admin.sharing.languageLabel"}</label></td>
		<td>
			{html_options name="sharingLanguage" id="sharingLanguage" options="$sharingLanguageOptions" selected=$sharingLanguage}
		</td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="sharingLogo">{translate key="rt.admin.sharing.logolabel"}</label></td>
		<td><input type="text" name="sharingLogo" id="sharingLogo" value="{$sharingLogo}" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="sharingLogoBackground">{translate key="rt.admin.sharing.logoBackgroundLabel"}</label></td>
		<td><input type="text" name="sharingLogoBackground" id="sharingLogoBackground" value="{$sharingLogoBackground|escape}"/></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="sharingLogoColor">{translate key="rt.admin.sharing.logoColorLabel"}</label></td>
		<td><input type="text" name="sharingLogoColor" id="sharingLogoColor" value="{$sharingLogoColor|escape}"/></td>
	</tr>
</table>

<p><input type="submit" value='{translate key="common.save"}' class="button defaultButton" /> 
<input type="button" value='{translate key="common.cancel"}' class="button" onclick="document.location.href='{url page=rtadmin escape=false}'" />
</p>

</form>

{include file="common/footer.tpl"}

