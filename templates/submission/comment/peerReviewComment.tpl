{**
 * comment.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to enter comments.
 *
 * $Id$
 *}


{include file="submission/comment/header.tpl"}

<table class="data" width="100%">
{foreach from=$articleComments item=comment}
<tr valign="top">
	<td width="25%">
		<div class="commentRole">
			{if $showReviewLetters and $comment->getRoleId() eq $reviewer}
				{assign var="start" value="A"|ord}
				{assign var="reviewId" value=$comment->getAssocId()}
				{translate key=$comment->getRoleName()} {$reviewLetters[$reviewId]+$start|chr}
			{else}
				{translate key=$comment->getRoleName()}
			{/if}
		</div>
		<div class="commentDate">{$comment->getDatePosted()|date_format:$datetimeFormatShort}</div>
		<br />
		<div class="commentNote">
			{if $comment->getViewable()}
				{translate key="submission.comments.canShareWithAuthor"}
			{else}
				{translate key="submission.comments.cannotShareWithAuthor"}
			{/if}
		</div>
	</td>
	<td width="75%">
		{if $comment->getAuthorId() eq $userId and not $isLocked}
			<div style="float: right"><a href="{$requestPageUrl}/editComment/{$articleId}/{$comment->getCommentId()}" class="action">{translate key="common.edit"}</a> <a href="{$requestPageUrl}/deleteComment/{$articleId}/{$comment->getCommentId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.comments.confirmDelete"}')" class="action">{translate key="common.delete"}</a></div>
		{/if}
		<a name="{$comment->getCommentId()}"></a>
		{if $comment->getCommentTitle()}
			<div class="commentTitle">{translate key="submission.comments.subject"}: {$comment->getCommentTitle()}</div>
		{/if}
		<div class="comments">{$comment->getComments()|nl2br}</div>
	</td>
</tr>
{foreachelse}
<tr>
	<td class="nodata">{translate key="submission.comments.noReviews"}</td>
</tr>
{/foreach}
</table>

<br />
<br />

{if not $isLocked}
<form method="post" action="{$requestPageUrl}/{$commentAction}">
{if $hiddenFormParams}
	{foreach from=$hiddenFormParams item=hiddenFormParam key=key}
		<input type="hidden" name="{$key}" value="{$hiddenFormParam}" />
	{/foreach}
{/if}


<a name="new"></a>
{include file="common/formErrors.tpl"}

<table class="data" width="100%">
<tr valign="top">
	<td class="label">{fieldLabel name="commentTitle" key="submission.comments.subject"}</td>
	<td class="value"><input type="text" name="commentTitle" id="commentTitle" value="{$commentTitle|escape}" size="50" maxlength="100" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="authorComments"}{translate key="submission.comments.forAuthorEditor"}</td>
	<td class="value"><textarea id="authorComments" name="authorComments" rows="10" cols="50" class="textArea">{$authorComments}</textarea></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="comments"}{translate key="submission.comments.forEditor"}</td>
	<td class="value"><textarea id="comments" name="comments" rows="10" cols="50" class="textArea">{$comments}</textarea></td>
</tr>
</table>

<p><input type="submit" name="save" value="{translate key="common.save"}" class="button defaultButton" /> {if $canEmail}<input type="submit" name="saveAndEmail" value="{translate key="common.saveAndEmail"}" class="button" />{/if} <input type="button" value="{translate key="common.done"}" class="button" onclick="window.opener.location.reload(); window.close()" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{else}
<input type="button" value="{translate key="common.close"}" class="button defaultButton" style="width: 5em" onclick="window.close()" />
{/if}

{include file="submission/comment/footer.tpl"}
