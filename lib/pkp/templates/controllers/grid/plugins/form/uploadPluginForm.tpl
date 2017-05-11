{**
 * controllers/grid/plugins/uploadPluginForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to upload plugin files.
 *}
<script>
	$(function() {ldelim}
		// Attach the upload form handler.
		$('#uploadPluginForm').pkpHandler(
			'$.pkp.controllers.form.FileUploadFormHandler',
			{ldelim}
				$uploader: $('#plupload'),
				uploaderOptions: {ldelim}
					uploadUrl: {url|json_encode router=$smarty.const.ROUTE_COMPONENT op="uploadPluginFile" function=$function escape=false},
					baseUrl: {$baseUrl|json_encode}
				{rdelim}
			{rdelim});
	{rdelim});
</script>

<form class="pkp_form" id="uploadPluginForm" action="{url router=$smarty.const.ROUTE_COMPONENT op="saveUploadPlugin" function=$function category=$category plugin=$plugin}" method="post">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="uploadPluginNotification"}

	{fbvFormArea id="file"}
		{if $function == 'install'}
			<p>{translate key="manager.plugins.uploadDescription"}</p>
		{elseif $function == 'upgrade'}
			<p>{translate key="manager.plugins.upgradeDescription"}</p>
		{/if}
		{fbvFormSection title="manager.plugins.uploadPluginDir" required=true}
			{fbvElement type="hidden" id="temporaryFileId" value=""}
			{* The uploader widget *}
			{include file="controllers/fileUploadContainer.tpl" id="plupload"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons id="mastheadFormSubmit" submitText="common.save"}
</form>
<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
