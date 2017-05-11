{**
 * templates/frontend/pages/userRegister.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User registration form.
 *
 * @uses $primaryLocale string The primary locale for this journal/press
 *}
{include file="frontend/components/header.tpl" pageTitle="user.register"}

<div class="page page_register">
	{include file="frontend/components/breadcrumbs.tpl" currentTitleKey="user.register"}

	<form class="cmp_form register" id="register" method="post" action="{url op="registerUser"}">
		{csrf}

		{if $source}
			<input type="hidden" name="source" value="{$source|escape}" />
		{/if}

		{include file="common/formErrors.tpl"}

		{include file="frontend/components/registrationForm.tpl"}

		{* When a user is registering with a specific journal *}
		{if $currentContext}

			{* Users are opted into the Reader and Author roles in the current
			   journal/press by default. See RegistrationForm::initData() *}
			{assign var=contextId value=$currentContext->getId()}
			{foreach from=$readerUserGroups[$contextId] item=userGroup}
				{if in_array($userGroup->getId(), $userGroupIds)}
					{assign var="userGroupId" value=$userGroup->getId()}
					<input type="hidden" name="readerGroup[{$userGroupId}]" value="1">
				{/if}
			{/foreach}
			{foreach from=$authorUserGroups[$contextId] item=userGroup}
				{if in_array($userGroup->getId(), $userGroupIds)}
					{assign var="userGroupId" value=$userGroup->getId()}
					<input type="hidden" name="authorGroup[{$userGroupId}]" value="1">
				{/if}
			{/foreach}

			{* Allow the user to sign up as a reviewer *}
			{assign var=userCanRegisterReviewer value=0}
			{foreach from=$reviewerUserGroups[$contextId] item=userGroup}
				{if $userGroup->getPermitSelfRegistration()}
					{assign var=userCanRegisterReviewer value=$userCanRegisterReviewer+1}
				{/if}
			{/foreach}
			{if $userCanRegisterReviewer}
				<fieldset class="reviewer">
					<legend>
						{translate key="user.reviewerPrompt"}
					</legend>
					<div class="fields">
						<div id="reviewerOptinGroup" class="optin">
							{foreach from=$reviewerUserGroups[$contextId] item=userGroup}
								{if $userGroup->getPermitSelfRegistration()}
									<label>
										{assign var="userGroupId" value=$userGroup->getId()}
										<input type="checkbox" name="reviewerGroup[{$userGroupId}]" value="1"{if in_array($userGroupId, $userGroupIds)} checked="checked"{/if}>
										{translate key="user.reviewerPrompt.userGroup" userGroup=$userGroup->getLocalizedName()}
									</label>
								{/if}
							{/foreach}
						</div>

						<div id="reviewerInterests" class="reviewer_interests">
							{*
							 * This container will be processed by the tag-it jQuery
							 * plugin. In order for it to work, your theme will need to
							 * load the jQuery tag-it plugin and initialize the
							 * component.
							 *
							 * Two data attributes are added which are not a default
							 * feature of the plugin. These are converted into options
							 * when the plugin is initialized on the element.
							 *
							 * See: /plugins/themes/default/js/main.js
							 *
							 * `data-field-name` represents the name used to POST the
							 * interests when the form is submitted.
							 *
							 * `data-autocomplete-url` is the URL used to request
							 * existing entries from the server.
							 *
							 * @link: http://aehlke.github.io/tag-it/
							 *}
							<div class="label">
								{translate key="user.interests"}
							</div>
							<ul class="interests tag-it" data-field-name="interests[]" data-autocomplete-url="{url|escape router=$smarty.const.ROUTE_PAGE page='user' op='getInterests'}">
								{foreach from=$interests item=interest}
									<li>{$interest|escape}</li>
								{/foreach}
							</ul>
						</div>
					</div>
				</fieldset>
			{/if}
		{/if}

		{include file="frontend/components/registrationFormContexts.tpl"}

		{* When a user is registering for no specific journal, allow them to
		   enter their reviewer interests *}
		{if !$currentContext}
			<fieldset class="reviewer_nocontext_interests">
				<legend>
					{translate key="user.register.noContextReviewerInterests"}
				</legend>
				<div class="fields">
					<div class="reviewer_nocontext_interests">
						{* See comment for .tag-it above *}
						<ul class="interests tag-it" data-field-name="interests[]" data-autocomplete-url="{url|escape router=$smarty.const.ROUTE_PAGE page='user' op='getInterests'}">
							{foreach from=$interests item=interest}
								<li>{$interest|escape}</li>
							{/foreach}
						</ul>
					</div>
				</div>
			</fieldset>
		{/if}

		{* recaptcha spam blocker *}
		{if $reCaptchaHtml}
			<fieldset class="recaptcha_wrapper">
				<div class="fields">
					<div class="recaptcha">
						{$reCaptchaHtml}
					</div>
				</div>
			</fieldset>
		{/if}

		<div class="buttons">
			<button class="submit" type="submit">
				{translate key="user.register"}
			</button>

			{url|assign:"rolesProfileUrl" page="user" op="profile" path="roles"}
			<a href="{url page="login" source=$rolesProfileUrl}" class="login">{translate key="user.login"}</a>
		</div>
	</form>

</div><!-- .page -->

{include file="frontend/components/footer.tpl"}
