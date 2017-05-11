{**
 * templates/frontend/pages/userRegisterComplete.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief A landing page displayed to users upon successful registration
 *}
{include file="frontend/components/header.tpl"}

<div class="page page_register_complete">
	{include file="frontend/components/breadcrumbs.tpl" currentTitleKey=$pageTitle}
	<p>
		{translate key="user.login.registrationComplete.instructions"}
	</p>
	<ul class="registration_complete_actions">
		{if array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER), (array)$userRoles)}
			<li class="view_submissions">
				<a href="{url page="submissions"}">
					{translate key="user.login.registrationComplete.manageSubmissions"}
				</a>
			</li>
		{/if}
		<li class="new_submission">
			<a href="{url page="submission" op="wizard"}">
				{translate key="user.login.registrationComplete.newSubmission"}
			</a>
		</li>
		<li class="edit_profile">
			<a href="{url router=$smarty.const.ROUTE_PAGE page="user" op="profile"}">
				{translate key="user.editMyProfile"}
			</a>
		</li>
		<li class="browse">
			<a href="{url page="index"}">
				{translate key="user.login.registrationComplete.continueBrowsing"}
			</a>
		</li>
	</ul>
</div>

{include file="frontend/components/footer.tpl"}
