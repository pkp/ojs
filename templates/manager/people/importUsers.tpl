{**
 * importUsers.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to import users from an uploaded file into a journal.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.people.importUsers"}
{include file="common/header.tpl"}

<form action="{$pageUrl}/manager/importUsers/confirm" method="post" enctype="multipart/form-data">

{if $error}
<p>
	<span class="formError">{translate key="$error"}</span>
</p>
{/if}

<p>{translate key="manager.people.importUsers.importInstructions"}</p>

<table width="100%" class="data">
	<tr>
		<td width="20%" class="label">{translate key="manager.people.importUsers.dataFile"}</td>
		<td width="80%" class="value"><input type="file" name="userFile" id="userFile" class="uploadField" /></td>
	</tr>
	<tr>
		<td colspan="2" class="label"><input type="checkbox" name="sendNotify" id="sendNotify" value="1"{if $sendNotify} checked="checked"{/if} /> <label for="sendNotify">{translate key="manager.people.importUsers.sendNotify"}</label></td>
	</tr>
	<tr>
		<td colspan="2" class="label"><input type="checkbox" name="continueOnError" id="continueOnError" value="1"{if $continueOnError} checked="checked"{/if} /> <label for="continueOnError">{translate key="manager.people.importUsers.continueOnError"}</label></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="formField">&nbsp;</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.upload"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/manager'" /></p>

</form>

{include file="common/footer.tpl"}
