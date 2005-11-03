{**
 * install.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Installation form.
 *
 * $Id$
 *}

{assign var="pageTitle" value="installer.ojsInstallation"}
{include file="common/header.tpl"}

{if is_writeable('config.inc.php')}{assign_translate var="writable_config" key="installer.checkYes"}{else}{assign_translate var="writable_config" key="installer.checkNo"}{/if}
{if is_writeable('public')}{assign_translate var="writable_public" key="installer.checkYes"}{else}{assign_translate var="writable_public" key="installer.checkNo"}{/if}
{if is_writeable('templates/t_cache')}{assign_translate var="writable_templates_cache" key="installer.checkYes"}{else}{assign_translate var="writable_templates_cache" key="installer.checkNo"}{/if}
{if is_writeable('templates/t_compile')}{assign_translate var="writable_templates_compile" key="installer.checkYes"}{else}{assign_translate var="writable_templates_compile" key="installer.checkNo"}{/if}

{translate key="installer.installationInstructions" version=$version->getVersionString() baseUrl=$baseUrl pageUrl=$pageUrl writable_config=$writable_config writable_public=$writable_public writable_templates_cache=$writable_templates_cache writable_templates_compile=$writable_templates_compile}


<div class="separator"></div>


<form method="post" action="{$pageUrl}/install/install">
{include file="common/formErrors.tpl"}

{if $isInstallError}
<p>
	<span class="formError">{translate key="installer.installErrorsOccurred"}:</span>
	<ul class="formErrorList">
		<li>{if $dbErrorMsg}{translate key="common.error.databaseError" error=$dbErrorMsg}{else}{translate key=$errorMsg}{/if}</li>
	</ul>
</p>
{/if}


<h3>{translate key="installer.localeSettings"}</h3>

<p>{translate key="installer.localeSettingsInstructions" supportsMBString=$supportsMBString}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="locale" key="locale.primary"}</td>
		<td width="80%" class="value">
			<select name="locale" id="locale" size="1" class="selectMenu">
				{html_options options=$localeOptions selected=$locale}
			</select>
			<br />
			<span class="instruct">{translate key="installer.localeInstructions"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel suppressId="true" name="additionalLocales" key="installer.additionalLocales"}</td>
		<td class="value">
			{foreach from=$localeOptions key=localeKey item=localeName}
				<input type="checkbox" name="additionalLocales[]" id="additionalLocales-{$localeKey}" value="{$localeKey}"{if in_array($localeKey, $additionalLocales)} checked="checked"{/if} /> <label for="additionalLocales-{$localeKey}">{$localeName} ({$localeKey})</label><br />
			{/foreach}
			<span class="instruct">{translate key="installer.additionalLocalesInstructions"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="clientCharset" key="installer.clientCharset"}</td>
		<td class="value">
			<select name="clientCharset" id="clientCharset" size="1" class="selectMenu">
				{html_options options=$clientCharsetOptions selected=$clientCharset}
			</select>
			<br />
			<span class="instruct">{translate key="installer.clientCharsetInstructions"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="connectionCharset" key="installer.connectionCharset"}</td>
		<td class="value">
			<select name="connectionCharset" id="connectionCharset" size="1" class="selectMenu">
				{html_options options=$connectionCharsetOptions selected=$connectionCharset}
			</select>
			<br />
			<span class="instruct">{translate key="installer.connectionCharsetInstructions"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="databaseCharset" key="installer.databaseCharset"}</td>
		<td class="value">
			<select name="databaseCharset" id="databaseCharset" size="1" class="selectMenu">
				{html_options options=$databaseCharsetOptions selected=$databaseCharset}
			</select>
			<br />
			<span class="instruct">{translate key="installer.databaseCharsetInstructions"}</span>
		</td>
	</tr>
</table>


<div class="separator"></div>


<h3>{translate key="installer.fileSettings"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="filesDir" key="installer.filesDir"}</td>
		<td width="80%" class="value">
			<input type="text" name="filesDir" id="filesDir" value="{$filesDir|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="installer.filesDirInstructions"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value"><input type="checkbox" name="skipFilesDir" id="skipFilesDir" value="1"{if $skipFilesDir} checked="checked"{/if} /> <label for="skipFilesDir">{translate key="installer.skipFilesDir"}</label></td>
	</tr>
