{**
 * templates/editor/issues/issueGalleyForm.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to add/edit an issue galley.
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#issueGalleyForm').pkpHandler(
			'$.pkp.controllers.form.FileUploadFormHandler',
			{ldelim}
				$uploader: $('#plupload'),
				uploaderOptions: {ldelim}
					uploadUrl: {url|json_encode op="upload" issueId=$issueId escape=false},
					baseUrl: {$baseUrl|json_encode}
				{rdelim}
			{rdelim}
		);
	{rdelim});
</script>
<form class="pkp_form" id="issueGalleyForm" method="post" action="{url op="update" issueId=$issueId issueGalleyId=$issueGalleyId}">
	{fbvFormArea id="file"}
		{fbvFormSection title="editor.issues.galley" required="true"}
			{include file="controllers/fileUploadContainer.tpl" id="plupload"}
			<input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
			{if $issueGalley}
				<a href="{url op="download" issueId=$issueId issueGalleyId=$issueGalleyId}" target="_blank">{$issueGalley->getOriginalFileName()|escape}</a>
			{/if}
		{/fbvFormSection}

		{fbvFormSection title="submission.layout.galleyLabel" required="true"}
			{fbvElement type="text" value=$label id="label"}
		{/fbvFormSection}
		{if $enablePublicGalleyId}
			{fbvFormSection title="submission.layout.publicGalleyId" required="true"}
				{fbvElement type="text" value=$publicGalleyId id="publicGalleyId"}
			{/fbvFormSection}
		{/if}
		{fbvFormSection title="common.language" required="true"}
			{fbvElement type="select" id="galleyLocale" from=$supportedLocales selected=$galleyLocale|default:$formLocale translate=false}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons submitText="common.save"}
</form>
