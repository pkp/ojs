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

{assign var="pageTitle" value="submission.comments.comments"}
{assign var="pageId" value="submission.comments.comments"}
{include file="submission/comment/header.tpl"}

<table class="plainFormat" width="100%">
{foreach from=$articleComments item=comment}
<tr class="{cycle values="row,rowAlt"}">
	<td valign="top" width="25%">
		<div class="commentRole">{translate key=$comment->getRoleName()}</div>
		<div class="commentDate">{$comment->getDatePosted()|date_format:$datetimeFormatShort}</div>
		{if $commentType eq "peerReview"}
			<br />
			<div class="commentNote">
				{if $comment->getViewable()}
					{translate key="submission.comments.canShareWithAuthor"}
				{else}
					{translate key="submission.comments.cannotShareWithAuthor"}
				{/if}
			</div>
		{/if}
	</td>
	<td valign="top" width="75%">
		{if $comment->getAuthorId() eq $userId and not $isLocked}
			<div style="float: right"><a href="{$requestPageUrl}/editComment/{$articleId}/{$comment->getCommentId()}" class="tableAction">{translate key="common.edit"}</a> <a href="{$requestPageUrl}/deleteComment/{$articleId}/{$comment->getCommentId()}" onclick="return confirm('{translate|escape:"javascript" key="submission.comments.confirmDelete"}')" class="tableAction">{translate key="common.delete"}</a></div>
		{/if}
		<div class="commentTitle"><a name="{$comment->getCommentId()}"></a>{translate key="submission.comments.subject"}: {$comment->getCommentTitle()}</div>
		<div class="comments">{$comment->getComments()|nl2br}</div>
	</td>
</tr>
{foreachelse}
<tr>
	<td align="center">{translate key="submission.comments.noComments"}</td>
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
	<td class="formField"><input type="submit" name="save" value="{translate key="common.save"}" class="formButton" /> <input type="submit" name="saveAndEmail" value="{translate key="common.saveAndEmail"}" class="formButton" /> <input type="button" value="{translate key="common.done"}" class="formButtonPlain" onclick="window.close()" /></td>
</tr>
</table>
</div>
</form>
{else}
<input type="button" value="{translate key="common.done"}" class="formButtonPlain" style="width: 5em" onclick="window.close()" />
{/if}

{include file="submission/comment/footer.tpl"}
