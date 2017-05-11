{**
 * templates/controllers/modals/editorDecision/form/sendReviewsForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form used to send reviews to author
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		$('#sendReviews').pkpHandler(
			'$.pkp.controllers.modals.editorDecision.form.EditorDecisionFormHandler',
			{ldelim}
				peerReviewUrl: {$peerReviewUrl|json_encode}
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="sendReviews" method="post" action="{url op=$saveFormOperation}" >
	{csrf}
	<input type="hidden" name="submissionId" value="{$submissionId|escape}" />
	<input type="hidden" name="stageId" value="{$stageId|escape}" />
	<input type="hidden" name="decision" value="{$decision|escape}" />
	<input type="hidden" name="reviewRoundId" value="{$reviewRoundId|escape}" />

	{if array_key_exists('help', $decisionData)}
		<p>{translate key=$decisionData.help}</p>
	{/if}

	{fbvFormSection title="user.role.author_s" for="authorName" size=$fbvStyles.size.MEDIUM}
		{fbvElement type="text" id="authorName" name="authorName" value=$authorName disabled=true}
	{/fbvFormSection}

	{* Message to reviewer textarea *}
	{fbvFormSection title="editor.review.personalMessageToAuthor" for="personalMessage"}
		{fbvElement type="textarea" name="personalMessage" id="personalMessage" value=$personalMessage rich=true variables=$allowedVariables}
	{/fbvFormSection}

	{* Button to add reviews to the email automatically *}
	{if $reviewsAvailable}
		{fbvFormSection}
			<a id="importPeerReviews" href="#" class="pkp_button">
				<span class="fa fa-plus" aria-hidden="true"></span>
				{translate key="submission.comments.addReviews"}
			</a>
		{/fbvFormSection}
	{/if}

	{* option to skip sending this email *}
	{fbvFormSection for="skipEmail" size=$fbvStyles.size.MEDIUM list=true}
		{fbvElement type="checkbox" id="skipEmail" name="skipEmail" label="editor.submissionReview.skipEmail"}
	{/fbvFormSection}

	{** Some decisions can be made before review is initiated (i.e. no attachments). **}
	{if $reviewRoundId}
		<div id="attachments" style="margin-top: 30px;">
			{url|assign:reviewAttachmentsGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.attachment.EditorSelectableReviewAttachmentsGridHandler" op="fetchGrid" submissionId=$submissionId stageId=$stageId reviewRoundId=$reviewRoundId escape=false}
			{load_url_in_div id="reviewAttachmentsGridContainer" url=$reviewAttachmentsGridUrl}
		</div>
	{/if}

	{fbvFormButtons submitText="editor.submissionReview.recordDecision"}
</form>
