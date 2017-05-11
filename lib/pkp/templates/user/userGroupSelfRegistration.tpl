{**
 * templates/user/userGroupSelfRegistration.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User group self-registration profile/registration form chunk.
 * Requires variables:
 * $context: The context to show roles available for self-registration
 *}
{assign var=contextId value=$context->getId()}
{foreach from=$readerUserGroups[$contextId] item=userGroup}
	{assign var="userGroupId" value=$userGroup->getId()}
	{if in_array($userGroup->getId(), $userGroupIds)}
		{assign var="checked" value=true}
	{else}
		{assign var="checked" value=false}
	{/if}
	{if $userGroup->getPermitSelfRegistration()}
		{fbvElement type="checkbox" id="readerGroup-$userGroupId" name="readerGroup[$userGroupId]" checked=$checked label=$userGroup->getLocalizedName() translate=false}
	{/if}
{/foreach}
{foreach from=$authorUserGroups[$contextId] item=userGroup}
	{assign var="userGroupId" value=$userGroup->getId()}
	{if in_array($userGroup->getId(), $userGroupIds)}
		{assign var="checked" value=true}
	{else}
		{assign var="checked" value=false}
	{/if}
	{if $userGroup->getPermitSelfRegistration()}
		{fbvElement type="checkbox" id="authorGroup-$userGroupId" name="authorGroup[$userGroupId]" checked=$checked label=$userGroup->getLocalizedName() translate=false}
	{/if}
{/foreach}
{foreach from=$reviewerUserGroups[$contextId] item=userGroup}
	{assign var="userGroupId" value=$userGroup->getId()}
	{if in_array($userGroup->getId(), $userGroupIds)}
		{assign var="checked" value=true}
	{else}
		{assign var="checked" value=false}
	{/if}
	{if $userGroup->getPermitSelfRegistration()}
		{fbvElement type="checkbox" id="reviewerGroup-$userGroupId" name="reviewerGroup[$userGroupId]" checked=$checked label=$userGroup->getLocalizedName() translate=false}
	{/if}
{/foreach}
