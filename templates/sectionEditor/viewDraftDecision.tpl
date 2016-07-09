{**
 * templates/sectionEditor/submissionReview.tpl
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission review.
 *
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="submission.page.review" id=$submission->getId()}{assign var="pageCrumbTitle" value="submission.review"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getId()}">{translate key="submission.summary"}</a></li>
	<li class="current"><a href="{url op="submissionReview" path=$submission->getId()}">{translate key="submission.review"}</a></li>
	{if $canEdit}<li><a href="{url op="submissionEditing" path=$submission->getId()}">{translate key="submission.editing"}</a></li>{/if}
	<li><a href="{url op="submissionHistory" path=$submission->getId()}">{translate key="submission.history"}</a></li>
	<li><a href="{url op="submissionCitations" path=$submission->getId()}">{translate key="submission.citations"}</a></li>
</ul>

<h3>{translate key="editor.draft.decision"}</h3>
<div>
	<form method="POST">
		{if $view == 'view'}
			<p>{translate key="editor.draft.intro"}</p>
			{foreach from=$draft item=d}
				{assign var="decision" value=$d.decision}
				<h5>{translate key="user.role.editor"}:</h5>
				{$d.junior_editor_name}
				<h5>{translate key="editor.article.recommendation"}:</h5>
				{translate key=$editorDecisionOptions.$decision}
				<h5>{translate key="editor.draft.SectionEditorRecommendation"}:</h5>
				{$d.note}
				<h5>Subject:</h5>
				{$d.subject}
				<h5>Body:</h5>
				{$d.body|nl2br}
			{/foreach}
			<br /><br />
			{if $isEditor }	
				<input type="submit" name="decline_draft" value="{translate key="editor.draft.decision.decline"}" class="button" /> &nbsp;&nbsp;{/if}<input type="submit" name="edit_draft" value="{translate key="editor.draft.decision.edit"}" class="button" /> &nbsp;&nbsp;{if $isEditor}<input type="submit" name="accept_draft" value="{translate key="editor.draft.decision.accept"}" class="button defaultButton" />{/if}
			{else}
			{foreach from=$draft item=d}
				{assign var="decision" value=$d.decision}
				<h5>Editor:</h5>
				{$d.junior_editor_name}
				<br /><br />
				<label for="decision">{translate key="editor.draft.decision"}</label><br />
				<select name="decision" size="1" class="selectMenu">
					{html_options_translate options=$editorDecisionOptions selected=$d.decision}
				</select>
				<br /><br />
				<label for="subject">{translate key="email.subject"}</label><br />
				<input id="subject" name="subject" value="{$d.subject|escape}" size="60" maxlength="200" class="textField" type="text">
				<br /><br />
				<label for="body">{translate key="email.body"}</label><br />
				<textarea name="body" cols="60" rows="15" class="textArea">{$d.body}</textarea>
				<br /><br />
				<label for="note">{translate key="editor.draft.emailNote"}</label><br />
				<textarea name="note" cols="60" rows="5" class="textArea">{$d.note}</textarea>
				<br /><br />
				<input type="submit" name="save_changes" value="Save Changes" class="button defaultButton" />
			{/foreach}
		{/if}
	</form>
</div>
{include file="common/footer.tpl"}