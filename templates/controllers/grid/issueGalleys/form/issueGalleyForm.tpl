{**
 * templates/editor/issues/issueGalleyForm.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Form to add/edit an issue galley.
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#issueGalleyForm').pkpHandler(
			'$.pkp.controllers.form.FileUploadFormHandler',
			{ldelim}
				$uploader: $('#pluploadgalley'),
				uploaderOptions: {ldelim}
					uploadUrl: {url|json_encode op="upload" issueId=$issueId escape=false},
					baseUrl: {$baseUrl|json_encode},
					browse_button: 'pkpIssueGalleyUploaderButton'
				{rdelim}
			{rdelim}
		);
	{rdelim});
</script>
<form class="pkp_form" id="issueGalleyForm" method="post" action="{url op="update" issueId=$issueId issueGalleyId=$issueGalleyId}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="publicationMetadataFormFieldsNotification"}
	{fbvFormArea id="file"}
		{fbvFormSection title="editor.issues.galley" required="true"}
			{include file="controllers/fileUploadContainer.tpl" id="pluploadgalley" browseButton="pkpIssueGalleyUploaderButton"}
			<input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
			{if $issueGalley}
				<a href="{url op="download" issueId=$issueId issueGalleyId=$issueGalleyId}" target="_blank">{$issueGalley->getOriginalFileName()|escape}</a>
			{/if}
		{/fbvFormSection}

		{fbvFormSection title="submission.layout.galleyLabel" required="true"}
			{fbvElement type="text" value=$label id="label" required="true"}
		{/fbvFormSection}
		{if $enablePublisherId}
			{fbvFormSection title="submission.publisherId"}
				{fbvElement type="text" value=$publicGalleyId id="publicGalleyId"}
			{/fbvFormSection}
		{/if}
		{fbvFormSection title="common.language" required="true"}
			{fbvElement type="select" id="galleyLocale" from=$supportedLocales selected=$galleyLocale|default:$formLocale translate=false required="true"}
		{/fbvFormSection}
		{fbvFormSection title="publication.urlPath"}
			{fbvElement type="text" label="publication.urlPath.description" value=$urlPath id="urlPath" size=$fbvStyles.size.MEDIUM inline=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons submitText="common.save"}
</form>
