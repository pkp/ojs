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
	<li><a href="{$pageUrl}/manager/people/authors">{translate key="user.role.authors"}</a></li>
	<li><a href="{$pageUrl}/manager/people/readers">{translate key="user.role.readers"}</a></li>
</ul>
{/if}
<br />

<table width="100%">
<tr class="heading">
	<td>{translate key="user.username"}</td>
	<td>{translate key="user.name"}</td>
	<td></td>
	{if $roleId}
	<td></td>
	{/if}
</tr>
{foreach from=$users item=user}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$pageUrl}/manager/userProfile/{$user->getUserId()}">{$user->getUsername()}</a></td>
	<td width="100%">{$user->getFullName()}</td>
	<td><a href="{$pageUrl}/manager/editUser/{$user->getUserId()}" class="tableAction">{translate key="common.edit"}</a></td>
	{if $roleId}
	<td><a href="#" onclick="confirmAction('{$pageUrl}/manager/unEnroll?userId={$user->getUserId()}&amp;roleId={$roleId}', '{translate|escape:"javascript" key="manager.people.confirmUnenroll"}')" class="tableAction">{translate key="manager.people.unenroll"}</a></td>
	{/if}
</tr>
{foreachelse}
<tr>
<td colspan="{if $roleId}4{else}3{/if}" class="noResults">{translate key="manager.people.noneEnrolled"}</td>
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