</table>


<div class="separator"></div>


<h3>{translate key="installer.securitySettings"}</h3>


<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="encryption" key="installer.encryption"}</td>
		<td width="80%" class="value">
			<select name="encryption" id="encryption" size="1" class="selectMenu">
				{html_options options=$encryptionOptions selected=$encryption}
			</select>
			<br />
			<span class="instruct">{translate key="installer.encryptionInstructions"}</span>
		</td>
	</tr>
</table>


<div class="separator"></div>


<h3>{translate key="installer.administratorAccount"}</h3>

<p>{translate key="installer.administratorAccountInstructions"}</p>

<table width="100%" class="data">
	<tr valign="top">	
		<td width="20%" class="label">{fieldLabel name="adminUsername" key="user.username"}</td>
		<td width="80%" class="value"><input type="text" name="adminUsername" id="adminUsername" value="{$adminUsername|escape}" size="20" maxlength="32" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="adminPassword" key="user.password"}</td>
		<td class="value"><input type="password" name="adminPassword" id="adminPassword" value="{$adminPassword|escape}" size="20" maxlength="32" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="adminPassword2" key="user.register.repeatPassword"}</td>
		<td class="value"><input type="password" name="adminPassword2" id="adminPassword2" value="{$adminPassword2|escape}" size="20" maxlength="32" class="textField" /></td>
	</tr>
	<tr valign="top">	
		<td width="20%" class="label">{fieldLabel name="adminEmail" key="user.email"}</td>
		<td width="80%" class="value"><input type="text" name="adminEmail" id="adminEmail" value="{$adminEmail|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
</table>


<div class="separator"></div>


<h3>{translate key="installer.databaseSettings"}</h3>

<p>{translate key="installer.administratorAccountInstructions"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="databaseDriver" key="installer.databaseDriver"}</td>
		<td width="80%" class="value">
			<select name="databaseDriver" id="databaseDriver" size="1" class="selectMenu">
				{html_options options=$databaseDriverOptions selected=$databaseDriver}
			</select>
			<br />
			<span class="instruct">{translate key="installer.databaseDriverInstructions"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="databaseHost" key="installer.databaseHost"}</td>
		<td class="value">
			<input type="text" name="databaseHost" id="databaseHost" value="{$databaseHost|escape}" size="30" maxlength="60" class="textField" />
			<br />
			<span class="instruct">{translate key="installer.databaseHostInstructions"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="databaseUsername" key="installer.databaseUsername"}</td>
		<td class="value"><input type="text" name="databaseUsername" id="databaseUsername" value="{$databaseUsername|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="databasePassword" key="installer.databasePassword"}</td>
		<td class="value"><input type="text" name="databasePassword" id="databasePassword" value="{$databasePassword|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="databaseName" key="installer.databaseName"}</td>
		<td class="value"><input type="text" name="databaseName" id="databaseName" value="{$databaseName|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<input type="checkbox" name="createDatabase" id="createDatabase" value="1"{if $createDatabase} checked="checked"{/if} /> <label for="createDatabase">{translate key="installer.createDatabase"}</label>
			<br />
			<span class="instruct">{translate key="installer.createDatabaseInstructions"}</span>
		</td>
	</tr>
</table>


<div class="separator"></div>


<h3>{translate key="installer.miscSettings"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="oaiRepositoryId" key="installer.oaiRepositoryId"}</td>
		<td width="80%" class="value">
			<input type="text" name="oaiRepositoryId" id="oaiRepositoryId" value="{$oaiRepositoryId|escape}" size="30" maxlength="60" class="textField" />
			<br />
			<span class="instruct">{translate key="installer.oaiRepositoryIdInstructions"}</span>
		</td>
	</tr>
</table>


<div class="separator"></div>


<p><input type="submit" value="{translate key="installer.installOJS"}" class="button defaultButton" /> <input type="submit" name="manualInstall" value="{translate key="installer.manualInstall"}" class="button" /></p>

</form>

{include file="common/footer.tpl"}
