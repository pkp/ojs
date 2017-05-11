{**
 * templates/controllers/grid/users/reviewer/createReviewerForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Create a reviewer and assign to a submission form.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#createReviewerForm').pkpHandler('$.pkp.controllers.grid.users.reviewer.form.AddReviewerFormHandler',
			{ldelim}
				fetchUsernameSuggestionUrl: {url|json_encode router=$smarty.const.ROUTE_COMPONENT component="api.user.UserApiHandler" op="suggestUsername" firstName="FIRST_NAME_DUMMY" lastName="LAST_NAME_DUMMY" escape=false},
				usernameSuggestionTextAlert: {translate|json_encode key="grid.user.mustProvideName"},
				templateUrl: {url|json_encode router=$smarty.const.ROUTE_COMPONENT component='grid.users.reviewer.ReviewerGridHandler' op='fetchTemplateBody' stageId=$stageId reviewRoundId=$reviewRoundId submissionId=$submissionId escape=false}
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form" id="createReviewerForm" method="post" action="{url op="createReviewer"}" >
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="createReviewerFormNotification"}

	<div class="action_links">
		{foreach from=$reviewerActions item=action}
			{include file="linkAction/linkAction.tpl" action=$action contextId="createReviewerForm"}
		{/foreach}
	</div>

	<h3>{translate key="editor.review.createReviewer"}</h3>

	{if count($userGroups)>1}
		{fbvFormSection title="user.group" required="true"}
			{fbvElement type="select" name="userGroupId" id="userGroupId" from=$userGroups translate=false label="editor.review.userGroupSelect" required="true"}
		{/fbvFormSection}
	{elseif count($userGroups)==1}
		{foreach from=$userGroups key=userGroupId item=userGroupName}
			{fbvElement type="hidden" id="userGroupId" value=$userGroupId}
		{/foreach}
	{/if}

	{fbvFormSection title="common.name"}
		{fbvElement type="text" label="user.firstName" id="firstName" value=$firstName required="true" inline=true size=$fbvStyles.size.SMALL}
		{fbvElement type="text" label="user.middleName" id="middleName" value=$middleName inline=true size=$fbvStyles.size.SMALL}
		{fbvElement type="text" label="user.lastName" id="lastName" value=$lastName required="true" inline=true size=$fbvStyles.size.MEDIUM}
	{/fbvFormSection}

	{fbvFormSection title="user.username" required="true"}
		{fbvElement type="text" label="user.register.usernameRestriction" id="username" required="true" value=$username size=$fbvStyles.size.MEDIUM inline=true}
		{fbvElement type="button" label="common.suggest" id="suggestUsernameButton" inline=true class="default"}
	{/fbvFormSection}

	{fbvFormSection title="user.email" required="true"}
		{fbvElement type="text" id="email" required="true" value=$email maxlength="90" size=$fbvStyles.size.MEDIUM}
	{/fbvFormSection}

	{fbvFormSection title="manager.reviewerSearch.interests" for="interests"}
		{fbvElement type="interests" id="interests" interests=$interests}
	{/fbvFormSection}

	{fbvFormSection title="user.affiliation"}
		{fbvElement type="text" multilingual="true" name="affiliation" id="affiliation" value=$affiliation size=$fbvStyles.size.LARGE}
	{/fbvFormSection}

	{include file="controllers/grid/users/reviewer/form/reviewerFormFooter.tpl"}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

	{fbvFormButtons submitText="editor.submission.addReviewer"}

</form>
