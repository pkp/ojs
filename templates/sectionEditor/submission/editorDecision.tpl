{**
 * editorDecision.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the editor decision table.
 *
 * $Id$
 *}
<div id="editorDecision">
{* 2011-11-09 BLH Custom message for eSchol journal WestJEM only *}
{* 2012-05-08 LS Custom page for eSchol journal WestJEM only *}
{* 2012-08-31 LS Adding in similar customizations for Frontiers of Biogeography*}
{if (($journalPath=='uciem_westjem' || $journalPath=='fb' ) && ($isEditor != 1))}
	<h3>{translate key="submission.submissionEditorDecision"}</h3>
	{else}
	<h3>{translate key="submission.editorDecision"}</h3>
{/if}

<table id="table1" width="100%" class="data">
{if (($journalPath=='uciem_westjem') &&  ($isEditor != 1))}
  <tr>
  	<td valign="middle">{translate key="submission.submissionEditorInstructionsWestJEM"}</td>
  </tr>
  <tr>
  	<td valign="middle">
	<ol>
		<li>Click the "Add New Note" link on the <a href="{url op="submissionNotes" path=$submission->getArticleId()}">{translate key="submission.history.submissionNotes"}</a> page and submit your decision.</li>
		<li><strong>Email Rex Chang at <a href="mailto:editor@westjem.org">editor@westjem.org</a></strong> to confirm that your comments have been received.</li>
	 </ol>
	</td>
  </tr>
  <tr>
   	<td valign="middle">{translate key="submission.submissionEditorInstructionsWestJEMclose"}</td>
</tr>
{elseif (($journalPath=='fb') &&  ($isEditor != 1))}
	<tr>
		<td valign="middle">{translate key="submission.submissionEditorInstructionsWestJEM"}</td>
	</tr>
	<tr>
		<td valign="middle">
			<ol>
				<li>Click the "Add New Note" link on the <a href="{url op="submissionNotes" path=$submission->getArticleId()}">{translate key="submission.history.submissionNotes"}</a> page and submit your decision.</li>	
					<li>Email the {$journalContact} to confirm that your comments have been received
						{assign var=emailString value=$journalEmail}
						{assign var=emailSubject value="Article "|cat:$submission->getArticleId()|cat:"--Comments from Section Editor"}
						{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$journalEmail|to_array subject=$emailSubject}
						{icon name="mail" url=$url}
 </li>					
					
			</ol>
		</td>
	</tr>
	<tr>
		<td valign="middle">{translate key="submission.submissionEditorInstructionsWestJEMclose"}</td>
	</tr>	
{else}
   <tr>
	<td class="label" width="20%" valign="middle">{translate key="editor.article.selectDecision"}</td>
	<td width="80%" class="value" valign="top">
		<form method="post" action="{url op="recordDecision"}">
			<input type="hidden" name="articleId" value="{$submission->getId()}" />
			<select name="decision" size="1" class="selectMenu"{if not $allowRecommendation} disabled="disabled"{/if}>
				{html_options_translate options=$editorDecisionOptions selected=$lastDecision}
			</select>
			<input type="submit" onclick="return confirm('{translate|escape:"jsparam" key="editor.submissionReview.confirmDecision"}')" name="submit" value="{translate key="editor.article.recordDecision"}" {if not $allowRecommendation}disabled="disabled"{/if} class="button" />
			{if not $allowRecommendation}&nbsp;&nbsp;{translate key="editor.article.cannotRecord"}{/if}
		</form>
	</td>
</tr>

<tr valign="top">
	<td class="label">{translate key="editor.article.decision"}</td>
	<td class="value">
		{foreach from=$submission->getDecisions($round) item=editorDecision key=decisionKey}
			{if $decisionKey neq 0} <br /> {/if}
			{assign var="decision" value=$editorDecision.decision}
			{$editorDecision.dateDecided|date_format:$dateFormatShort}&nbsp;&nbsp;{translate key=$editorDecisionOptions.$decision}
		{foreachelse}
			{translate key="common.none"}
		{/foreach}
	</td>
</tr>

