{**
 * submissionEditing.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of a submission.
 *
 * FIXME: The tabbed navigation does NOT use nested lists. This might want to be addressed later.
 *
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{assign var="pageId" value="sectionEditor.submissionEditing"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$requestPageUrl}/summary/{$submission->getArticleId()}">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.submission"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}">{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}" class="active">{translate key="submission.submissionEditing"}</a></li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}">{translate key="submission.submissionHistory"}</a></li>
</ul>
<ul id="subnav">
	<li><a href="#copyedit">{translate key="submission.copyedit"}</a></li>
	<li><a href="#layout">{translate key="submission.layout"}</a></li>
	<li><a href="#proofread">{translate key="submission.proofread"}</a></li>
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

{include file="sectionEditor/submission/copyedit.tpl"}

<br />

{include file="sectionEditor/submission/layout.tpl"}

<br />

<a name="proofread"></a>
<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.proofread"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
			<tr>
				<td width="55%" colspan="3"><a href="">{translate key="submission.proofreadingComments"}</a></td>
				<td width="15%" class="label">{translate key="submission.request"}</td>
				<td width="15%" class="label">{translate key="submission.complete"}</td>
				<td width="15%" class="label">{translate key="submission.thank"}</td>
			</tr>
			<tr>
				<td width="5%">A.</td>
				<td width="25%">{translate key="user.role.author"}</td>
				<td width="25%" align="right">
					<form method="post" action="">
						<input type="submit" value="{translate key="editor.article.notify"}">
					</form>
				</td>
				<td width="15%"></td>
				<td width="15%"></td>
				<td width="15%"></td>
			</tr>
			<tr>
				<td width="5%">B.</td>
				<td width="25%">{translate key="user.role.editor"}</td>
				<td width="25%" align="right">
					<form method="post" action="">
						<input type="submit" value="{translate key="editor.article.initiate"}">
					</form>
				</td>
				<td width="15%"></td>
				<td width="15%"></td>
				<td width="15%">{translate key="common.notApplicableShort"}</td>
			</tr>
			<tr>
				<td colspan="6" align="right">
					<table class="plainFormat">
						<tr>
							<td>
								<form method="post" action="">
									<input type="submit" value="{translate key="submission.queueForScheduling"}">
								</form>
							</td>
							<td>
								<form method="post" action="">
									<input type="submit" value="{translate key="submission.archiveSubmission"}">
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
{include file="common/footer.tpl"}
