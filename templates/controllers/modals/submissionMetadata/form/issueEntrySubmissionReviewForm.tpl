{**
 * controllers/modals/submissionMetadata/form/issueEntrySubmissionReviewForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a submission's issue entry form.
 *
 *}
{* generate a unique ID for the form *}
{assign var="submissionMetadataViewFormId" value="submissionMetadataViewForm-"|uniqid|escape}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#{$submissionMetadataViewFormId}').pkpHandler(
			'$.pkp.controllers.form.FileUploadFormHandler',
			{ldelim}
				readOnly: {$formParams.readOnly|json_encode},
				$uploader: $('#coverImageUploader'),
				$preview: $('#coverImagePreview'),
				uploaderOptions: {ldelim}
					uploadUrl: {url|json_encode router=$smarty.const.ROUTE_COMPONENT op="uploadCoverImage" escape=false},
					baseUrl: {$baseUrl|json_encode},
					filters: {ldelim}
						mime_types : [
							{ldelim} title : "Image files", extensions : "jpg,jpeg,png,svg" {rdelim}
						]
					{rdelim},
					multipart_params: {ldelim}
						submissionId: {$submissionId|escape},
						{if $stageId}stageId: {$stageId|escape},{/if}
					{rdelim}
				{rdelim}
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="{$submissionMetadataViewFormId}" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="saveForm"}">
	{csrf}
	{assign var="notificationId" value="submissionMetadataViewFormNotification-"|uniqid|escape}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId=$notificationId}

	<input type="hidden" name="submissionId" value="{$submissionId|escape}" />
	<input type="hidden" name="stageId" value="{$stageId|escape}" />
	<input type="hidden" name="displayedInContainer" value="{$formParams.displayedInContainer|escape}" />
	<input type="hidden" name="tab" value="submission" />

	{include file="submission/form/section.tpl" readOnly=$formParams.readOnly}

	{include file="core:submission/submissionLocale.tpl" readOnly=$formParams.readOnly}

	{include file="core:submission/submissionMetadataFormTitleFields.tpl" readOnly=$formParams.readOnly}

	{* Contributors *}
	{if !$formParams.hideSubmit || !$formParams.anonymous}
		{* generate a unique ID for the form *}
		{assign var="authorsGridContainer" value="authorsGridContainer-"|uniqid|escape}
		{url|assign:authorGridUrl router=$smarty.const.ROUTE_COMPONENT  component="grid.users.author.AuthorGridHandler" op="fetchGrid" submissionId=$submissionId stageId=$stageId escape=false}
		{load_url_in_div id=$authorsGridContainer url="$authorGridUrl"}
	{/if}

	{* Cover Image *}
	{fbvFormArea id="coverImage" title="editor.article.coverImage"}
		{if !$formParams.readOnly}
			{fbvFormSection}
				{include file="controllers/fileUploadContainer.tpl" id="coverImageUploader"}
				<input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
			{/fbvFormSection}
		{/if}
		{fbvFormSection id="coverImagePreview"}
			{if $coverImage != ''}
				<div class="pkp_form_file_view pkp_form_image_view">
					<div class="img">
						<img src="{$publicFilesDir}/{$coverImage|escape:"url"}{'?'|uniqid}" {if $coverImageAlt !== ''} alt="{$coverImageAlt|escape}"{/if}>
					</div>

					<div class="data">
						<span class="title">
							{translate key="common.altText"}
						</span>
						<span class="value">
							{fbvElement type="text" id="coverImageAltText" label="common.altTextInstructions" value=$coverImageAltText readonly=$formParams.readOnly}
						</span>

						{if !$formParams.readOnly}
							<div id="{$deleteCoverImageLinkAction->getId()}" class="actions">
								{include file="linkAction/linkAction.tpl" action=$deleteCoverImageLinkAction contextId="issueForm"}
							</div>
						{/if}
					</div>
				</div>
			{/if}
		{/fbvFormSection}
	{/fbvFormArea}

	{include file="submission/submissionMetadataFormFields.tpl" readOnly=$formParams.readOnly}

	{if !$formParams.hideSubmit}
		{fbvFormButtons id="submissionMetadataFormSubmit" submitText="common.save"}
	{/if}
</form>
