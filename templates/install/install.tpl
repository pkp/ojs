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

{translate key="installer.installationInstructions" baseUrl=$baseUrl}

<br /><br />

<form method="post" action="{$pageUrl}/install/install">
{include file="common/formErrors.tpl"}

{if $isInstallError}
<span class="formError">{translate key="installer.installErrorsOccurred"}:</span>
<ul class="formErrorList">
	<li>{if $dbErrorMsg}{translate key="common.error.databaseError" error=$dbErrorMsg}{else}{translate key=$errorMsg}{/if}</li>
</ul>
{translate key="installer.reinstallAfterDatabaseError"}
<br /><br />
<div class="spacer">&nbsp;</div>
{/if}

<div class="formSectionTitle">{translate key="installer.databaseSettings"}</div>
<div class="formSection">
<table class="form">
<tr>
	<td class="formLabel">{formLabel name="databaseDriver"}{translate key="installer.databaseDriver"}:{/formLabel}</td>
	<td class="formField"><select name="databaseDriver" size="1">
		{html_options options=$databaseDriverOptions selected=$databaseDriver}
	</select></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="databaseHost"}{translate key="installer.databaseHost"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="databaseHost" value="{$databaseHost|escape}" size="30" maxlength="60" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="databaseUsername"}{translate key="installer.databaseUsername"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="databaseUsername" value="{$databaseUsername|escape}" size="30" maxlength="60" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="databasePassword"}{translate key="installer.databasePassword"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="databasePassword" value="{$databasePassword|escape}" size="30" maxlength="60" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="databaseName"}{translate key="installer.databaseName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="databaseName" value="{$databaseName|escape}" size="30" maxlength="60" class="textField" /></td>
</tr>
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="createDatabase" value="1"{if $createDatabase} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="installer.createDatabase"}</td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="installer.createDatabaseInstructions"}</td>
</tr>
</table>
</div>

<br />

<table class="form">
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="installer.installOJS"}" class="formButton" /> <input type="submit" name="manualInstall" value="{translate key="installer.manualInstall"}" class="formButton" /></td>
</tr>
</table>
</form>

{include file="common/footer.tpl"}
