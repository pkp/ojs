{**
 * plugins/generic/browse/templates/settingsForm.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Browse plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.browse.manager.settings.browseSettings"}
{include file="common/header.tpl"}
{/strip}

<div id="browseSettings">
<div id="description">{translate key="plugins.generic.browse.manager.settings.description"}</div>

<div class="separator"></div>

<br />

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="journalContent" key="plugins.generic.browse.manager.settings.browse"}</td>
		<td width="80%" class="value">
			{translate key="plugins.generic.browse.manager.settings.browseByObjects"}<br />
			<input type="checkbox" name="enableBrowseBySections" id="enableBrowseBySections" value="1"{if $enableBrowseBySections} checked="checked"{/if}/>
			<label for="enableBrowseBySections">{translate key="plugins.generic.browse.manager.settings.enableBrowseBySections"}</label><br />
			<input type="checkbox" name="enableBrowseByIdentifyTypes" id="enableBrowseByIdentifyTypes" value="1"{if $enableBrowseByIdentifyTypes} checked="checked"{/if}/>
			<label for="enableBrowseByIdentifyTypes">{translate key="plugins.generic.browse.manager.settings.enableBrowseByIdentifyTypes"}</label><br />
		</td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="urnPrefix" key="plugins.generic.browse.manager.settings.excludedSections"}</td>
		<td width="80%" class="value">
			{translate key="plugins.generic.browse.manager.settings.excludedSections.description"}<br />
			<select name="excludedSections[]" id="excludedSections" class="selectMenu" multiple="multiple" size="5">
					<option {if in_array('', $excludedSections)}selected="selected" {/if}value=''>{translate key="common.none"}</option>
				{foreach from=$sections key=id item=title}
					<option {if in_array($id, $excludedSections)}selected="selected" {/if}value="{$id|escape}">{$title|escape}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="urnPrefix" key="plugins.generic.browse.manager.settings.excludedIdentifyTypes"}</td>
		<td width="80%" class="value">
			{translate key="plugins.generic.browse.manager.settings.excludedIdentifyTypes.description"}<br />
			<select name="excludedIdentifyTypes[]" id="excludedIdentifyTypes" class="selectMenu" multiple="multiple" size="5">
					<option {if in_array('', $excludedIdentifyTypes)}selected="selected" {/if}value=''>{translate key="common.none"}</option>
				{foreach from=$identifyTypes key=id item=identifyType}
					<option {if in_array($id, $excludedIdentifyTypes)}selected="selected" {/if}value="{$identifyType|escape}">{$identifyType|escape}</option>
				{/foreach}
			</select>
		</td>
	</tr>
</table>
</div>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
