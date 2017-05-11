{**
 * templates/user/publicProfileForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Public user profile form.
 *}

{* Help Link *}
{help file="user-profile.md" class="pkp_help_tab"}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#publicProfileForm').pkpHandler(
			'$.pkp.controllers.form.FileUploadFormHandler',
			{ldelim}
				$uploader: $('#plupload'),
				uploaderOptions: {ldelim}
					uploadUrl: {url|json_encode op="uploadProfileImage" escape=false},
					baseUrl: {$baseUrl|json_encode},
					filters: {ldelim}
						mime_types : [
							{ldelim} title : "Image files", extensions : "jpg,jpeg,png" {rdelim}
						]
					{rdelim},
					resize: {ldelim}
						width: {$profileImageMaxWidth|intval},
						height: {$profileImageMaxHeight|intval},
						crop: true,
					{rdelim}
				{rdelim}
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="publicProfileForm" method="post" action="{url op="savePublicProfile"}" enctype="multipart/form-data">
	{csrf}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="publicProfileNotification"}

	{fbvFormSection title="user.profile.form.profileImage"}
		{if $profileImage}
			{* Add a unique ID to prevent caching *}
			<img src="{$baseUrl}/{$publicSiteFilesPath}/{$profileImage.uploadName}?{""|uniqid}" alt="{translate key="user.profile.form.profileImage"}" />
			<div>
				<a class="pkp_button pkp_button_offset" href="{url op="deleteProfileImage"}">{translate key="common.delete"}</a>
			</div>
		{/if}
	{/fbvFormSection}
	{fbvFormSection}
		{include file="controllers/fileUploadContainer.tpl" id="plupload"}
	{/fbvFormSection}

	{fbvFormSection}
		{fbvElement type="textarea" label="user.biography" multilingual="true" name="biography" id="biography" rich=true value=$biography}
	{/fbvFormSection}
	{fbvFormSection}
		{fbvElement type="text" label="user.url" name="userUrl" id="userUrl" value=$userUrl maxlength="255"}
	{/fbvFormSection}
	{fbvFormSection}
		{fbvElement type="text" label="user.orcid" name="orcid" id="orcid" value=$orcid maxlength="36"}
	{/fbvFormSection}

	{fbvFormButtons hideCancel=true submitText="common.save"}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
