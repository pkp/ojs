{**
 * journalSettings.tpl
 *
 * Copyright (c) 2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Basic journal settings under site administration.
 *
 * $Id$
 *}

{assign var="pageTitle" value="admin.journals.importOJS1"}
{assign var="helpTopicId" value="site.siteManagement"}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/admin/doImportOJS1">

{include file="common/formErrors.tpl"}

{if $importError}
<p>
	<span class="formError">{translate key="admin.journals.importErrors"}:</span>
	<ul class="formErrorList">
		<li>{$importError|escape}</li>
	</ul>
</p>
{/if}

<p><span class="instruct">{translate key="admin.journals.importOJS1Instructions"}</span></p>

<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="title" key="journal.path" required="true"}</td>
		<td width="80%" class="value">
			<input type="text" id="journalPath" name="journalPath" value="{$journalPath|escape}" size="16" maxlength="32" class="textField" />
			<br />
			<span class="instruct">{translate key="admin.journal.pathImportInstructions"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="title" key="admin.journal.importPath" required="true"}</td>
		<td class="value">
			<input type="text" id="importPath" name="importPath" value="{$importPath|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="admin.journal.importPathInstructions"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.options"}</td>
		<td class="value">
			<input type="checkbox" name="options[]" id="options[importSubscriptions]" value="importSubscriptions"{if $options && in_array('importSubscriptions', $options)} checked="checked"{/if} /> <label for="options[importSubscriptions]">{translate key="admin.journals.importSubscriptions"}</label>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.import"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/admin/journals'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
