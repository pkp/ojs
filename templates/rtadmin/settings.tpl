{**
 * step1.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of journal setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="rt.admin.settings}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/rtadmin/saveSettings">

<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="3%"><input type="checkbox" name="captureCite" id="captureCite" /></td>
		<td class="value" width="97%"><label for="captureCite">{translate key="rt.admin.settings.captureCite"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="viewMetadata" id="viewMetadata" /></td>
		<td class="value"><label for="viewMetadata">{translate key="rt.admin.settings.viewMetadata"}</td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="supplementaryFiles" id="supplementaryFiles" /></td>
		<td class="value"><label for="supplementaryFiles">{translate key="rt.admin.settings.supplementaryFiles"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="printerFriendly" id="printerFriendly" /></td>
		<td class="value"><label for="printerFriendly">{translate key="rt.admin.settings.printerFriendly"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="authorBio" id="authorBio" /></td>
		<td class="value"><label for="authorBio">{translate key="rt.admin.settings.authorBio"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="defineTerms" id="defineTerms" /></td>
		<td class="value"><label for="defineTerms">{translate key="rt.admin.settings.defineTerms"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="emailAuthor" id="emailAuthor" /></td>
		<td class="value"><label for="emailAuthor">{translate key="rt.admin.settings.emailAuthor"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="checkbox" name="emailOthers" id="emailOthers" /></td>
		<td class="value"><label for="emailOthers">{translate key="rt.admin.settings.emailOthers"}</label></td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/rtadmin'" /></p>

</form>

{include file="common/footer.tpl"}
