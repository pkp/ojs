{**
 * templates/controllers/tab/pubIds/form/publicIdentifiersForm.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @hook Templates::Controllers::Tab::PubIds::Form::PublicIdentifiersForm []
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#publicIdentifiersForm').pkpHandler(
			'$.pkp.controllers.form.AjaxFormHandler',
			{ldelim}
				trackFormChanges: true
			{rdelim}
		);
	{rdelim});
</script>
{if $pubObject instanceof Article}
	<form class="pkp_form" id="publicIdentifiersForm" method="post" action="{url router=PKP\core\PKPApplication::ROUTE_COMPONENT op="updateIdentifiers"}">
		{include file="controllers/notification/inPlaceNotification.tpl" notificationId="publicationIdentifiersFormFieldsNotification"}
		<input type="hidden" name="submissionId" value="{$pubObject->getId()|escape}" />
		<input type="hidden" name="stageId" value="{$stageId|escape}" />
		<input type="hidden" name="tabPos" value="1" />
		<input type="hidden" name="displayedInContainer" value="{$formParams.displayedInContainer|escape}" />
		<input type="hidden" name="tab" value="identifiers" />
{elseif $pubObject instanceof \PKP\galley\Galley}
	<form class="pkp_form" id="publicIdentifiersForm" method="post" action="{url component="grid.articleGalleys.ArticleGalleyGridHandler" op="updateIdentifiers" submissionId=$submissionId publicationId=$pubObject->getData('publicationId') representationId=$pubObject->getId() escape=false}">
		{include file="controllers/notification/inPlaceNotification.tpl" notificationId="representationIdentifiersFormFieldsNotification"}
{elseif $pubObject instanceof SubmissionFile}
	<form class="pkp_form" id="publicIdentifiersForm" method="post" action="{url component="api.file.ManageFileApiHandler" op="updateIdentifiers" fileId=$pubObject->getId() submissionId=$pubObject->getData('submissionId') stageId=$stageId fileStageId=$pubObject->getFileStage() escape=false}">
		{include file="controllers/notification/inPlaceNotification.tpl" notificationId="fileIdentifiersFormFieldsNotification"}
{else} {* $pubObject instanceof Issue *}
	<form class="pkp_form" id="publicIdentifiersForm" method="post" action="{url router=PKP\core\PKPApplication::ROUTE_COMPONENT op="updateIdentifiers" issueId=$pubObject->getId()}">
		{include file="controllers/notification/inPlaceNotification.tpl" notificationId="issueIdentifiersFormFieldsNotification"}
{/if}
{csrf}

{*include file="common/formErrors.tpl"*}
{if $enablePublisherId}
	{fbvFormSection}
		{fbvElement type="text" label="submission.publisherId" id="publisherId" name="publisherId" value=$publisherId size=$fbvStyles.size.MEDIUM disabled=$formDisabled}
	{/fbvFormSection}
{/if}

{foreach from=$pubIdPlugins item=pubIdPlugin}
	{assign var=pubIdMetadataFile value=$pubIdPlugin->getPubIdMetadataFile()}
	{assign var=canBeAssigned value=$pubIdPlugin->canBeAssigned($pubObject)}
	{include file="$pubIdMetadataFile" pubObject=$pubObject canBeAssigned=$canBeAssigned formDisabled=$formDisabled}
{/foreach}
{call_hook name="Templates::Controllers::Tab::PubIds::Form::PublicIdentifiersForm"}
{fbvFormButtons id="publicIdentifiersFormSubmit" submitText="common.save" submitDisabled=$formDisabled hideCancel=$formDisabled}

</form>
