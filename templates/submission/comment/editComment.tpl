{**
 * editComment.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to edit comments.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.comments.editComment"}
{assign var="pageId" value="submission.comments.editComment"}
{include file="submission/comment/header.tpl"}

<form method="post" action="{$requestPageUrl}/saveComment/{$commentId}">
{if $hiddenFormParams}
	{foreach from=$hiddenFormParams item=hiddenFormParam key=key}
		<input type="hidden" name="{$key}" value="{$hiddenFormParam}" />
	{/foreach}
{/if}

<a name="new"></a>
<div class="form">
{include file="common/formErrors.tpl"}

<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="commentTitle"}{translate key="submission.comments.subject"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="commentTitle" value="{$commentTitle|escape}" size="60" maxlength="100" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="comments" required="true"}{translate key="submission.comments.comments"}:{/formLabel}</td>
	<td class="formField"><textarea name="comments" rows="10" cols="60">{$comments|nl2br}</textarea></td>
</tr>
{if $commentType eq "peerReview"}
<tr>
	<td></td>
	<td class="formField">
		<input type="checkbox" name="viewable" value="1" />
		{translate key="submission.comments.viewableDescription"}
	</td>
</tr>
{/if}
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="window.close()" /></td>
</tr>
</table>
</div>
</form>


{include file="submission/comment/footer.tpl"}
