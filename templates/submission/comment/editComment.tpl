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
{include file="common/formErrors.tpl"}

<table class="data">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="commentTitle" key="submission.comments.subject"}</td>
	<td width="80%" class="value"><input type="text" id="commentTitle" name="commentTitle" value="{$commentTitle|escape}" size="50" maxlength="100" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="comments" required="true" key="submission.comments.comments"}</td>
	<td class="value"><textarea name="comments" id="comments" rows="10" cols="50">{$comments|nl2br}</textarea></td>
</tr>
{if $commentType eq "peerReview"}
<tr valign="top">
	<td></td>
	<td class="data">
		<input type="checkbox" name="viewable" value="1" />
		{translate key="submission.comments.viewableDescription"}
	</td>
</tr>
{/if}
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1);" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>


{include file="submission/comment/footer.tpl"}
