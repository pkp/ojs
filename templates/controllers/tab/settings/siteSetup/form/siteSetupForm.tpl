{**
 * controllers/tab/settings/siteSetup/form/siteSetupForm.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site settings form.
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#siteSetupForm').pkpHandler(
			'$.pkp.controllers.tab.settings.form.FileViewFormHandler',
			{ldelim}
				fetchFileUrl: {url|json_encode op="fetchFile" tab="siteSetup" escape=false},
			{rdelim}
		);
	{rdelim});
</script>

<form id="siteSetupForm" class="pkp_form" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.AdminSettingsTabHandler" op="saveFormData" tab="siteSetup"}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="siteSetupFormNotification"}

	{fbvFormSection title="admin.settings.siteTitle" required=true}
		{fbvElement type="text" id="title" name="title" value=$title required=true multilingual=true}
	{/fbvFormSection}

	{fbvFormSection label="admin.settings.siteLogo"}
		<div id="pageHeaderTitleImage">
			{$imageView}
		</div>
		<div id="{$uploadImageLinkAction->getId()}" class="pkp_linkActions">
			{include file="linkAction/linkAction.tpl" action=$uploadImageLinkAction contextId="siteSetupForm"}
		</div>
	{/fbvFormSection}

	{fbvFormArea id="journalInformation"}
		{fbvFormSection title="admin.settings.aboutDescription"}
			{fbvElement type="textarea" multilingual=true id="about" value=$about}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="siteRedirection"}
		{fbvFormSection title="admin.settings.journalRedirect"}
			{fbvElement type="select" id="redirect" from=$redirectOptions selected=$redirect translate=false defaultValue="" label="admin.settings.journalRedirectInstructions" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="siteContact"}
		{fbvFormSection title="admin.settings.contactName" required=true}
			{fbvElement type="text" multilingual=true name="contactName" id="contatcName" value=$contactName size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection title="admin.settings.contactEmail" required=true}
			{fbvElement type="text" multilingual=true name="contactEmail" id="contactEmail" value=$contactEmail size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="security"}
		{fbvFormSection title="admin.settings.minPasswordLength" required=true}
			{fbvElement type="text" id="minPasswordLength" value=$minPasswordLength size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="appearance"}
		{fbvFormSection title="admin.settings.siteStyleSheet"}
			<div id="siteStyleSheet">
				{$cssView}
			</div>
			<div id="{$uploadCssLinkAction->getId()}" class="pkp_linkActions">
				{include file="linkAction/linkAction.tpl" action=$uploadCssLinkAction contextId="siteSetupForm"}
			</div>
		{/fbvFormSection}
		{fbvFormSection label="manager.setup.layout.theme" description="manager.setup.layout.themeDescription"}
			{fbvElement type="select" id="themePluginPath" from=$themePluginOptions selected=$themePluginPath translate=false size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection}{/fbvFormSection}{* FIXME: Clear inline fbvFormSections *}
	{/fbvFormArea}
	{fbvFormArea id="oai"}
		{fbvFormSection title="admin.settings.oaiRegistration"}
			{url|assign:"oaiUrl" router=$smarty.const.ROUTE_PAGE page="oai"}
			{url|assign:"siteUrl" router=$smarty.const.ROUTE_PAGE page="index"}
			<p>{translate key="admin.settings.oaiRegistrationDescription" siteUrl=$siteUrl oaiUrl=$oaiUrl}</p>
			{if count($availableMetricTypes) > 1}
				<br />
				<div id="defaultMetricSelection">
					<h4>{translate key="defaultMetric.title"}</h4>
					<p>{translate key="admin.settings.defaultMetricDescription"}</p>
					<table class="data" width="100%">
						<tr valign="top">
							<td width="20%" class="label">{fieldLabel name="defaultMetricType" key="defaultMetric.availableMetrics"}</td>
							<td colspan="2" width="80%" class="value">
								<select name="defaultMetricType" class="selectMenu" id="defaultMetricType">
									{foreach from=$availableMetricTypes key=metricType item=displayName}
										<option value="{$metricType|escape}"{if $metricType == $defaultMetricType} selected="selected"{/if}>{$displayName|escape}</option>
									{/foreach}
								</select>
							</td>
						</tr>
					</table>
				</div>
			{/if}
		{/fbvFormSection}
	{/fbvFormArea}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
	{fbvFormButtons id="siteSetupFormSubmit" submitText="common.save" hideCancel=true}
</form>
