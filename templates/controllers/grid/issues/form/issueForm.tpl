{**
 * templates/controllers/grid/issues/form/issueData.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for creation and modification of an issue
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#issueForm').pkpHandler(
			'$.pkp.controllers.form.FileUploadFormHandler',
			{ldelim}
				$uploader: $('#pluploadcss'),
				uploaderOptions: {ldelim}
					uploadUrl: '{url|escape:javascript op="uploadFile"}',
					baseUrl: '{$baseUrl|escape:javascript}'
				{rdelim}
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="issueForm" method="post" action="{url op="updateIssue" issueId=$issueId}">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="issueDataNotification"}

	{if $issue && $issue->getPublished()}
		{assign var=issuePublished value=true}
	{else}
		{assign var=issuePublished value=false}
	{/if}

	{if $currentJournal->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION || $issuePublished}
		{fbvFormArea id="issueAccessArea" title="editor.issues.access" class="border"}
			{fbvFormSection}
				{if $issuePublished}
					{fbvElement type="text" label="editor.issues.datePublished" id="datePublished" value=$datePublished|date_format:"%y-%m-%d" size=$fbvStyles.size.SMALL inline=true class="datepicker"}
				{/if}
				{fbvElement type="select" id="accessStatus" label="editor.issues.accessStatus" from=$accessOptions selected=$accessStatus translate=false size=$fbvStyles.size.SMALL inline=true}
				{fbvElement type="text" label="editor.issues.accessDate" id="openAccessDate" value=$openAccessDate|date_format:"%y-%m-%d" size=$fbvStyles.size.SMALL inline=true class="datepicker"}
			{/fbvFormSection}
		{/fbvFormArea}
	{/if}


	{fbvFormArea id="identificationArea" class="border" title="editor.issues.identification"}
		{fbvFormSection}
			{fbvElement type="text" label="issue.volume" id="volume" value=$volume maxlength="40" inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" label="issue.number" id="number" value=$number maxlength="40" inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" label="issue.year" id="year" value=$year maxlength="4" inline=true size=$fbvStyles.size.SMALL}
			{if $enablePublicIssueId}
				{fbvElement type="text" label="editor.issues.publicIssueIdentifier" id="publicIssueId" inline=true value=$publicIssueId size=$fbvStyles.size.SMALL}
			{/if}
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

	{fbvFormArea id="file"}
		{fbvFormSection title="editor.issues.styleFile"}
			<div id="pluploadcss"></div>
			{if $styleFileName}
				{translate key="common.currentStyleSheet"}: <a href="{$publicFilesDir}/{$styleFileName|escape}" target="_blank">{$originalStyleFileName|escape}</a>
			{/if}
		{/fbvFormSection}
	{/fbvFormArea}
	<input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />

	{foreach from=$pubIdPlugins item=pubIdPlugin}
		{assign var=pubIdMetadataFile value=$pubIdPlugin->getPubIdMetadataFile()}
		{include file="$pubIdMetadataFile" pubObject=$issue}
	{/foreach}

	{call_hook name="Templates::Editor::Issues::IssueData::AdditionalMetadata"}

	{fbvFormButtons submitText="common.save"}
</form>
