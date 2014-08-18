{**
 * plugins/themes/custom/settingsForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Custom Theme plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.theme.custom.name"}
{include file="common/header.tpl"}
{/strip}

<div id="customThemeSettings">
<div id="description">{translate key="plugins.theme.custom.description"}</div>

<div class="separator"></div>

<br />

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customThemeHeaderColour" required="true" key="plugins.theme.custom.header"}</td>
		<td width="80%" class="value">
			<input name="customThemeHeaderColour" type="text" id="customThemeHeaderColour" size="7" maxlength="7" value="{$customThemeHeaderColour|escape}" {if $customThemeHeaderColour}style="background-color: {$customThemeHeaderColour|escape};" {/if}/>
			<span onclick="openPicker('customThemeHeaderColour')" class="picker_buttons">{translate key="plugins.theme.custom.pickColour"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="customThemeLinkColour" required="true" key="plugins.theme.custom.link"}</td>
		<td class="value">
			<input name="customThemeLinkColour" type="text" id="customThemeLinkColour" size="7" maxlength="7" value="{$customThemeLinkColour|escape}" {if $customThemeLinkColour}style="background-color: {$customThemeLinkColour|escape};" {/if}/>
			<span onclick="openPicker('customThemeLinkColour')" class="picker_buttons">{translate key="plugins.theme.custom.pickColour"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="customThemeBackgroundColour" required="true" key="plugins.theme.custom.background"}</td>
		<td class="value">
			<input name="customThemeBackgroundColour" type="text" id="customThemeBackgroundColour" size="7" maxlength="7" value="{$customThemeBackgroundColour|escape}" {if $customThemeBackgroundColour}style="background-color: {$customThemeBackgroundColour|escape};" {/if}/>
			<span onclick="openPicker('customThemeBackgroundColour')" class="picker_buttons">{translate key="plugins.theme.custom.pickColour"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="customThemeForegroundColour" required="true" key="plugins.theme.custom.foreground"}</td>
		<td class="value">
			<input name="customThemeForegroundColour" type="text" id="customThemeForegroundColour" size="7" maxlength="7" value="{$customThemeForegroundColour|escape}" {if $customThemeForegroundColour}style="background-color: {$customThemeForegroundColour|escape};" {/if}/>
			<span onclick="openPicker('customThemeForegroundColour')" class="picker_buttons">{translate key="plugins.theme.custom.pickColour"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="customThemePerJournal" key="plugins.theme.custom.perJournal"}</td>
		<td class="value">
			<input name="customThemePerJournal" type="checkbox" id="customThemePerJournal" value="on" {if ($customThemePerJournal || $disablePluginPath)}checked="checked" {/if}{if $disablePluginPath}disabled="disabled" {/if}/>
			{if $disablePluginPath}<span class="instruct">{translate key="plugins.theme.custom.notWritablePlugin" stylesheetFileLocation=$stylesheetFilePluginLocation}</span>{/if}
		</td>
	</tr>
</table>

<br/>

{if !$canSave}{translate key="plugins.theme.custom.notWritable" stylesheetFileLocation=$stylesheetFileLocation}<br/>{/if}

<input type="submit" {if !$canSave}disabled="disabled" {/if}name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