<tr valign="top">
	<td class="label">{translate key="submission.notifyAuthor"}</td>
	<td class="value">
		{url|assign:"notifyAuthorUrl" op="emailEditorDecisionComment" articleId=$submission->getId()}

		{if $decision == SUBMISSION_EDITOR_DECISION_DECLINE}
			{* The last decision was a decline; notify the user that sending this message will archive the submission. *}
			{translate|escape:"quotes"|assign:"confirmString" key="editor.submissionReview.emailWillArchive"}
			{icon name="mail" url=$notifyAuthorUrl onclick="return confirm('$confirmString')"}
		{else}
			{icon name="mail" url=$notifyAuthorUrl}
		{/if}
		&nbsp;{translate key="submission.notifyAuthorSendEmail"}
	<td>
</tr>
	
<tr valign="top">
	<td class="label">{translate key="submission.editorAuthorRecord"}</td>
	<td class="value">
		{if $submission->getMostRecentEditorDecisionComment()}
			{assign var="comment" value=$submission->getMostRecentEditorDecisionComment()}
			<a href="javascript:openComments('{url op="viewEditorDecisionComments" path=$submission->getId() anchor=$comment->getId()}');" class="icon">{icon name="comment"}</a>
			&nbsp;{$comment->getDatePosted()|date_format:$dateFormatShort}&nbsp;{translate key="submission.lastEditorAuthorEmail"}
		{else}
			<a href="javascript:openComments('{url op="viewEditorDecisionComments" path=$submission->getId()}');" class="icon">{icon name="comment"}</a>
			&nbsp;{translate key="submission.noEditorAuthorEmails"}
		{/if}
	</td>
</tr>
{/if}
</table>


<form method="post" action="{url op="editorReview"}" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$submission->getId()}" />
{assign var=authorFiles value=$submission->getAuthorFileRevisions($round)}
{assign var=editorFiles value=$submission->getEditorFileRevisions($round)}

{assign var="authorRevisionExists" value=false}
{foreach from=$authorFiles item=authorFile}
	{assign var="authorRevisionExists" value=true}
{/foreach}

