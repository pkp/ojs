{**
 * templates/controllers/grid/issues/form/coverForm.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for creation and modification of an issue
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#coverForm').pkpHandler(
			'$.pkp.controllers.form.FileUploadFormHandler',
			{ldelim}
				$uploader: $('#pluploadimage'),
				uploaderOptions: {ldelim}
					uploadUrl: {url|json_encode op="uploadFile" escape=false},
					baseUrl: {$baseUrl|json_encode}
				{rdelim}
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="coverForm" method="post" action="{url op="updateCover" issueId=$issueId}">
	{fbvFormArea id="enableCover"}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="showCoverPage" value=1 checked=$showCoverPage label="editor.issues.showCoverPage" inline=true}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="coverFile"}
		{fbvFormSection title="editor.issues.coverPage"}
			<div id="pluploadimage"></div>
			<input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
			{if $fileName.$formLocale}
				<img src="{$publicFilesDir}/{$fileName.$formLocale|escape}?random=$issueId|uniqid}" alt="{$coverPageAltText.$formLocale|escape}"/>
			{/if}
			{fbvElement type="text" id="coverPageAltText" label="common.altText" value=$coverPageAltText multilingual=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="coverDetails"}
		{fbvFormSection}
			{fbvElement type="textarea" id="coverPageDescription" value=$coverPageDescription multilingual=true rich=true label="editor.issues.coverPageCaption"}
		{/fbvFormSection}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="hideCoverPageArchives" value=1 checked=$hideCoverPageArchives label="editor.issues.hideCoverPageArchives" inline=true multilingual=true}
			{fbvElement type="checkbox" id="hideCoverPageCover" value=1 checked=$hideCoverPageCover label="editor.issues.hideCoverPageCover" inline=true multilingual=true}
		{/fbvFormSection}
	{/fbvFormArea}
{fbvFormButtons submitText="common.save"}

</form>
