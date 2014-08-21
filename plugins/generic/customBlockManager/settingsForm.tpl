{**
 * plugins/generic/customBlockManager/settingsForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for Custom Block Plugin settings.
 *
 *}
{assign var="pageTitle" value="plugins.generic.customBlockManager.displayName"}
{include file="common/header.tpl"}

{url|assign:"sysPluginsUrl" page="manager" op="plugins"}
{url|assign:"setup56" page="manager" op="setup" path="5"}
{translate key="plugin.generic.customBlockManager.introduction" systemPluginsUrl=$sysPluginsUrl setupStep56=$setup56}

<br />
<br />

<form method="post" action="{plugin_url path="settings"}">

{include file="common/formErrors.tpl"}
<br />

<input type="hidden" name="deletedBlocks" value="{$deletedBlocks|escape}" />

<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
	<td width="20%" align="left">{translate key="plugins.generic.customBlockManager.blockName"}</td>
	<td width="80%" align="left" >{translate key="plugins.generic.customBlockManager.action"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	
{foreach name=blocks from=$blocks key=blockIndex item=block}
<tr valign="top">
	<td width="20%" class="value" align="left"><input type="text" class="textField" name="blocks[{$blockIndex|escape}]" id="blocks-{$blockIndex|escape}" value="{$block|escape}" size="20" maxlength="40" /></td>
	<td  align="left"><input type="submit" name="delBlock[{$blockIndex|escape}]" value="{translate key="plugins.generic.customBlockManager.delete"}" class="button" /></td>
</tr>
<tr>
	<td colspan="4" class="separator">&nbsp;</td>
</tr>
{foreachelse}
<tr valign="top">
	<td width="20%" class="value" align="right"><input type="text" class="textField" name="blocks[0]" id="blocks-0" size="20" maxlength="40" /></td>
</tr>

{/foreach}
</table>
<p><input type="submit" class="button" name="addBlock" value="{translate key="plugins.generic.customBlockManager.addBlock"}" />
<input type="submit" class="button" name="save" value="{translate key="common.save"}" />
<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager" op="plugins" escape=false}'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
