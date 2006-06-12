{**
 * sourceSettings.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Authentication source settings under site administration.
 *
 * $Id$
 *}

{assign var="pageTitle" value="admin.authSources"}
{include file="common/header.tpl"}

<br />

<form method="post" action="{url op="updateAuthSource" path=$authId}">

<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="title" key="common.title"}</td>
		<td width="80%" class="value"><input type="text" id="title" name="title" value="{$title|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label" colspan="2">
			<h4>{translate key="common.options"}</h4>
		</td>
	</tr>
	<tr valign="top">
		<td class="label" align="right">
			<input type="checkbox" name="settings[syncProfiles]" id="syncProfiles" value="1"{if $settings.syncProfiles} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="syncProfiles">{translate key="admin.auth.enableSyncProfiles"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td class="label" align="right">
			<input type="checkbox" name="settings[syncPasswords]" id="syncPasswords" value="1"{if $settings.syncPasswords} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="syncPasswords">{translate key="admin.auth.enableSyncPasswords"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td class="label" align="right">
			<input type="checkbox" name="settings[createUsers]" id="createUsers" value="1"{if $settings.createUsers} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="createUsers">{translate key="admin.auth.enableCreateUsers"}</label>
		</td>
	</tr>
</table>

{if $pluginTemplate}{include file=$pluginTemplate}{/if}

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="auth" escape=false}'" /></p>

</form>

{include file="common/footer.tpl"}
