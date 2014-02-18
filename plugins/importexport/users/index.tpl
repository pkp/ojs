{**
 * plugins/importexport/users/index.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.users.displayName"}
{include file="common/header.tpl"}
{/strip}

<br/>
<div id="exportUsers">
<h3>{translate key="plugins.importexport.users.export.exportUsers"}</h3>

<ul class="plain">
	<li>
		<script>
			$(function() {ldelim}
				// Attach the form handler.
				$('#exportByRoleForm').pkpHandler('$.pkp.controllers.form.FormHandler');
			{rdelim});
		</script>
		<form class="pkp_form" id="exportByRoleForm" action="{plugin_url path="exportByRole"}" method="post">
			&#187; {translate key="plugins.importexport.users.export.exportByRole"}<br/>
			&nbsp;&nbsp;&nbsp;&nbsp;<select name="roles[]" size="5" multiple="multiple" class="selectMenu">
				{foreach from=$roleOptions item=roleOption key=roleKey}
					{if $roleKey != ''}<option value="{$roleKey|escape}">{translate key=$roleOption}</option>{/if}
				{/foreach}
			</select><br/>
			&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" class="button" value="{translate key="plugins.importexport.users.export.exportUsers"}"/>

		</form>
		&nbsp;
	</li>
	<li>&#187; <a href="{plugin_url path="exportAll"}">{translate key="plugins.importexport.users.export.exportAllUsers"}</a></li>
</ul>
</div>
<div id="importUsers">
<h3>{translate key="plugins.importexport.users.import.importUsers"}</h3>

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#exportUsersForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="exportUsersForm" action="{plugin_url path="confirm"}" method="post" enctype="multipart/form-data">

<p>{translate key="plugins.importexport.users.import.instructions"}</p>

<table class="data">
	<tr>
		<td class="label">{translate key="plugins.importexport.users.import.dataFile"}</td>
		<td class="value"><input type="file" name="userFile" id="userFile" class="uploadField" /></td>
	</tr>
	<tr>
		<td colspan="2" class="label"><input type="checkbox" name="sendNotify" id="sendNotify" value="1"{if $sendNotify} checked="checked"{/if} /> <label for="sendNotify">{translate key="plugins.importexport.users.import.sendNotify"}</label></td>
	</tr>
	<tr>
		<td colspan="2" class="label"><input type="checkbox" name="continueOnError" id="continueOnError" value="1"{if $continueOnError} checked="checked"{/if} /> <label for="continueOnError">{translate key="plugins.importexport.users.import.continueOnError"}</label></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="formField">&nbsp;</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.upload"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager" path="importexport" escape=false}'" /></p>

</form>
</div>

{include file="common/footer.tpl"}
