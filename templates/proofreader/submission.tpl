{**
 * submission.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of a submission.
 *
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

<div class="tableContainer">
<table width="100%">
<tr class="submissionRow">
	<td class="submissionBox">
		<div class="leftAligned">
			<div>{foreach from=$authors item=author key=authorKey}{if $authorKey neq 0},{/if} {$author->getFullName()}{/foreach}</div>
			<div class="submissionTitle">{$submission->getTitle()}</div>
		</div>
		<div class="submissionId">{$submission->getArticleId()}</div>
	</td>
</tr>
</table>
</div>

<br />

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.proofreading"}</td>
</tr>
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td>
					{if $useProofreaders}
						{if $proofAssignment->getProofreaderId()}
							<span class="boldText">{translate key="user.role.proofreader"}:</span> {$proofAssignment->getProofreaderFullName()}
						{else}
							<span class="boldText">{translate key="user.role.proofreader"}:</span> {translate key="common.none"}
						{/if}
					{/if}
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr class="submissionDivider">
	<td></td>
</tr>
<!-- START AUTHOR COMMENTS -->
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
		<tr>
			<td width="55%">
				<span class="boldText">1. {translate key="editor.article.authorComments"}</span>
				{if $submission->getMostRecentProofreadComment()}
					{assign var="comment" value=$submission->getMostRecentProofreadComment()}
					<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
				{else}
					<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
				{/if}	
			</td>
			<td align="center" width="15%"><strong>{translate key="submission.request"}</strong></td>
			<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
			<td align="center" width="15%"><strong>{translate key="submission.complete"}</strong></td>
		</tr>
		<tr>
			<td width="55%">&nbsp;</td>
			<td align="center" width="15%">{if $proofAssignment->getDateAuthorNotified()}{$proofAssignment->getDateAuthorNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
			<td align="center" width="15%">{if $proofAssignment->getDateAuthorUnderway()}{$proofAssignment->getDateAuthorUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
			<td align="center" width="15%">{if $proofAssignment->getDateAuthorCompleted()}{$proofAssignment->getDateAuthorCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
		</tr>
		</table>
	</td>
</tr>
<!-- END AUTHOR COMMENTS -->
<!-- START PROOFREADER COMMENTS -->
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="55%">
					<span class="boldText">2. {translate key="editor.article.proofreaderComments"}</span>
					{if $submission->getMostRecentProofreadComment()}
						{assign var="comment" value=$submission->getMostRecentProofreadComment()}
						<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
					{else}
						<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
					{/if}
				</td>
				<td align="center" width="15%"><strong>{translate key="submission.request"}</strong></td>
				<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
				<td align="center" width="15%">
					<form method="post" action="{$requestPageUrl}/completeProofreader">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<input type="submit" value="{translate key="submission.complete"}" {if not $proofAssignment->getDateProofreaderNotified() or not $useProofreaders or $proofAssignment->getDateProofreaderCompleted()}disabled="disabled"{/if}>
					</form>						
				</td>
			</tr>
			<tr>
				<td width="55%">&nbsp;</td>
				<td align="center" width="15%">{if $proofAssignment->getDateProofreaderNotified()}{$proofAssignment->getDateProofreaderNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $proofAssignment->getDateProofreaderUnderway()}{$proofAssignment->getDateProofreaderUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $proofAssignment->getDateProofreaderCompleted()}{$proofAssignment->getDateProofreaderCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
			</tr>
		</table>
	</td>
</tr>
<!-- END PROOFREADER COMMENTS -->
<!-- START LAYOUT EDITOR FINAL -->
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
		<tr>
			<td width="55%">
				<span class="boldText">3. {translate key="editor.article.layoutEditorFinal"}</span>
				{if $submission->getMostRecentProofreadComment()}
					{assign var="comment" value=$submission->getMostRecentProofreadComment()}
					<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}#{$comment->getCommentId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>{$comment->getDatePosted()|date_format:$dateFormatShort}
				{else}
					<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}');"><img src="{$baseUrl}/templates/images/letter.gif" border="0" /></a>
				{/if}	
			</td>
			<td align="center" width="15%"><strong>{translate key="submission.request"}</strong></td>
			<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
			<td align="center" width="15%"><strong>{translate key="submission.complete"}</strong></td>
		</tr>
			<tr>
				<td width="55%">&nbsp;</td>
				<td align="center" width="15%">{if $proofAssignment->getDateLayoutEditorNotified()}{$proofAssignment->getDateLayoutEditorNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $proofAssignment->getDateLayoutEditorUnderway()}{$proofAssignment->getDateLayoutEditorUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">{if $proofAssignment->getDateLayoutEditorCompleted()}{$proofAssignment->getDateLayoutEditorCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
			</tr>
		</table>
	</td>
</tr>
<!-- END LAYOUT EDITOR FINAL -->
</table>
</div>

{include file="common/footer.tpl"}
