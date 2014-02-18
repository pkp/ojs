{**
 * controllers/tab/settings/identifiers/form/identifiersForm.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Access options form.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#identifiersForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="identifiersForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.OJSDistributionSettingsTabHandler" op="saveFormData" tab="identifiers"}">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="identifiersFormNotification"}

	{fbvFormArea id="identifiers"}
		<p>{translate key="manager.setup.uniqueIdentifierDescription"}</p>
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="enablePublicIssueId" name="enablePublicIssueId" value=1 checked=$enablePublicIssueId label="manager.setup.enablePublicIssueId"}
			{fbvElement type="checkbox" id="enablePublicArticleId" name="enablePublicArticleId" value=1 checked=$enablePublicArticleId label="manager.setup.enablePublicArticleId"}
			{fbvElement type="checkbox" id="enablePublicGalleyId" name="enablePublicGalleyId" value=1 checked=$enablePublicGalleyId label="manager.setup.enablePublicGalleyId"}
			{fbvElement type="checkbox" id="enablePageNumber" name="enablePageNumber" value=1 checked=$enablePageNumber label="manager.setup.enablePageNumber"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons submitText="common.save" hideCancel=true}
</form>
