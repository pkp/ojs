{**
 * templates/comment/comment.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reader comment editing
 *
 *}
{strip}
{assign var="pageTitle" value="comments.enterComment"}
{include file="common/header.tpl"}
{/strip}

<script>
<!--
{literal}
function handleAnonymousCheckbox(theBox) {
	var submitForm = document.getElementById('submit');
	if (theBox.checked) {
		submitForm.posterName.disabled = false;
		submitForm.posterEmail.disabled = false;
		submitForm.posterName.value = "";
		submitForm.posterEmail.value = "";
		submitForm.posterName.focus();
	} else {
		submitForm.posterName.disabled = true;
		submitForm.posterEmail.disabled = true;
		{/literal}{if $isUserLoggedIn && ($enableComments == COMMENTS_ANONYMOUS || $enableComments == COMMENTS_UNAUTHENTICATED)}
		submitForm.posterName.value = "{$userName|escape}";
		submitForm.posterEmail.value = "{$userEmail|escape}";
		{/if}{literal}
	}
}
// -->
{/literal}
</script>

{include file="common/formErrors.tpl"}
{assign var=parentId value=$parentId|default:"0"}
<div id="commentForm">
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#submit').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="submit" action="{if $commentId}{url op="edit" path=$articleId|to_array:$galleyId:$commentId}{else}{url op="add" path=$articleId|to_array:$galleyId:$parentId:"save"}{/if}" method="post">
<table class="data">
	<tr>
		<td class="label"><label for="posterName">{translate key="comments.name"}</label></td>
		<td class="value"><input type="text" class="textField" name="posterName" id="posterName" value="{$posterName|escape}" size="40" maxlength="90" /></td>
	</tr>
	<tr>
		<td class="label"><label for="posterEmail">{translate key="comments.email"}</label></td>
		<td class="value"><input type="text" class="textField" name="posterEmail" id="posterEmail" value="{$posterEmail|escape}" size="40" maxlength="90" /></td>
	</tr>
	{if $isUserLoggedIn && ($enableComments == COMMENTS_ANONYMOUS || $enableComments == COMMENTS_UNAUTHENTICATED)}
	<tr>
		<td class="label">&nbsp;</td>
		<td class="value">
			<input type="checkbox" name="anonymous" id="anonymous" onclick="handleAnonymousCheckbox(this)">
			<label for="anonymous">{translate key="comments.postAnonymously"}</label>
		</td>
	</tr>
	{/if}
	<tr>
		<td class="label"><label for="title">{translate key="comments.title"}</label></td>
		<td class="value"><input type="text" class="textField" name="title" id="title" value="{$title|escape}" size="60" maxlength="255" /></td>
	</tr>
	<tr>
		<td class="label"><label for="commentBody">{translate key="comments.body"}</label></td>
		<td class="value">
			<textarea class="textArea" name="body" id="commentBody" rows="5" cols="60">{$commentBody|escape}</textarea>
		</td>
	</tr>

{if $captchaEnabled}
	<tr>
		<td class="label" valign="top">{fieldLabel name="recaptcha_challenge_field" required="true" key="common.captchaField"}</td>
		<td class="value">
			{$reCaptchaHtml}
		</td>
	</tr>
{/if}

</table>
<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="location.href='{url page="comment" op="view" path=$articleId|to_array:$galleyId:$parentId}';" /></p>

</form>
</div>

{include file="common/footer.tpl"}

