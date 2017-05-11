{**
 * templates/manager/reviewForms/reviewFormForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to create/modify a review form.
 *
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#reviewFormForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="reviewFormForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.reviewForms.ReviewFormGridHandler" op="updateReviewForm"}">
	{csrf}

	{if $reviewFormId}
		{fbvElement id="reviewFormId" type="hidden" name="reviewFormId" value=$reviewFormId}
	{/if}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="reviewFormsNotification"}

	{fbvFormArea id="reviewFormForm"}
		{fbvFormSection title="manager.reviewForms.title" required=true for="title"}
			{fbvElement type="text" id="title" value=$title multilingual=true required=true}
		{/fbvFormSection}
		{fbvFormSection title="manager.reviewForms.description" for="description"}
			{fbvElement type="textarea" id="description" value=$description multilingual=true rich=true}
		{/fbvFormSection}
		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
		{fbvFormButtons id="reviewFormFormSubmit" submitText="common.save"}
	{/fbvFormArea}
</form>
