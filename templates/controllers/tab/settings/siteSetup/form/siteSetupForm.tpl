{**
 * controllers/tab/settings/siteSetup/form/siteSetupForm.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site settings form.
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#siteSetupForm').pkpHandler('$.pkp.controllers.tab.settings.form.FileViewFormHandler',
			{ldelim}
				fetchFileUrl: '{url|escape:javascript op="fetchFile" tab="siteSetup" escape=false}',
			{rdelim}
		);
	{rdelim});
</script>

<form id="siteSetupForm" class="pkp_form" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.AdminSettingsTabHandler" op="saveFormData" tab="siteSetup"}">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="siteSetupFormNotification"}

	<h4>{translate key="admin.settings.siteTitle"}</h4>
	{fbvFormArea id="siteTitle"}
		{fbvFormSection list=true}
			{fbvElement type="radio" name="pageHeaderTitleType[$locale]" id="pageHeaderTitleType-0" value=0 checked=!$pageHeaderTitleType[$locale] label="manager.setup.useTextTitle"}
		{/fbvFormSection}
		{fbvFormSection}
			{fbvElement type="text" name="title" id="title" value=$title multilingual=true required=true}
		{/fbvFormSection}
		{fbvFormSection list=true}
			{fbvElement type="radio" name="pageHeaderTitleType[$locale]" id="pageHeaderTitleType-1" value=1 checked=$pageHeaderTitleType[$locale] label="manager.setup.useImageTitle" inline=true}
			<div id="{$uploadImageLinkAction->getId()}" class="pkp_linkActions inline">
				{include file="linkAction/linkAction.tpl" action=$uploadImageLinkAction contextId="siteSetupForm"}
			</div>
			<div id="pageHeaderTitleImage">
				{$imageView}
			</div>
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="journalInformation"}
		{fbvFormSection title="admin.settings.introduction"}
			{fbvElement type="textarea" multilingual=true id="intro" value=$intro}
		{/fbvFormSection}
		{fbvFormSection title="admin.settings.aboutDescription"}
			{fbvElement type="textarea" multilingual=true id="aboutField" value=$about}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="siteRedirection"}
		{fbvFormSection title="admin.settings.journalRedirect"}
			{fbvElement type="select" id="redirect" from=$redirectOptions selected=$redirect translate=false defaultValue="" defaultLabel=""}
			<span class="instruct">{translate key="admin.settings.journalRedirectInstructions"}</span>
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="journalListOptions" class="border"}
		<p>{translate key="admin.settings.journalsList.description"}</p>

		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="showTitle" name="showTitle" value=1 checked=$showTitle label="admin.settings.journalsList.showTitle"}
			{fbvElement type="checkbox" id="showThumbnail" name="showThumbnail" value=1 checked=$showThumbnail label="admin.settings.journalsList.showThumbnail"}
			{fbvElement type="checkbox" id="showDescription" name="showDescription" value=1 checked=$showDescription label="admin.settings.journalsList.showDescription"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="siteContact"}
		{fbvFormSection title="admin.settings.contactName" required=true}
			{fbvElement type="text" multilingual=true name="contactName" id="contatcName" value=$contactName}
		{/fbvFormSection}
		{fbvFormSection title="admin.settings.contactEmail" required=true}
			{fbvElement type="text" multilingual=true name="contactEmail" id="contactEmail" value=$contactEmail}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="security"}
		{fbvFormSection title="admin.settings.minPasswordLength" required=true}
			{fbvElement type="text" id="minPasswordLength" value=$minPasswordLength}
			{translate key="admin.settings.passwordCharacters"}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormArea id="appearance"}
		{fbvFormSection title="admin.settings.siteStyleSheet" inline=true size=$fbvStyles.size.MEDIUM}
			<div id="{$uploadCssLinkAction->getId()}" class="pkp_linkActions">
				{include file="linkAction/linkAction.tpl" action=$uploadCssLinkAction contextId="siteSetupForm"}
			</div>
			<div id="siteStyleSheet">
				{$cssView}
			</div>
		{/fbvFormSection}
		{fbvFormSection title="manager.setup.layout.theme" description="manager.setup.layout.themeDescription" size=$fbvStyles.size.MEDIUM inline=true}
			{fbvElement type="select" id="themePluginPath" from=$themePluginOptions selected=$themePluginPath translate=false size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection}{/fbvFormSection}{* FIXME: Clear inline fbvFormSections *}
	{/fbvFormArea}
	<h4>{translate key="admin.settings.oaiRegistration"}</h4>
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

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
	{fbvFormButtons id="siteSetupFormSubmit" submitText="common.save" hideCancel=true}
</form>
