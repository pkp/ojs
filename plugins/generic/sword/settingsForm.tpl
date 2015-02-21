{**
 * plugins/generic/sword/settingsForm.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * SWORD plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.sword.displayName"}
{include file="common/header.tpl"}
{/strip}
<div id="swordSettings">
<div id="description">{translate key="plugins.generic.sword.description"}</div>

<div class="separator">&nbsp;</div>

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<h3>{translate key="plugins.generic.sword.settings"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="10%" class="label" align="right"><input type="checkbox" name="allowAuthorSpecify" id="allowAuthorSpecify" {if $allowAuthorSpecify}checked="checked" {/if}/></td>
		<td width="90%" class="value"><label for="allowAuthorSpecify">{translate key="plugins.generic.sword.settings.allowAuthorSpecify"}</label></td>
	</tr>
</table>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/> <input type="button" class="button" value="{translate key="common.cancel"}" onclick="document.location='{url op="plugin"}';"/>
</form>
</div><!-- swordSettings -->

<div id="depositPoints">
<h3>{translate key="plugins.generic.sword.settings.depositPoints"}</h3>

<table width="100%" class="listing">
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td>{translate key="plugins.importexport.sword.depositPoint"}</td>
		<td>{translate key="common.type"}</td>
		<td width="15%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
{foreach name=depositPoints from=$depositPoints key=key item=depositPoint}
	<tr valign="top">
		<td>{$depositPoint.url|escape}</td>
		<td>
			{assign var=depositPointType value=$depositPoint.type}
			{translate key=$depositPointTypes.$depositPointType}
		</td>
		<td>
			<a class="action" href="{plugin_url path="editDepositPoint"|to_array:$key}">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a class="action" href="{plugin_url path="deleteDepositPoint"|to_array:$key}">{translate key="common.delete"}</a>
		</td>
	</tr>
	{if !$smarty.foreach.depositPoints.last}
		<tr valign="top">
			<td colspan="3" class="separator">&nbsp;</td>
		</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td colspan="3" class="nodata">{translate key="common.none"}</td>
	</tr>
{/foreach}
	<tr valign="top">
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
</table>

<p><a class="action" href="{plugin_url path="createDepositPoint"}">{translate key="plugins.generic.sword.depositPoints.create"}</a></p>

{translate key="plugins.generic.sword.depositPoints.type.description"}

</div><!-- depositPoints -->

{include file="common/footer.tpl"}
