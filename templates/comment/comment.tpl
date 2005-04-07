{**
 * comment.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reader comment editing
 *
 * $Id$
 *}

{assign var="pageTitle" value="comments.enterComment"}
{include file="common/header.tpl"}

<form action="{$requestPageUrl}/{if $commentId}edit/{$commentId}{else}add/{$articleId}/{$parentId}/save{/if}" method="post">
<table class="data" width="100%">
	<tr valign="top">
		<td class="label" width="20%"><label for="title">{translate key="comments.title"}</label></td>
		<td class="value" width="80%"><input type="text" class="textField" name="title" id="title" value="{$title|escape}" size="60" /></td>
	</tr>
	{if $isUserLoggedIn}
	<tr valign="top">
		<td class="label">&nbsp;</td>
		<td class="value">
			<input type="checkbox" name="anonymous" id="anonymous">
			<label for="anonymous">{translate key="comments.postAnonymously"}</label>
		</td>
	</tr>
	{/if}
	<tr valign="top">
		<td class="label"><label for="bodyField">{translate key="comments.body"}</label></td>
		<td class="value">
			<textarea class="textArea" name="body" id="bodyField" rows="5" cols="60">{$body|escape}</textarea>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$requestPageUrl}/comments/{$articleId}/{$parentId}" /></p>

</form>

{include file="common/footer.tpl"}
