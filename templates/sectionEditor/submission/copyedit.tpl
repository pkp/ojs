{**
 * copyedit.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the copyediting table.
 *
 * $Id$
 *}

<a name="copyedit"></a>
<h3>{translate key="submission.copyediting"}</h3>

{if $useCopyeditors}
<p>{translate key="user.role.copyeditor"}:
{if $submission->getCopyeditorId()}&nbsp; {$copyeditor->getFullName()}{/if}
&nbsp; <a href="{$requestPageUrl}/selectCopyeditor/{$submission->getArticleId()}" class="action">{translate key="editor.article.selectCopyeditor"}</a></p>
{/if}

<table width="100%" class="info">
	<tr>
		<td width="28%" colspan="2"><a href="{$requestPageUrl}/viewMetadata/{$submission->getArticleId()}" class="action">{translate key="submission.reviewMetadata"}</a></td>
		<td width="18%" class="heading">{translate key="submission.request"}</td>
		<td width="18%" class="heading">{translate key="submission.underway"}</td>
		<td width="18%" class="heading">{translate key="submission.complete"}</td>
		<td width="18%" class="heading">{translate key="submission.acknowledge"}</td>
	</tr>
	<tr>
		<td width="5%">1.</td>
		<td width="23%">{translate key="submission.copyedit.initialCopyedit"}</td>
		<td>
			{if $useCopyeditors}
				{if $submission->getCopyeditorId() && $initialCopyeditFile}
					{if $submission->getCopyeditorDateUnderway()}
						{assign_translate|escape:"javascript" var=confirmText key="sectionEditor.copyedit.confirmRenotify"}
						{icon name="mail" onClick="return confirm('$confirmText')" url="$requestPageUrl/notifyCopyeditor?articleId=`$submission->getArticleId()`"}
					{else}
						{icon name="mail" url="$requestPageUrl/notifyCopyeditor?articleId=`$submission->getArticleId()`"}
					{/if}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
			{else}
				{if !$submission->getCopyeditorDateNotified() && $initialCopyeditFile}
					<a href="{$requestPageUrl}/initiateCopyedit?articleId={$submission->getArticleId()}" class="action">{translate key="common.initiate"}</a>
				{/if}
			{/if}
			{$submission->getCopyeditorDateNotified()|date_format:$dateFormatShort|default:""}
		</td>
		<td>
			{if $useCopyeditors}
				{$submission->getCopyeditorDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
		<td>
			{if $submission->getCopyeditorDateCompleted()}
				{$submission->getCopyeditorDateCompleted()|date_format:$dateFormatShort}
			{elseif !$useCopyeditors}
				<a href="{$requestPageUrl}/completeCopyedit?articleId={$submission->getArticleId()}" class="action">{translate key="common.complete"}</a>
			{else}
				&mdash;
			{/if}
		</td>
		<td>
			{if $useCopyeditors}
				{if $submission->getCopyeditorId() &&  $submission->getCopyeditorDateNotified() && !$submission->getCopyeditorDateAcknowledged()}
					{icon name="mail" url="$requestPageUrl/thankCopyeditor?articleId=`$submission->getArticleId()`"}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
				{$submission->getCopyeditorDateAcknowledged()|date_format:$dateFormatShort|default:""}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="5">
			{translate key="common.file"}:
			{if $initialCopyeditFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$initialCopyeditFile->getFileId()}/{$initialCopyeditFile->getRevision()}" class="file">{$initialCopyeditFile->getFileName()}</a> {$initialCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="6" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td>2.</td>
		<td width="20%">{translate key="submission.copyedit.editorAuthorReview"}</td>
		<td>
			{if ($submission->getCopyeditorId() || !$useCopyeditors) && $submission->getCopyeditorDateCompleted()}
				{if $submission->getCopyeditorDateAuthorUnderway()}
					{assign_translate|escape:"javascript" var=confirmText key="sectionEditor.author.confirmRenotify"}
					{icon name="mail" onClick="return confirm('$confirmText')" url="$requestPageUrl/notifyAuthorCopyedit?articleId=`$submission->getArticleId()`"}
				{else}
					{icon name="mail" url="$requestPageUrl/notifyAuthorCopyedit?articleId=`$submission->getArticleId()`"}
				{/if}
			{else}
				{icon name="mail" disabled="disable"}
			{/if}
			{$submission->getCopyeditorDateAuthorNotified()|date_format:$dateFormatShort|default:""}
		</td>
		<td>
				{$submission->getCopyeditorDateAuthorUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td>
				{$submission->getCopyeditorDateAuthorCompleted()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td>
			{if ($submission->getCopyeditorId() || !$useCopyeditors) && $submission->getCopyeditorDateAuthorNotified() && !$submission->getCopyeditorDateAuthorAcknowledged()}
				{icon name="mail" url="$requestPageUrl/thankAuthorCopyedit?articleId=`$submission->getArticleId()`"}
			{else}
				{icon name="mail" disabled="disable"}
			{/if}
			{$submission->getCopyeditorDateAuthorAcknowledged()|date_format:$dateFormatShort|default:""}
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="5">
			{translate key="common.file"}:
			{if $editorAuthorCopyeditFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$editorAuthorCopyeditFile->getFileId()}/{$editorAuthorCopyeditFile->getRevision()}" class="file">{$editorAuthorCopyeditFile->getFileName()}</a> {$editorAuthorCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="6" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td>3.</td>
		<td width="20%">{translate key="submission.copyedit.finalCopyedit"}</td>
		<td>
			{if $useCopyeditors}
				{if $submission->getCopyeditorId() && $submission->getCopyeditorDateAuthorCompleted()}
					{if $submission->getCopyeditorDateFinalUnderway()}
						{assign_translate|escape:"javascript" var=confirmText key="sectionEditor.copyedit.confirmRenotify"}
						{icon name="mail" onClick="return confirm('$confirmText')" url="$requestPageUrl/notifyFinalCopyedit?articleId=`$submission->getArticleId()`"}
					{else}
						{icon name="mail" url="$requestPageUrl/notifyFinalCopyedit?articleId=`$submission->getArticleId()`"}
					{/if}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
			{/if}
			{$submission->getCopyeditorDateFinalNotified()|date_format:$dateFormatShort|default:""}
		</td>
		<td>
			{if $useCopyeditors}
				{$submission->getCopyeditorDateFinalUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
		<td>
			{if $submission->getCopyeditorDateFinalCompleted()}
				{$submission->getCopyeditorDateFinalCompleted()|date_format:$dateFormatShort}
			{elseif !$useCopyeditors}
				<a href="{$requestPageUrl}/completeFinalCopyedit?articleId={$submission->getArticleId()}" class="action">{translate key="common.complete"}</a>
			{else}
				&mdash;
			{/if}
		</td>
		<td>
			{if $useCopyeditors}
				{if $submission->getCopyeditorId() &&  $submission->getCopyeditorDateFinalNotified() && !$submission->getCopyeditorDateFinalAcknowledged()}
					{icon name="mail" url="$requestPageUrl/thankFinalCopyedit?articleId=`$submission->getArticleId()`"}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
				{$submission->getCopyeditorDateFinalAcknowledged()|date_format:$dateFormatShort|default:""}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="5">
			{translate key="common.file"}:
			{if $finalCopyeditFile}
				<a href="{$requestPageUrl}/downloadFile/{$submission->getArticleId()}/{$finalCopyeditFile->getFileId()}/{$finalCopyeditFile->getRevision()}" class="file">{$finalCopyeditFile->getFileName()}</a> {$finalCopyeditFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="6" class="separator">&nbsp;</td>
	</tr>
</table>

{if $submission->getCopyeditorDateAuthorCompleted() && !$submission->getCopyeditorDateFinalCompleted()}
{assign var="canUploadCopyedit" value="3"}
{elseif $submission->getCopyeditorDateCompleted() && !$submission->getCopyeditorDateAuthorCompleted()}
{assign var="canUploadCopyedit" value="2"}
{elseif !$submission->getCopyeditorDateCompleted()}
{assign var="canUploadCopyedit" value="1"}
{/if}
<form method="post" action="{$requestPageUrl}/uploadCopyeditVersion"  enctype="multipart/form-data">
	<input type="hidden" name="articleId" value="{$submission->getArticleId()}" />
	{translate key="submission.uploadFileTo"} <input type="radio" name="copyeditStage" id="copyeditStageInitial" value="initial" {if $canUploadCopyedit != 1} disabled="disabled"{else} checked="checked"{/if} /><label for="copyeditStageInitial"{if $canUploadCopyedit != 1} class="disabled"{/if}>{translate key="navigation.stepNumber" step=1}</label>, <input type="radio" name="copyeditStage" id="copyeditStageAuthor" value="author"{if $canUploadCopyedit != 2} disabled="disabled"{else} checked="checked"{/if} /><label for="copyeditStageAuthor"{if $canUploadCopyedit != 2} class="disabled"{/if}>{translate key="navigation.stepNumber" step=2}</label>, {translate key="common.or"} <input type="radio" name="copyeditStage" id="copyeditStageFinal" value="final"{if $canUploadCopyedit != 3} disabled="disabled"{else} checked="checked"{/if} /><label for="copyeditStageFinal"{if $canUploadCopyedit != 3} class="disabled"{/if}>{translate key="navigation.stepNumber" step=3}</label>
	<input type="file" name="upload" size="10" class="uploadField"{if !$canUploadCopyedit} disabled="disabled"{/if} />
	<input type="submit" value="{translate key="common.upload"}" class="button"{if !$canUploadCopyedit} disabled="disabled"{/if} />
</form>

<p>
{translate key="submission.copyedit.copyeditComments"}
{if $submission->getMostRecentCopyeditComment()}
	{assign var="comment" value=$submission->getMostRecentCopyeditComment()}
	<a href="javascript:openComments('{$requestPageUrl}/viewCopyeditComments/{$submission->getArticleId()}#{$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	<a href="javascript:openComments('{$requestPageUrl}/viewCopyeditComments/{$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>
{/if}

{if $currentJournal->getSetting('copyeditInstructions')}
&nbsp;&nbsp;
<a href="javascript:openHelp('{$requestPageUrl}/instructions/copy')" class="action">{translate key="submission.copyedit.instructions"}</a>
{/if}
</p>
