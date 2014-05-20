{**
 * templates/rtadmin/version.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RTAdmin version editing
 *
 *}
{strip}
{assign var="pageTitle" value="rt.admin.versions.edit.editVersion"}
{include file="common/header.tpl"}
{/strip}

{if $versionId}
	<ul class="menu">
		<li class="current"><a href="{url op="editVersion" path=$versionId}" class="action">{translate key="rt.admin.versions.metadata"}</a></li>
		<li><a href="{url op="contexts" path=$versionId}" class="action">{translate key="rt.contexts"}</a></li>
	</ul>
{/if}

<br />

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#versionForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="versionForm" action="{if $versionId}{url op="saveVersion" path=$versionId}{else}{url op="createVersion" path="save"}{/if}" method="post">
<table class="data">
	<tr>
		<td class="label"><label for="title">{translate key="rt.version.title"}</label></td>
		<td class="value"><input type="text" class="textField" name="title" id="title" value="{$title|escape}" size="60" /></td>
	</tr>
	<tr>
		<td class="label"><label for="key">{translate key="rt.version.key"}</label></td>
		<td class="value"><input type="text" class="textField" name="key" id="key" value="{$key|escape}" size="60" /></td>
	</tr>
	<tr>
		<td class="label"><label for="locale">{translate key="rt.version.locale"}</label></td>
		<td class="value"><input type="text" class="textField" name="locale" id="locale" maxlength="5" size="5" value="{$locale|escape}" /></td>
	</tr>
	<tr>
		<td class="label"><label for="description">{translate key="rt.version.description"}</label></td>
		<td class="value">
			<textarea class="textArea richContent" name="description" id="description" rows="5" cols="60">{$description|escape}</textarea>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="versions" escape=false}'" /></p>

</form>

{include file="common/footer.tpl"}