{assign var="editorRevisionExists" value=false}
{foreach from=$editorFiles item=editorFile}
	{assign var="editorRevisionExists" value=true}
{/foreach}
{if $reviewFile}
	{assign var="reviewVersionExists" value=1}
{/if}
<hr style="width:75%" align="left" />
{if $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT}
	<p>{translate key="submission.selectVersionForCopyediting"}</p>
{elseif $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}
	<p>{translate key="submission.selectVersionForResubmit"}</p>
{else}
	<p>{translate key="submission.currentArticleVersions"}</p>
{/if}
<table id="table2" class="data" width="100%">
	{if $reviewFile}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.reviewVersion"}</td>
			<td width="50%" class="value">
				{if $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT || $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}
					<input type="radio" name="editorDecisionFile" value="{$reviewFile->getFileId()},{$reviewFile->getRevision()}" checked /> {** BLH 20110822 added 'checked' to prevent empty value **}
				{/if}
				<a href="{url op="downloadFile" path=$submission->getId()|to_array:$reviewFile->getFileId():$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()|escape}</a>&nbsp;&nbsp;
				{$reviewFile->getDateModified()|date_format:$dateFormatShort}
				{if $copyeditFile && $copyeditFile->getSourceFileId() == $reviewFile->getFileId()}
					&nbsp;&nbsp;&nbsp;&nbsp;{translate key="submission.sentToCopyediting"}&nbsp;&nbsp;{$copyeditFile->getDateUploaded()|date_format:$dateFormatShort}
				{/if}
			</td>
		</tr>
	{/if}
	{assign var="firstItem" value=true}
	{foreach from=$authorFiles item=authorFile key=key}
		<tr valign="top">
			{if $firstItem}
				{assign var="firstItem" value=false}
				<td width="20%" rowspan="{$authorFiles|@count}" class="label">{translate key="submission.authorVersion"}</td>
			{/if}
			<td width="80%" class="value">
				{if $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT || $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}<input type="radio" name="editorDecisionFile" value="{$authorFile->getFileId()},{$authorFile->getRevision()}" /> {/if}<a href="{url op="downloadFile" path=$submission->getId()|to_array:$authorFile->getFileId():$authorFile->getRevision()}" class="file">{$authorFile->getFileName()|escape}</a>&nbsp;&nbsp;
				{$authorFile->getDateModified()|date_format:$dateFormatShort}
				{if $copyeditFile && $copyeditFile->getSourceFileId() == $authorFile->getFileId() && $copyeditFile->getSourceRevision() == $editorFile->getRevision()}
					&nbsp;&nbsp;&nbsp;&nbsp;{translate key="submission.sentToCopyediting"}&nbsp;&nbsp;{$copyeditFile->getDateUploaded()|date_format:$dateFormatShort}
				{/if}
			</td>
		</tr>
{** If there are no author versions, display nothing. Decluttering UI. **}
{**
	{foreachelse}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.authorVersion"}</td>
			<td width="80%" class="nodata">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{translate key="common.none"}</td>
		</tr>
**} 
	{/foreach}
	{assign var="firstItem" value=true}
	{foreach from=$editorFiles item=editorFile key=key}
		<tr valign="top">
			{if $firstItem}
				{assign var="firstItem" value=false}
				<td width="20%" rowspan="{$editorFiles|@count}" class="label">{translate key="submission.editorVersion"}</td>
			{/if}
			<td width="80%" class="value">
				{if $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT || $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}<input type="radio" name="editorDecisionFile" value="{$editorFile->getFileId()},{$editorFile->getRevision()}" /> {/if}<a href="{url op="downloadFile" path=$submission->getId()|to_array:$editorFile->getFileId():$editorFile->getRevision()}" class="file">{$editorFile->getFileName()|escape}</a>&nbsp;&nbsp;
				{$editorFile->getDateModified()|date_format:$dateFormatShort}&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="{url op="deleteArticleFile" path=$submission->getId()|to_array:$editorFile->getFileId():$editorFile->getRevision()}" class="action">{translate key="common.delete"}</a>
				{if $copyeditFile && $copyeditFile->getSourceFileId() == $editorFile->getFileId() && $copyeditFile->getSourceRevision() == $editorFile->getRevision()}
					&nbsp;&nbsp;&nbsp;&nbsp;{translate key="submission.sentToCopyediting"}&nbsp;&nbsp;{$copyeditFile->getDateUploaded()|date_format:$dateFormatShort}
				{/if}
			</td>
		</tr>
	{foreachelse}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.editorVersion"}</td>
			<td width="80%" class="nodata">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{translate key="common.none"}</td>
		</tr>
	{/foreach}
	<tr valign="middle">
		<td class="label">&nbsp;</td>
		<td class="value">
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{translate key="submission.UploadNewEditorVersion"}
			<input type="file" name="upload" class="uploadField" />
			<input type="submit" name="submit" value="{translate key="common.upload"}" class="button" />
		</td>
	</tr>
	{if $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}
		<tr>
			<td width="20%">&nbsp;</td>
			<td width="80%">
				<input type="submit" name="resubmit" {if !($editorRevisionExists or $authorRevisionExists or $reviewVersionExists)}disabled="disabled" {/if}value="{translate key="form.resubmit"}" class="defaultButton" />
				{**{translate key="editor.article.resubmitFileForPeerReview"}**}
			</td>
		</tr>
	{/if}
	{if $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT}
	<tr valign="top">
		<td width="20%">&nbsp;</td>
		<td width="80%">
			{if !($editorRevisionExists or $authorRevisionExists or $reviewVersionExists) or !$submission->getMostRecentEditorDecisionComment()}{assign var=copyeditingUnavailable value=1}{else}{assign var=copyeditingUnavailable value=0}{/if}
			<input type="submit" {if $copyeditingUnavailable}disabled="disabled" {/if}name="setCopyeditFile" value="{translate key="editor.submissionReview.sendToCopyediting"}" {if $copyeditingUnavailable}class="button"{else}class="defaultButton"{/if} />
			{if $copyeditingUnavailable}
				<span class="instruct">{translate key="editor.submissionReview.cannotSendToCopyediting"}</span>
			{/if}
		</td>
	</tr>
	{/if}
</table>

</form>
</div>

