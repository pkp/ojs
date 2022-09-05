{**
 * templates/settingsForm.tpl
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * PLN plugin settings
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#plnSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

{if $prerequisitesMissing|@count > 0}
	<ul>
		{foreach from=$prerequisitesMissing item=message}
			<li><span class='pkp_form_error'>{$message}</span></li>
		{/foreach}
	</ul>
{/if}

<div id="plnSettings">
	<form class="pkp_form" id="plnSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
		{include file="controllers/notification/inPlaceNotification.tpl" notificationId="PLNSettingsFormNotification"}

		{fbvFormArea id="PLNSettingsFormArea"}
			{fbvFormSection title="plugins.generic.pln.settings.terms_of_use" list=true}
				{if $hasIssn}
					{foreach name=terms from=$terms_of_use key=term_name item=term_data}
						{if $terms_of_use_agreement[$term_name]} 
							{assign var="checked" value="checked"}
						{else}
							{assign var="checked" value=""}
						{/if}

						{fbvElement type="checkbox" name="terms_agreed[$term_name]" id="terms_agreed[$term_name]" value="1" checked=$checked label=$term_data.term translate=false}
					{/foreach}
				{else}
					<p>{translate key="plugins.generic.pln.notifications.issn_setting"}</p>
				{/if}
			{/fbvFormSection}

			{fbvFormSection title="plugins.generic.pln.settings.journal_uuid" list=true}
				<p>{translate key="plugins.generic.pln.settings.journal_uuid_help"}</p>
				<input type="text" id="journal_uuid" name="journal_uuid"  size="36" maxlength="36" class="textField" value="{$journal_uuid|escape}" readonly="readonly"/>
			{/fbvFormSection}

			{fbvFormSection title="plugins.generic.pln.settings.refresh" list=true}
				<p>{translate key="plugins.generic.pln.settings.refresh_help"}</p>
				<input type="submit" id="refresh" name="refresh" class="button" value="{translate key="plugins.generic.pln.settings.refresh"}"/>
			{/fbvFormSection}
			
			{fbvFormButtons id="plnPluginSettingsFormSubmit" submitText="common.save" hideCancel=true}
		{/fbvFormArea}
	</form>
</div>
