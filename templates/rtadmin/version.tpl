{**
 * version.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RTAdmin version editing
 *
 * $Id$
 *}

{assign var="pageTitle" value="rt.admin.versions.edit.editVersion"}
{include file="common/header.tpl"}

<form action="{$requestPageUrl}/saveVersion/{$version->getVersionId()}" method="post">
<table class="data" width="100%">
	<tr valign="top">
		<td class="label" width="20%"><label for="title">{translate key="rt.version.title"}</label></td>
		<td class="value" width="80%"><input type="text" class="textField" name="title" id="title" value="{$version->getTitle()|escape}" size="60" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="key">{translate key="rt.version.key"}</label></td>
		<td class="value"><input type="text" class="textField" name="key" id="key" value="{$version->getKey()|escape}" size="60" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="locale">{translate key="rt.version.locale"}</label></td>
		<td class="value"><input type="text" class="textField" name="locale" id="locale" maxlength="5" size="5" value="{$version->getLocale()|escape}" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="description">{translate key="rt.version.description"}</label></td>
		<td class="value">
			<textarea class="textArea" name="description" id="description" rows="5" cols="60">{$version->getDescription()|escape}</textarea>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$requestPageUrl}/versions" /></p>

</form>

{include file="common/footer.tpl"}
