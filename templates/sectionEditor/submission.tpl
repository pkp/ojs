{**
 * submission.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of a submission.
 *
 * FIXME: Editor decision values need to be localized.
 * DO: Reviewer comments need to be implemented.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$requestPageUrl}/summary/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}" class="active">{translate key="submission.submission"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}" >{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.submissionEditing"}</a></li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}">{translate key="submission.submissionHistory"}</a></li>
</ul>
<ul id="subnav">
</ul>

<div class="tableContainer">
<table width="100%">
<tr class="submissionRow">
	<td class="submissionBox">
		<div class="leftAligned">
			<div>{foreach from=$submission->getAuthors() item=author key=authorKey}{if $authorKey neq 0},{/if} {$author->getFullName()}{/foreach}</div>
			<div class="submissionTitle">{$submission->getArticleTitle()}</div>
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
	<td>{translate key="submission.files"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
			<tr>
				<td valign="top">
					{translate key="editor.article.originalFile"}:
					{if $submissionFile}
						<a href="{$requestPageUrl}/downloadFile/{$submissionFile->getFileId()}">{$submissionFile->getFileName()}</a> {$submissionFile->getDateModified()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}
					{/if}
				</td>
			</tr>
			<tr>
				<td valign="top">
					<table class="plainFormat">
						<tr>
							<td valign="top">{translate key="article.suppFiles"}:</td>
							<td valign="top">
								{foreach from=$suppFiles item=suppFile}
									<a href="{$requestPageUrl}/downloadFile/{$suppFile->getFileId()}">{$suppFile->getTitle()}</a><br />
								{foreachelse}
									{translate key="common.none"}
								{/foreach}
								<form method="post" action="{$requestPageUrl}/addSuppFile/{$submission->getArticleId()}">
									<input type="submit" value="{translate key="submission.addSuppFile"}">
								</form>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>
</div>

<br />

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.submissionManagement"}</td>
</tr>
<tr>
	<td>
		<table class="plain">
			<tr>
				<td colspan="2">
					{if $submission->getStatus()}
						<form method="post" action="{$requestPageUrl}/archiveSubmission">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="editor.article.archiveSubmission"}">
						</form>
					{else}
						<form method="post" action="{$requestPageUrl}/restoreToQueue">
							<input type="hidden" name="articleId" value="{$submission->getArticleId()}">
							<input type="submit" value="{translate key="editor.article.restoreToQueue"}">
						</form>
					{/if}
				</td>
			</tr>
			{if $isEditor}
			<tr>
				<td valign="top">
					{translate key="editor.article.editor"}:
				</td>
				<td>
					{foreach from=$submission->getReplacedEditors() item=replacedEditor}
						<span class="disabledText">{$replacedEditor->getEditorFullName()}</span><br />
					{/foreach}
					{if $editor}
						{$editor->getEditorFullName()}<br />
					{/if}
				</td>
			</tr>
			<tr>
				<td></td>
				<td>
					<form method="post" action="{$pageUrl}/editor/assignEditor/{$submission->getArticleId()}">
						<input type="submit" value="{translate key="editor.article.assignEditor"}">
					</form>
				</td>
			</tr>
			{/if}
		</table>
	</td>
</table>
</div>

<br />

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="article.metadata"}</td>
</tr>
<tr>
	<td align="center">
		<form method="post" action="{$requestPageUrl}/viewMetadata/{$submission->getArticleId()}">
			<input type="submit" value="{translate key="submission.editMetadata"}">
		</form>
	</td>
