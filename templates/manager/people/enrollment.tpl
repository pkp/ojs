{**
 * enrollment.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List enrolled users.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.people.enrollment"}
{include file="common/header.tpl"}

<h3>{translate key=$roleName}</h3>

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

<br />
{else}
<p><a href="{$pageUrl}/manager/people/all" class="action">{translate key="manager.people.allUsers"}</a></p>
{/if}

<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator"></td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="15%">{translate key="user.username"}</td>
		<td width="25%">{translate key="user.name"}</td>
		<td width="30%">{translate key="user.email"}</td>
		<td width="30%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator"></td>
	</tr>
	{foreach name="users" from=$users item=user}
	<tr class="{cycle values="row,rowAlt"}">
		<td><a href="{$pageUrl}/manager/userProfile/{$user->getUserId()}">{$user->getUsername()}</a></td>
		<td>{$user->getFullName()}</td>
		<td>{$user->getEmail()}</td>
		<td align="right">
			{if $roleId}
			<a href="{$pageUrl}/manager/unEnroll?userId={$user->getUserId()}&amp;roleId={$roleId}" onclick="return confirm('{translate|escape:"javascript" key="manager.people.confirmUnenroll"}')" class="action">{translate key="manager.people.unenroll"}</a>
			{/if}
			<a href="{$pageUrl}/manager/editUser/{$user->getUserId()}" class="action">{translate key="common.edit"}</a>
			<a href="{$pageUrl}/manager/signInAsUser/{$user->getUserId()}" class="action">{translate key="manager.people.signInAs"}</a>
		</td>
	</tr>
	<tr>
		<td colspan="4" class="{if $smarty.foreach.users.last}end{/if}separator"></td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="4" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator"></td>
	</tr>
	{/foreach}
</table>

<p>
{if $roleId}
<a href="{$pageUrl}/manager/enrollSearch/{$roleId}" class="action">{translate key="manager.people.enroll"}</a> |
{/if}
<a href="{$pageUrl}/manager/createUser" class="action">{translate key="manager.people.createUser"}</a>
</p>

{include file="common/footer.tpl"}
