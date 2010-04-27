{**
 * plugins/generic/sword/depositPointForm.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * SWORD plugin settings
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.sword.depositPoints.edit"}
{include file="common/header.tpl"}
{/strip}
<div id="depositPointSettings">

<form method="post" action="{plugin_url path="editDepositPoint"|to_array:$depositPointId}">
{include file="common/formErrors.tpl"}

<table width="100%" class="data">
	<tr valign="top">
		<td width="30%" class="label"><label for="name">{translate key="plugins.generic.sword.depositPoints.name"}</label></td>
		<td width="70%" class="value"><input type="text" name="depositPoint[name]" id="name" value="{$depositPoint.name|escape}" size="40" maxlength="90" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="swordUrl">{translate key="plugins.importexport.sword.depositUrl"}</label></td>
		<td class="value"><input type="text" name="depositPoint[url]" id="swordUrl" value="{$depositPoint.url|escape}" size="40" maxlength="90" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="swordUsername">{translate key="user.username"}</label></td>
		<td class="value"><input type="text" name="depositPoint[username]" id="swordUsername" value="{$depositPoint.username|escape}" size="20" maxlength="90" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="swordPassword">{translate key="user.password"}</label></td>
		<td class="value">
			<input type="password" name="depositPoint[password]" id="swordPassword" value="{$depositPoint.password|escape}" size="20" maxlength="90" /><br/>
			<span class="instruct">{translate key="plugins.generic.sword.depositPoints.password.description"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="depositPointType">{translate key="common.type"}</label></td>
		<td class="value">
			{html_options_translate name="depositPoint[type]" options=$depositPointTypes selected=$depositPoint.type}
		</td>
	</tr>
</table>
{translate key="plugins.generic.sword.depositPoints.type.description"}

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/> <input type="button" class="button" value="{translate key="common.cancel"}" onclick="document.location='{plugin_url path="settings"}';"/>
</form>
</div><!-- depositPointSettings -->

{include file="common/footer.tpl"}
