{**
 * proofread.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the status of an author's submission -- proofreading subtable.
 *
 *
 * $Id$
 *}

<!-- START OF PROOFREADING -->
<h3>{translate key="submission.proofreading"}</h3>
{translate key="user.role.proofreader"}:&nbsp;{if $proofAssignment->getProofreaderId()}{$proofAssignment->getProofreaderFullName()}{else}{translate key="common.none"}{/if}<br/>

<table width="100%" class="listing">
	<tr class="heading" valign="bottom">
		<td width="40%" colspan="2">{translate key="author.submissions.proofreadingCorrections"}</td>
		<td width="20%">{translate key="submission.request"}</td>
		<td width="20%">{translate key="submission.underway"}</td>
		<td width="20%">{translate key="submission.complete"}</td>
	</tr>

<!-- START AUTHOR COMMENTS -->
	<tr valign="top">
		<td width="5%">1.</td>
		<td width="35%">
			{translate key="editor.article.authorProofing"}
			<br/>
			<form method="post" action="{$requestPageUrl}/authorProofreadingComplete">
				<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
				<input type="submit" class="button" value="{translate key="submission.complete"}" {if not $proofAssignment->getDateAuthorNotified() or $proofAssignment->getDateAuthorCompleted()}disabled="disabled"{/if}>
			</form>
		</td>
		<td>{if $proofAssignment->getDateAuthorNotified()}{$proofAssignment->getDateAuthorNotified()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
		<td>{if $proofAssignment->getDateAuthorUnderway()}{$proofAssignment->getDateAuthorUnderway()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
		<td>{if $proofAssignment->getDateAuthorCompleted()}{$proofAssignment->getDateAuthorCompleted()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
	</tr>
<!-- END AUTHOR COMMENTS -->
	<tr><td colspan="5" class="separator"></td></tr>
<!-- START PROOFREADER COMMENTS -->
	<tr valign="top">
		<td>2.</td>
		<td>
			{translate key="editor.article.proofreaderComments"}
		</td>
		<td>{if $proofAssignment->getDateProofreaderNotified()}{$proofAssignment->getDateProofreaderNotified()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
		<td>{if $proofAssignment->getDateProofreaderUnderway()}{$proofAssignment->getDateProofreaderUnderway()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
		<td>{if $proofAssignment->getDateProofreaderCompleted()}{$proofAssignment->getDateProofreaderCompleted()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
	</tr>
<!-- END PROOFREADER COMMENTS -->
	<tr><td colspan="5" class="separator"></td></tr>
<!-- START LAYOUT EDITOR FINAL -->
	<tr valign="top">
		<td>3.</td>
		<td>
			{translate key="editor.article.layoutEditorFinal"}
		</td>
		<td>{if $proofAssignment->getDateLayoutEditorNotified()}{$proofAssignment->getDateLayoutEditorNotified()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
		<td>{if $proofAssignment->getDateLayoutEditorUnderway()}{$proofAssignment->getDateLayoutEditorUnderway()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
		<td>{if $proofAssignment->getDateLayoutEditorCompleted()}{$proofAssignment->getDateLayoutEditorCompleted()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
	</tr>
</table>

{translate key="submission.proofread.corrections"}
{if $submission->getMostRecentProofreadComment()}
        {assign var="comment" value=$submission->getMostRecentProofreadComment()}
        <a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}#{$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
        <a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>
{/if}

<div class="separator"></div>

<!-- END LAYOUT EDITOR FINAL -->
<!-- END OF PROOFREADING -->


