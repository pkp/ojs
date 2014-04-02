{**
 * plugins/generic/stopForumSpam/settingsForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Stop Forum Spam plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.stopForumSpam.manager.stopForumSpamSettings"}
{include file="common/header.tpl"}
{/strip}
<div id="stopForumSpamSettings">
<div id="description">{translate key="plugins.generic.stopForumSpam.manager.settings.description"}</div>

<div class="separator"></div>

<br />

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="checkIp" key="plugins.generic.stopForumSpam.manager.settings.checkIp"}</td>
		<td width="80%" class="value"><input type="checkbox" name="checkIp" id="checkIp" value="checkIp" {if $checkIp}checked="checked" {/if}/></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="checkEmail" key="plugins.generic.stopForumSpam.manager.settings.checkEmail"}</td>
		<td width="80%" class="value"><input type="checkbox" name="checkEmail" id="checkEmail" value="checkEmail" {if $checkEmail}checked="checked" {/if}/></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="checkUsername" key="plugins.generic.stopForumSpam.manager.settings.checkUsername"}</td>
		<td width="80%" class="value"><input type="checkbox" name="checkUsername" id="checkUsername" value="checkUsername" {if $checkUsername}checked="checked" {/if}/></td>
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
