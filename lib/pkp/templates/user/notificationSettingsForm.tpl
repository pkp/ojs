{**
 * templates/user/notificationSettingsForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User profile form.
 *}

{* Help Link *}
{help file="user-profile.md" class="pkp_help_tab"}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#notificationSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler', {ldelim}
			'enableDisablePairs': {ldelim}
					{foreach from=$notificationSettingCategories item=notificationSettingCategory}
						{foreach name=notifications from=$notificationSettingCategory.settings item=settingId}
						{$notificationSettings.$settingId.settingName|json_encode}: {$notificationSettings.$settingId.emailSettingName|json_encode},
						{/foreach}
					{/foreach}
				{rdelim}
		{rdelim});
	{rdelim});
</script>

<p>{translate key="notification.settingsDescription"}</p>

<form class="pkp_form" id="notificationSettingsForm" method="post" action="{url op="saveNotificationSettings"}" enctype="multipart/form-data">
	{csrf}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="notificationSettingsFormNotification"}

	{fbvFormArea id="notificationSettings"}
		{foreach from=$notificationSettingCategories item=notificationSettingCategory}
			<h4>{translate key=$notificationSettingCategory.categoryKey}</h4>
			{foreach from=$notificationSettingCategory.settings item=settingId}
				{assign var="settingName" value=$notificationSettings.$settingId.settingName}
				{assign var="emailSettingName" value=$notificationSettings.$settingId.emailSettingName}
				{translate|assign:"settingKey" key=$notificationSettings.$settingId.settingKey title="common.title"|translate}

				{fbvFormSection title=$settingKey list=true translate=false}
					{if $settingId|in_array:$blockedNotifications}
						{assign var="checked" value="0"}
					{else}
						{assign var="checked" value="1"}
					{/if}
					{if $settingId|in_array:$emailSettings}
						{assign var="emailChecked" value="1"}
					{else}
						{assign var="emailChecked" value="0"}
					{/if}
					{fbvElement type="checkbox" id=$settingName checked=$checked label="notification.allow"}
					{fbvElement type="checkbox" id=$emailSettingName checked=$emailChecked label="notification.email"}
				{/fbvFormSection}
			{/foreach}
		{/foreach}
		<br />
		{fbvFormButtons hideCancel=true submitText="common.save"}
	{/fbvFormArea}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
