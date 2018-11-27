{**
 * templates/controllers/grid/issues/form/issueData.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for creation and modification of an issue
 *}

{help file="issue-management" section="edit-issue-data" class="pkp_help_tab"}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#issueForm').pkpHandler(
			'$.pkp.controllers.form.FileUploadFormHandler',
			{ldelim}
				$uploader: $('#coverImageUploader'),
				$preview: $('#coverImagePreview'),
				uploaderOptions: {ldelim}
					uploadUrl: {url|json_encode op="uploadFile" escape=false},
					baseUrl: {$baseUrl|json_encode},
					filters: {ldelim}
						mime_types : [
							{ldelim} title : "Image files", extensions : "jpg,jpeg,png,svg" {rdelim}
						]
					{rdelim}
				{rdelim}
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="issueForm" method="post" action="{url op="updateIssue" issueId=$issueId}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="issueDataNotification"}

	{if $issue && $issue->getPublished()}
		{assign var=issuePublished value=true}
	{else}
		{assign var=issuePublished value=false}
	{/if}

	{if $issuePublished}
		{fbvFormArea id="datePublishedArea" title="editor.issues.datePublished"}
			{fbvFormSection}
				{if $issuePublished}
					{fbvElement type="text" id="datePublished" value=$datePublished size=$fbvStyles.size.SMALL class="datepicker"}
				{/if}
			{/fbvFormSection}
		{/fbvFormArea}
	{/if}


	{fbvFormArea id="identificationArea" title="editor.issues.identification"}
		{fbvFormSection}
			{fbvElement type="text" label="issue.volume" id="volume" value=$volume maxlength="40" inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" label="issue.number" id="number" value=$number maxlength="40" inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" label="issue.year" id="year" value=$year maxlength="4" inline=true size=$fbvStyles.size.SMALL}
		{/fbvFormSection}
		{fbvFormSection}
			{fbvElement type="text" label="issue.title" id="title" value=$title multilingual=true}
		{/fbvFormSection}

		{fbvFormSection list=true}
			{fbvElement type="checkbox" label="issue.volume" id="showVolume" checked=$showVolume inline=true value=1}
			{fbvElement type="checkbox" label="issue.number" id="showNumber" checked=$showNumber inline=true value=1}
			{fbvElement type="checkbox" label="issue.year" id="showYear" checked=$showYear inline=true value=1}
			{fbvElement type="checkbox" label="issue.title" id="showTitle" checked=$showTitle inline=true value=1}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="description" title="editor.issues.description"}
		{fbvElement type="textarea" id="description" value=$description multilingual=true rich=true}
	{/fbvFormArea}

	{fbvFormArea id="coverImage" title="editor.issues.coverPage"}
		{fbvFormSection}
			{include file="controllers/fileUploadContainer.tpl" id="coverImageUploader"}
			<input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
		{/fbvFormSection}
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
							{fbvElement type="text" id="coverImageAltText" label="common.altTextInstructions" value=$coverImageAltText}
						</span>

						<div id="{$deleteCoverImageLinkAction->getId()}" class="actions">
							{include file="linkAction/linkAction.tpl" action=$deleteCoverImageLinkAction contextId="issueForm"}
						</div>
					</div>
				</div>
			{/if}
		{/fbvFormSection}
	{/fbvFormArea}

	{foreach from=$pubIdPlugins item=pubIdPlugin}
		{assign var=pubIdMetadataFile value=$pubIdPlugin->getPubIdMetadataFile()}
		{include file="$pubIdMetadataFile" pubObject=$issue}
	{/foreach}

	{call_hook name="Templates::Editor::Issues::IssueData::AdditionalMetadata"}

	{fbvFormButtons submitText="common.save"}
</form>
