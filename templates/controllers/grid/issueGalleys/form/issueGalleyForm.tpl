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
		{fbvFormSection title="editor.issues.galley"}
			<div id="plupload"></div>
			<input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
			{if $issueGalley}
				<a href="{url op="download" issueId=$issueId issueGalleyId=$issueGalleyId}" target="_blank">{$issueGalley->getOriginalFileName()|escape}</a>
			{/if}
		{/fbvFormSection}

		{fbvFormSection title="submission.layout.galleyFileData"}
			{fbvElement type="text" label="submission.layout.galleyLabel" value=$label id="label"}
			{if $enablePublicGalleyId}
				{fbvElement type="text" label="submission.layout.publicGalleyId" value=$publicGalleyId id="publicGalleyId" size=$fbvStyles.size.MEDIUM}
			{/if}
			{fbvElement type="select" id="galleyLocale" label="common.language" from=$supportedLocales selected=$galleyLocale|default:$formLocale size=$fbvStyles.size.MEDIUM translate=false}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons submitText="common.save"}
</form>
