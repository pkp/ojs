{**
 * importUsers.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to import users from an uploaded file into a journal.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.people.importUsers"}
{include file="common/header.tpl"}

<form action="{$pageUrl}/manager/importUsers/import" method="post" enctype="multipart/form-data">
<div class="form">
{if $error}
	<span class="formError">{translate key="$error"}</span>
	<br /><br />
{/if}

<div class="formInstructions">{translate key="manager.people.importUsers.importInstructions"}</div>

<br />

<table class="form">
<tr>
	<td class="formLabel">{translate key="manager.people.importUsers.dataFile"}:</td>
	<td class="formField"><input type="file" name="userFile" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel"><input type="checkbox" name="sendNotify" value="1"{if $sendNotify} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.people.importUsers.sendNotify"}</td>
</tr>
<tr>
	<td class="formLabel"><input type="checkbox" name="continueOnError" value="1"{if $continueOnError} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.people.importUsers.continueOnError"}</td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.upload"}" class="formButton" /></td>
</tr>
</table>
</form>

{include file="common/footer.tpl"}
