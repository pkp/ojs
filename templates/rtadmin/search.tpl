{**
 * search.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RTAdmin search editing
 *
 * $Id$
 *}

{assign var="pageTitle" value="rt.admin.searches.edit.editSearch"}
{include file="common/header.tpl"}

<form action="{$requestPageUrl}/saveSearch/{$versionId}/{$contextId}/{$searchId}" method="post">
<table class="data" width="100%">
	<tr valign="top">
		<td class="label" width="20%"><label for="title">{translate key="rt.search.title"}</label></td>
		<td class="value" width="80%"><input type="text" class="textField" name="title" id="title" value="{$title|escape}" size="60" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="order">{translate key="rt.context.order"}</label></td>
		<td class="value"><input type="text" class="textField" name="order" id="order" value="{$order|escape}" size="5" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="url">{translate key="rt.search.url"}</label></td>
		<td class="value"><input type="text" class="textField" name="url" id="url" value="{$url|escape}" size="60" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="searchUrl">{translate key="rt.search.searchUrl"}</label></td>
		<td class="value"><input type="text" class="textField" name="searchUrl" id="searchUrl" value="{$searchUrl|escape}" size="60" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="searchPost">{translate key="rt.search.searchPost"}</label></td>
		<td class="value"><input type="text" class="textField" name="searchPost" id="searchPost" value="{$searchPost|escape}" size="60" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="description">{translate key="rt.context.description"}</label></td>
		<td class="value">
			<textarea class="textArea" name="description" id="description" rows="5" cols="60">{$description|escape}</textarea>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$requestPageUrl}/searches/{$versionId}/{$contextId}" /></p>

</form>

{include file="common/footer.tpl"}