</tr>
<tr>
	<td>
		<table class="metadata" width="100%">
		<tr>
			<td class="metadataHeading" colspan="2">{translate key="author.submit.submissionAbstract"}</td>
		</tr>
		<tr>
			<td class="metadataLabel">{translate key="common.abstract"}:</td>
			<td class="metadataContent">{$submission->getAbstract()|nl2br}</td>
		</tr>
		{if $alternateLocale1}
		<tr>
			<td class="metadataLabel">{translate key="common.abstract"} ({$languageToggleLocales.$alternateLocale1}):</td>
			<td class="metadataContent">{$submission->getAbstractAlt1()|nl2br}</td>
		</tr>
		{/if}
		{if $alternateLocale2}
		<tr>
			<td class="metadataLabel">{translate key="common.abstract"} ({$languageToggleLocales.$alternateLocale2}):</td>
			<td class="metadataContent">{$submission->getAbstractAlt2()|nl2br}</td>
		</tr>
		{/if}
		</table>
		</div>
		
		<br />
		
		<table class="metadata" width="100%">
		<tr>
			<td class="metadataHeading" colspan="2">{translate key="author.submit.submissionAuthors"}</td>
		</tr>
		</table>
	
		{foreach name=authors from=$authors key=authorIndex item=author}
		<table class="metadata" width="100%">
		<tr>
			<td class="metadataLabel">{translate key="user.firstName"}:</td>
			<td class="metadataContent">{$author->getFirstName()}</td>
		</tr>
		<tr>
			<td class="metadataLabel">{translate key="user.middleName"}:</td>
			<td class="metadataContent">{$author->getMiddleName()}</td>
		</tr>
		<tr>
			<td class="metadataLabel">{translate key="user.lastName"}:</td>
			<td class="metadataContent">{$author->getLastName()}</td>
		</tr>
		<tr>
			<td class="metadataLabel">{translate key="user.affiliation"}:</td>
			<td class="metadataContent">{$author->getAffiliation()}</td>
		</tr>
		<tr>
			<td class="metadataLabel">{translate key="user.email"}:</td>
			<td class="metadataContent">{$author->getEmail()}</td>
		</tr>
		<tr>
			<td class="metadataLabel">{translate key="user.biography"}:</td>
			<td class="metadataContent">{$author->getBiography()|nl2br}</td>
		</tr>
		</table>
		{/foreach}

		</div>
		
		<br />
		
		<table class="metadata" width="100%">
		<tr>
			<td class="metadataHeading" colspan="2">{translate key="author.submit.submissionIndexing"}</td>
		</tr>
		{if $journalSettings.metaDiscipline}
		<tr>
			<td class="metadataLabel">{translate key="article.discipline"}:</td>
			<td class="metadataContent">{$submission->getDiscipline()}</td>
		</tr>
		{/if}
		
		{if $journalSettings.metaSubjectClass}
		<tr>
			<td class="metadataLabel">{translate key="article.subjectClassification"}:</td>
			<td class="metadataContent">{$submission->getSubjectClass()}</td>
		</tr>
		{/if}
		
		{if $journalSettings.metaSubject}
		<tr>
			<td class="metadataLabel">{translate key="article.subject"}</td>
			<td class="metadataContent">{$submission->getSubject()}</td>
		</tr>
		{/if}
		
		{if $journalSettings.metaCoverage}
		<tr>
			<td class="metadataLabel">{translate key="article.coverageGeo"}:</td>
			<td class="metadataContent">{$submission->getCoverageGeo()}</td>
		</tr>
		<tr>
			<td class="metadataLabel">{translate key="article.coverageChron"}:</td>
			<td class="metadataContent">{$submission->getCoverageChron()}</td>
		</tr>
		{/if}
		
		{if $journalSettings.metaType}
		<tr>
			<td class="metadataLabel">{translate key="article.type"}</td>
			<td class="metadataContent">{$submission->getType()}</td>
		</tr>
		{/if}
		
		<tr>
			<td class="metadataLabel">{translate key="article.language"}:</td>
			<td class="metadataContent">{$submission->getLanguage()}</td>
		</tr>
		<tr>
			<td class="metadataLabel"></td>
			<td class="metadataContent">{translate key="author.submit.languageInstructions"}</td>
		</tr>
		</table>
		</div>
		
		<br />
		
		<table class="metadata" width="100%">
		<tr>
			<td class="metadataHeading" colspan="2">{translate key="author.submit.submissionSupportingAgencies"}</td>
		</tr>
		<tr>
			<td class="metadataLabel">{translate key="author.submit.agencies"}:</td>
			<td class="metadataContent">{$submission->getSponsor()}</td>
		</tr>
		</table>
		</div>
	</td>
</table>
</div>
{include file="common/footer.tpl"}
