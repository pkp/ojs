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

<script type="text/javascript">
{literal}
function handleAnonymousCheckbox(theBox) {
	if (theBox.checked) {
		document.submit.posterName.disabled = false;
		document.submit.posterEmail.disabled = false;
		document.submit.posterName.value = "";
		document.submit.posterEmail.value = "";
		document.submit.posterName.focus();
	} else {
		document.submit.posterName.disabled = true;
		document.submit.posterEmail.disabled = true;
		{/literal}{if $isUserLoggedIn && ($enableComments == COMMENTS_ANONYMOUS || $enableComments == COMMENTS_UNAUTHENTICATED)}
		document.submit.posterName.value = "{$userName|escape}";
		document.submit.posterEmail.value = "{$userEmail|escape}";
		{/if}{literal}
	}
}

{/literal}
</script>

<form name="submit" action="{$requestPageUrl}/{if $commentId}edit/{$articleId}/{$galleyId}/{$commentId}{else}add/{$articleId}/{$galleyId}/{$parentId}/save{/if}" method="post">
<table class="data" width="100%">
	<tr valign="top">
		<td class="label" width="20%"><label for="posterName">{translate key="comments.name"}</label></td>
		<td class="value" width="80%"><input type="text" class="textField" name="posterName" id="posterName" value="{$posterName|escape}" size="40" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="posterEmail">{translate key="comments.email"}</label></td>
		<td class="value"><input type="text" class="textField" name="posterEmail" id="posterEmail" value="{$posterEmail|escape}" size="40" /></td>
	</tr>
	{if $isUserLoggedIn && ($enableComments == COMMENTS_ANONYMOUS || $enableComments == COMMENTS_UNAUTHENTICATED)}
	<tr valign="top">
		<td class="label">&nbsp;</td>
		<td class="value">
			<input type="checkbox" name="anonymous" id="anonymous" onClick="handleAnonymousCheckbox(this)">
			<label for="anonymous">{translate key="comments.postAnonymously"}</label>
		</td>
	</tr>
	{/if}
	<tr valign="top">
		<td class="label"><label for="title">{translate key="comments.title"}</label></td>
		<td class="value"><input type="text" class="textField" name="title" id="title" value="{$title|escape}" size="60" /></td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="bodyField">{translate key="comments.body"}</label></td>
		<td class="value">
			<textarea class="textArea" name="body" id="bodyField" rows="5" cols="60">{$body|escape}</textarea>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$requestPageUrl}/comments/{$articleId}/{$galleyId}/{$parentId}" /></p>

</form>

{include file="common/footer.tpl"}
