{**
 * plugins/generic/lucene/templates/settingsForm.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Lucene plugin settings
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.lucene.settings.luceneSettings"}
{include file="common/header.tpl"}
{/strip}
<div id="luceneSettings">
<div id="description">{translate key="plugins.generic.lucene.settings.description"}</div>

<div class="separator"></div>

<br />

<form class="pkp_form" method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="searchEndpoint" required="true" key="plugins.generic.lucene.settings.searchEndpoint"}</td>
		<td width="80%" class="value"><input type="text" name="searchEndpoint" id="searchEndpoint" value="{$searchEndpoint|escape}" size="45" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="plugins.generic.lucene.settings.searchEndpointInstructions"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="username" required="true" key="plugins.generic.lucene.settings.username"}</td>
		<td width="80%" class="value"><input type="text" name="username" id="username" value="{$username|escape}" size="15" maxlength="25" class="textField" />
			<br />
			<span class="instruct">{translate key="plugins.generic.lucene.settings.usernameInstructions"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="password" required="true" key="plugins.generic.lucene.settings.password"}</td>
		<td width="80%" class="value"><input type="password" name="password" id="password" value="{$password|escape}" size="15" maxlength="25" class="textField" />
			<br />
			<span class="instruct">{translate key="plugins.generic.lucene.settings.passwordInstructions"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="instId" required="true" key="plugins.generic.lucene.settings.instId"}</td>
		<td width="80%" class="value"><input type="text" name="instId" id="instId" value="{$instId|escape}" size="15" maxlength="25" class="textField" />
			<br />
			<span class="instruct">{translate key="plugins.generic.lucene.settings.instIdInstructions"}</span>
		</td>
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
