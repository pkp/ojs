{**
 * enrollment.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List enrolled users.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.people.enrollment"}
{assign var="pageId" value="manager.people.enrollment"}
{include file="common/header.tpl"}

<div class="subTitle">{translate key=$roleName}</div>

{if not $roleId}
<ul>
	<li><a href="{$pageUrl}/manager/people/managers">{translate key="user.role.managers"}</a></li>
	<li><a href="{$pageUrl}/manager/people/editors">{translate key="user.role.editors"}</a></li>
	<li><a href="{$pageUrl}/manager/people/sectionEditors">{translate key="user.role.sectionEditors"}</a></li>
	<li><a href="{$pageUrl}/manager/people/layoutEditors">{translate key="user.role.layoutEditors"}</a></li>
	<li><a href="{$pageUrl}/manager/people/copyeditors">{translate key="user.role.copyeditors"}</a></li>
	<li><a href="{$pageUrl}/manager/people/proofreaders">{translate key="user.role.proofreaders"}</a></li>
	<li><a href="{$pageUrl}/manager/people/reviewers">{translate key="user.role.reviewers"}</a></li>
	<li><a href="{$pageUrl}/manager/people/authors">{translate key="user.role.authors"}</a></li>
	<li><a href="{$pageUrl}/manager/people/readers">{translate key="user.role.readers"}</a></li>
</ul>
{/if}
<br />

<table class="rightPadded">
<tr class="heading">
	<th>{translate key="user.username"}</th>
	<th>{translate key="user.name"}</th>
	{if $isReviewer}
	{if $rateReviewerOnTimeliness}<th>{translate key="reviewer.averageTimeliness"}</th>{/if}
	{if $rateReviewerOnQuality}<th>{translate key="reviewer.averageQuality"}</th>{/if}
	{/if}
	<th></th>
	{if $roleId}
	<th></th>
	{/if}
	<td></td>
</tr>
{foreach from=$users item=user}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$pageUrl}/manager/userProfile/{$user->getUserId()}">{$user->getUsername()}</a></td>
	<td>{$user->getFullName()}</td>
	{if $isReviewer}
	{assign var="userId" value=$user->getUserId()}
	{if $rateReviewerOnTimeliness}<td>
		{if $timelinessRatings[$userId].count}
			{$timelinessRatings[$userId].average|string_format:"%.1f"} / 5
		{else}{translate key="reviewer.notRated"}{/if}
	</td>{/if}
	{if $rateReviewerOnQuality}<td>
		{if $qualityRatings[$userId].count}
			{$qualityRatings[$userId].average|string_format:"%.1f"} / 5
		{else}{translate key="reviewer.notRated"}{/if}
	</td>{/if}
	{/if}
	<td><a href="{$pageUrl}/manager/editUser/{$user->getUserId()}" class="tableAction">{translate key="common.edit"}</a></td>
	{if $roleId}
	<td><a href="{$pageUrl}/manager/unEnroll?userId={$user->getUserId()}&amp;roleId={$roleId}" onclick="return confirm('{translate|escape:"javascript" key="manager.people.confirmUnenroll"}')" class="tableAction">{translate key="manager.people.unenroll"}</a></td>
	{/if}
	<td><nobr><a href="{$pageUrl}/manager/signInAsUser/{$user->getUserId()}" class="tableAction">{translate key="manager.people.signInAs"}</a></nobr></td>
</tr>
{foreachelse}
<tr>
<td colspan="{if $roleId}5{else}4{/if}" class="noResults">{translate key="manager.people.noneEnrolled"}</td>
</tr>
{/foreach}
</table>

{if $roleId}
<a href="{$pageUrl}/manager/enrollSearch/{$roleId}" class="tableButton">{translate key="manager.people.enroll"}</a>
<br /><br />
&#187; <a href="{$pageUrl}/manager/people/all">{translate key="manager.people.allUsers"}</a>
{else}
<a href="{$pageUrl}/manager/createUser" class="tableButton">{translate key="manager.people.createUser"}</a>
{/if}

{include file="common/footer.tpl"}
