{**
 * editorDecisionComment.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to enter comments.
 *
 * $Id$
 *}
{strip}
{include file="submission/comment/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
<!--
// In case this page is the result of a comment submit, reload the parent
// so that the necessary buttons will be activated.
window.opener.location.reload();
// -->
{/literal}
</script>
<div id="existingComments">
<table class="data" width="100%">
{foreach from=$articleComments item=comment}
<div id="comment">
<tr valign="top">
	<td width="25%">
		<div class="commentRole">{translate key=$comment->getRoleName()}</div>
		<div class="commentDate">{$comment->getDatePosted()|date_format:$datetimeFormatShort}</div>
	</td>
	<td width="75%">
		{if $comment->getAuthorId() eq $userId and not $isLocked}
			<div style="float: right"><a href="{url op="deleteComment" path=$articleId|to_array:$comment->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.comments.confirmDelete"}')" class="action">{translate key="common.delete"}</a></div>
		{/if}
		<div id="{$comment->getId()}"></a>
		{if $comment->getCommentTitle() neq ""}
			<div class="commentTitle">{translate key="submission.comments.subject"}: {$comment->getCommentTitle()|escape}</div>
		{/if}
		</div>
		<div class="comments">{$comment->getComments()|strip_unsafe_html|nl2br}</div>
	</td>
</tr>
</div>
{foreachelse}
<tr>
	<td class="nodata">{translate key="submission.comments.noComments"}</td>
</tr>
{/foreach}
</table>
</div>
<br />
<br />

{* Following help text added by eScholarship (BLH 2013-03-20) *}
{if $isAuthor}
<table width="90%" align="center">
<tr><td style="background: yellow; padding: 15px;">
<p><strong>Authors, Please Note:</strong></p>
<p>Peer reviewer feedback can be provided in two ways: as comments typed into a review form
and/or as uploaded files. Depending on which option(s) reviewers have utilized for this
manuscript, you may need to do one or both of the following:</p>
<ol>
        <li><strong>Comments typed into a review form</strong> can be viewed in the Editor/Author Correspondence thread above. Reviewer comments will be surrounded by dashed lines and usually appear at the end of the decision letter sent to you by the journal editor. <strong>If you do not see reviewer comments above</strong> contact the editor and ask them to re-send your decision letter.</li>
        <li><strong>Uploaded files</strong> can be viewed by returning to the previous screen (step 2. Review) and clicking on any files that have been made available to you in the Peer Review section.</li>
</ol>
<p> More detailed information can be found in <a href="https://vimeo.com/33303895" target="_blank">this help video.</a></p>
</td></tr>
</table>
<br />
<br />
{/if}

{if not $isLocked and $isEditor}

<form method="post" action="{url op=$commentAction}">
{if $hiddenFormParams}
	{foreach from=$hiddenFormParams item=hiddenFormParam key=key}
		<input type="hidden" name="{$key|escape}" value="{$hiddenFormParam|escape}" />
	{/foreach}
{/if}


<div id="new">
{include file="common/formErrors.tpl"}

<table class="data" width="100%">
<tr valign="top">
	<td class="label">{fieldLabel name="commentTitle" key="submission.comments.subject"}</td>
	<td class="value"><input type="text" name="commentTitle" id="commentTitle" value="{$commentTitle|escape}" size="50" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="comments" key="submission.comments.addComment"}</td>
	<td class="value"><textarea id="comments" name="comments" rows="10" cols="50" class="textArea">{$comments|escape}</textarea></td>
</tr>
</table>

<p><input type="submit" name="save" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.close"}" class="button" onclick="window.close()" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
</form>

{else}
<input type="button" value="{translate key="common.close"}" class="button defaultButton" style="width: 5em" onclick="window.close()" />
{/if}

{include file="submission/comment/footer.tpl"}

