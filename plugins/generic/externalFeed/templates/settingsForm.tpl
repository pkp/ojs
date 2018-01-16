{**
 * plugins/generic/externalFeed/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * External feed plugin settings
 *
 *}

<script type="text/javascript">
	// Attach the file upload form handler.
	$(function() {ldelim}
		$('#externalFeedSettingsForm').pkpHandler(
			'$.pkp.controllers.form.FileUploadFormHandler',
			{ldelim}
				$uploader: $('#plupload'),
				uploaderOptions: {ldelim}
					{if $fileType == 'css'}
						filters: {ldelim}
							mime_types : [
								{ldelim} title : "CSS files", extensions : "css" {rdelim}
							]
						{rdelim},
					{/if}
					uploadUrl: {url|json_encode router=$smarty.const.ROUTE_COMPONENT component="grid.settings.plugins.settingsPluginGridHandler" op="manage" plugin="externalfeedplugin" category="generic" verb="uploadStyleSheet" escape=false},
					baseUrl: {$baseUrl|json_encode}
				{rdelim}
			{rdelim}
		);
	{rdelim});
</script>

<h2>{translate key="plugins.generic.externalFeed.settings.description"}</h2>

<h3>{translate key="plugins.generic.externalFeed.settings.styleSheet"}</h>

<form class="pkp_form" id="externalFeedSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.plugins.settingsPluginGridHandler" op="manage" plugin="externalfeedplugin" category="generic" verb="settings" save=true escape=false}" enctype="multipart/form-data">
	{csrf}
	
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="ExternalFeedSettingsFormNotification"}
	
	<p>{translate key="plugins.generic.externalFeed.settings.stylesheetDescription"}: <a href="{$defaultStyleSheetUrl}" target="_blank">{translate key="plugins.generic.externalFeed.settings.defaultStyleSheet"}</a></p>
	
	{fbvFormArea id="file"}
		{fbvFormSection title="common.file"}
			{include file="controllers/fileUploadContainer.tpl" id="plupload"}
			<input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
		{/fbvFormSection}
	{/fbvFormArea}
	
	{if $externalFeedStyleSheet}
		{translate key="common.fileName"}: <a href="{$publicFilesDir}/{$externalFeedStyleSheet.uploadName|escape:"url"}" class="file">{$externalFeedStyleSheet.name|escape}</a> {$externalFeedStyleSheet.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteStyleSheet" value="{translate key="common.delete"}" class="button" />
		<br/>
	{/if}

	{fbvFormButtons id="externalFeedSettingsFormSubmit" name="uploadStyleSheet" submitText="common.save" hideCancel=true}
	
</form>

