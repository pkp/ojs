{**
 * templates/frontend/components/registrationFormContexts.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display role selection for all of the journals/presses on this site
 *
 * @uses $contexts array List of journals/presses on this site that have enabled registration
 * @uses $readerUserGroups array Associative array of user groups with reader
 *  permissions in each context.
 * @uses $authorUserGroups array Associative array of user groups with author
 *  permissions in each context.
 * @uses $reviewerUserGroups array Associative array of user groups with reviewer
 *  permissions in each context.
 * @uses $userGroupIds array List group IDs this user is assigned
 *}

{* Only display the context role selection when registration is taking place
   outside of the context of any one journal/press. *}
{if !$currentContext}

	{* Allow users to register for any journal/press on this site *}
	<fieldset name="contexts">
		<legend>
			{translate key="user.register.contextsPrompt"}
		</legend>
		<div class="fields">
			<div id="contextOptinGroup" class="context_optin">
				<ul class="registration-context">
					{foreach from=$contexts item=context}
						{assign var=contextId value=$context->getId()}
						{assign var=isSelected value=false}
						<li class="context">
							{capture assign="contextUrl"}{url router=$smarty.const.ROUTE_PAGE context=$context->getPath()}{/capture}
							<a href="{$contextUrl|escape}" class="registration-context__name">
								{$context->getLocalizedName()}
							</a class="name">
							<fieldset class="registration-context__roles">
								<legend>
									{translate key="user.register.otherContextRoles"}
								</legend>
								<div class="custom-control custom-checkbox context-checkbox">
									{foreach from=$readerUserGroups[$contextId] item=userGroup}
										{if $userGroup->getPermitSelfRegistration()}
											{assign var="userGroupId" value=$userGroup->getId()}
											<input type="checkbox" class="custom-control-input" id="readerGroup[{$userGroupId}]" name="readerGroup[{$userGroupId}]"{if in_array($userGroupId, $userGroupIds)} checked="checked"{/if}>
											<label for="readerGroup[{$userGroupId}]" class="custom-control-label">
												{$userGroup->getLocalizedName()}
											</label>
											{if in_array($userGroupId, $userGroupIds)}
												{assign var=isSelected value=true}
											{/if}
										{/if}
									{/foreach}
								</div>
								<div class="custom-control custom-checkbox context-checkbox">
									{foreach from=$reviewerUserGroups[$contextId] item=userGroup}
										{if $userGroup->getPermitSelfRegistration()}
											{assign var="userGroupId" value=$userGroup->getId()}
											<input type="checkbox" class="custom-control-input" id="reviewerGroup[{$userGroupId}]" name="reviewerGroup[{$userGroupId}]"{if in_array($userGroupId, $userGroupIds)} checked="checked"{/if}>
											<label for="reviewerGroup[{$userGroupId}]" class="custom-control-label">
												{$userGroup->getLocalizedName()}
											</label>
											{if in_array($userGroupId, $userGroupIds)}
												{assign var=isSelected value=true}
											{/if}
										{/if}
									{/foreach}
								</div>
							</fieldset>
							{* Require the user to agree to the terms of the context's privacy policy *}
							{if !$enableSiteWidePrivacyStatement && $context->getSetting('privacyStatement')}
								<div class="custom-control custom-checkbox context_privacy {if $isSelected}context_privacy_visible{/if}">
									<input type="checkbox" class="custom-control-input" name="privacyConsent[{$contextId}]" id="privacyConsent[{$contextId}]" value="1"{if $privacyConsent[$contextId]} checked="checked"{/if}>
									<label for="privacyConsent[{$contextId}]" class="custom-control-label">
										{capture assign="privacyUrl"}{url router=$smarty.const.ROUTE_PAGE context=$context->getPath() page="about" op="privacy"}{/capture}
										{translate key="user.register.form.privacyConsentThisContext" privacyUrl=$privacyUrl}
									</label>
								</div>
							{/if}
						</li>
					{/foreach}
				</ul>
			</div>
		</div>
	</fieldset>
{/if}
