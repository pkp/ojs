{**
 * proofread.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the proofreading table.
 *
 * $Id$
 *}

<a name="proofreading"></a>
<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.proofreading"}</td>
</tr>
<tr class="submissionRowAlt">
	<td class="submissionBox">
		<table class="plainFormat" width="100%">
			<tr>
				<td width="40%">
					{if $useProofreaders}
						{if $proofAssignment->getProofreaderId()}
							<span class="boldText">{translate key="user.role.proofreader"}:</span> {$proofAssignment->getProofreaderFullName()}
						{else}
							<form method="post" action="{$requestPageUrl}/selectProofreader/{$submission->getArticleId()}">
								<input type="submit" value="{translate key="editor.article.selectProofreader"}">
							</form>
						{/if}
					{/if}
				</td>
				<td width="70%">
					{if $useProofreaders}
						{if $proofAssignment->getProofreaderId()}
							<form method="post" action="{$requestPageUrl}/replaceProofreader/{$proofAssignment->getArticleId()}/{$proofAssignment->getProofreaderId()}">
								<input type="submit" value="{translate key="editor.article.replaceProofreader"}">
							</form>
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
			<td width="40%"><span class="boldText">1. {translate key="editor.article.authorComments"}</td>
			<td align="center" width="15%">
				<form method="post" action="{$requestPageUrl}/notifyAuthorProofreader">
					<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
					<input type="submit" value="{translate key="submission.request"}" {if $proofAssignment->getDateAuthorCompleted()}disabled="disabled"{/if}>
				</form>
			</td>
			<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
			<td align="center" width="15%"><strong>{translate key="submission.complete"}</strong></td>
			<td align="center" width="15%">
				<form method="post" action="{$requestPageUrl}/thankAuthorProofreader">
					<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
					<input type="submit" value="{translate key="submission.thank"}" {if not $proofAssignment->getDateAuthorNotified() or $proofAssignment->getDateAuthorAcknowledged()}disabled="disabled"{/if}>
				</form>
			</td>
		</tr>
		<tr>
			<td width="40%">&nbsp;</td>
			<td align="center" width="15%">{if $proofAssignment->getDateAuthorNotified()}{$proofAssignment->getDateAuthorNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
			<td align="center" width="15%">{if $proofAssignment->getDateAuthorUnderway()}{$proofAssignment->getDateAuthorUnderway()|date_format:$dateFormatShort}{else}-{/if}</td>
			<td align="center" width="15%">{if $proofAssignment->getDateAuthorCompleted()}{$proofAssignment->getDateAuthorCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
			<td align="center" width="15%">{if $proofAssignment->getDateAuthorAcknowledged()}{$proofAssignment->getDateAuthorAcknowledged()|date_format:$dateFormatShort}{else}-{/if}</td>
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
				<td width="40%"><span class="boldText">2. {translate key="editor.article.proofreaderComments"}</span></td>
				<td align="center" width="15%">
					{if $useProofreaders}
						<form method="post" action="{$requestPageUrl}/notifyProofreader">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="submission.request"}" {if not $proofAssignment->getProofreaderId() or not $proofAssignment->getDateAuthorCompleted() or $proofAssignment->getDateProofreaderCompleted()}disabled="disabled"{/if}>
						</form>
					{else}
						<form method="post" action="{$requestPageUrl}/editorInitiateProofreader">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="editor.article.initiate"}" {if not $proofAssignment->getDateAuthorCompleted() or $proofAssignment->getDateProofreaderCompleted()}disabled="disabled"{/if}>
						</form>
					{/if}
				</td>
				<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
				<td align="center" width="15%">
					{if $useProofreaders}
						<strong>{translate key="submission.complete"}</strong>
					{else}
						<form method="post" action="{$requestPageUrl}/editorCompleteProofreader">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="submission.complete"}" {if not $proofAssignment->getDateProofreaderNotified() or $proofAssignment->getDateProofreaderCompleted()}disabled="disabled"{/if}>
						</form>						
					{/if}
				</td>
				<td align="center" width="15%">
					<form method="post" action="{$requestPageUrl}/thankProofreader">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<input type="submit" value="{translate key="submission.thank"}" {if not $proofAssignment->getProofreaderId() or not $useProofreaders or not $proofAssignment->getDateProofreaderNotified() or $proofAssignment->getDateProofreaderAcknowledged()}disabled="disabled"{/if}>
					</form>
				</td>
			</tr>
			<tr>
				<td width="40%">&nbsp;</td>
				<td align="center" width="15%">{if $proofAssignment->getDateProofreaderNotified()}{$proofAssignment->getDateProofreaderNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">
					{if $useProofreaders}
						{if $proofAssignment->getDateProofreaderUnderway()}{$proofAssignment->getDateProofreaderUnderway()|date_format:$dateFormatShort}{else}-{/if}
					{else}
						{translate key="common.notApplicableShort"}
					{/if}
				</td>
				<td align="center" width="15%">{if $proofAssignment->getDateProofreaderCompleted()}{$proofAssignment->getDateProofreaderCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">
					{if $useProofreaders}
						{if $proofAssignment->getDateProofreaderAcknowledged()}{$proofAssignment->getDateProofreaderAcknowledged()|date_format:$dateFormatShort}{else}-{/if}
					{else}
						{translate key="common.notApplicableShort"}
					{/if}
				</td>
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
			<td width="40%"><span class="boldText">3. {translate key="editor.article.layoutEditorFinal"}</td>
			<td align="center" width="15%">
				{if $useLayoutEditors}
					<form method="post" action="{$requestPageUrl}/notifyLayoutEditorProofreader">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<input type="submit" value="{translate key="submission.request"}" {if not $layoutAssignment->getEditorId() or not $proofAssignment->getDateProofreaderCompleted()}disabled="disabled"{/if}>
					</form>
				{else}
					<form method="post" action="{$requestPageUrl}/editorInitiateLayoutEditor">
						<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
						<input type="submit" value="{translate key="editor.article.initiate"}" {if not $proofAssignment->getDateProofreaderCompleted()}disabled="disabled"{/if}>
					</form>
				{/if}
			</td>
			<td align="center" width="15%"><strong>{translate key="submission.underway"}</strong></td>
			<td align="center" width="15%">
					{if $useLayoutEditors}
						<strong>{translate key="submission.complete"}</strong>
					{else}
						<form method="post" action="{$requestPageUrl}/editorCompleteLayoutEditor">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="submission.complete"}" {if not $proofAssignment->getDateLayoutEditorNotified() or $proofAssignment->getDateLayoutEditorCompleted()}disabled="disabled"{/if}>
						</form>						
					{/if}			
			</td>
			<td align="center" width="15%">
				<form method="post" action="{$requestPageUrl}/thankLayoutEditorProofreader">
					<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
					<input type="submit" value="{translate key="submission.thank"}" {if not $layoutAssignment->getEditorId() or not $useLayoutEditors or not $proofAssignment->getDateLayoutEditorNotified() or $proofAssignment->getDateLayoutEditorAcknowledged()}disabled="disabled"{/if}>
				</form>
			</td>
		</tr>
			<tr>
				<td width="40%">&nbsp;</td>
				<td align="center" width="15%">{if $proofAssignment->getDateLayoutEditorNotified()}{$proofAssignment->getDateLayoutEditorNotified()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">
					{if $useLayoutEditors}
						{if $proofAssignment->getDateLayoutEditorUnderway()}{$proofAssignment->getDateLayoutEditorUnderway()|date_format:$dateFormatShort}{else}-{/if}
					{else}
						{translate key="common.notApplicableShort"}
					{/if}
				</td>
				<td align="center" width="15%">{if $proofAssignment->getDateLayoutEditorCompleted()}{$proofAssignment->getDateLayoutEditorCompleted()|date_format:$dateFormatShort}{else}-{/if}</td>
				<td align="center" width="15%">
					{if $useLayoutEditors}
						{if $proofAssignment->getDateLayoutEditorAcknowledged()}{$proofAssignment->getDateLayoutEditorAcknowledged()|date_format:$dateFormatShort}{else}-{/if}			
					{else}
						{translate key="common.notApplicableShort"}
					{/if}
				</td>
			</tr>
		</table>
	</td>
</tr>
<!-- END LAYOUT EDITOR FINAL -->
<tr class="submissionDivider">
	<td></td>
</tr>
<tr class="submissionRow">
	<td class="submissionBox">
		<form method="post" action="{$requestPageUrl}/queueForScheduling/{$submission->getArticleId()}">
			<input type="submit" value="{translate key="editor.article.placeSubmissionInSchedulingQueue"}">{if $proofAssignment->getDateSchedulingQueue()}&nbsp;({$proofAssignment->getDateSchedulingQueue()|date_format:$dateFormatShort}){else}&nbsp;{translate key="editor.article.noDate"}{/if}
		</form>
	</td>
</tr>
</table>
</div>
