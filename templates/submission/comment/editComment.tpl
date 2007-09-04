{**
 * editComment.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to edit comments.
 *
 * $Id$
 *}
{assign var="pageTitle" value="submission.comments.editComment"}
{include file="submission/comment/header.tpl"}

<form method="post" action="{url op="saveComment" path=$commentId}">
{if $hiddenFormParams}
	{foreach from=$hiddenFormParams item=hiddenFormParam key=key}
		<input type="hidden" name="{$key|escape}" value="{$hiddenFormParam|escape}" />
	{/foreach}
{/if}
{if $isPeerReviewComment}
<input type="hidden" name="viewable" value="{$viewable|escape}" />
{/if}

<a name="new"></a>
{include file="common/formErrors.tpl"}

<table class="data">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="commentTitle" key="submission.comments.subject"}</td>
	<td width="80%" class="value"><input type="text" id="commentTitle" name="commentTitle" value="{$commentTitle|escape}" size="50" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="comments" key="submission.comments.comments" required="true"}</td>
	<td class="value"><textarea name="comments" id="comments" rows="15" cols="50" class="textArea">{$comments}</textarea></td>
</tr>
{if $isPeerReviewComment}
<tr valign="top">
	<td>&nbsp;</td>
	<td class="value">
		<input type="checkbox" name="viewable" id="viewable" value="1"{if $viewable} checked="checked"{/if} />
		<label for="viewable">{translate key="submission.comments.viewableDescription"}</label>
	</td>
</tr>
{/if}
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if !$isPeerReviewComment}<input type="submit" name="saveAndEmail" value="{translate key="common.saveAndEmail"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1);" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="submission/comment/footer.tpl"}
