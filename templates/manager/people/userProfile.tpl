{**
 * userProfile.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display user profile.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.people"}
{include file="common/header.tpl"}

<div class="blockTitle">{translate key="user.profile"}: {$user->getFullName()|escape} <a href="{$pageUrl}/manager/editUser/{$user->getUserId()}" class="tableButton">{translate key="common.edit"}</a></div>
<div class="block">
<table class="form">
<tr>
	<td class="formLabel">{translate key="user.username"}:</td>
	<td class="formField">{$user->getUsername()|escape}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="user.firstName"}:</td>
	<td class="formField">{$user->getFirstName()|escape}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="user.middleName"}:</td>
	<td class="formField">{$user->getMiddleName()|escape}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="user.lastName"}:</td>
	<td class="formField">{$user->getLastName()|escape}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="user.affiliation"}:</td>
	<td class="formField">{$user->getAffiliation()|escape}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="user.email"}:</td>
	<td class="formField"><a href="mailto:{$user->getEmail()|escape}">{$user->getEmail()|escape}</a></td>
</tr>
<tr>
	<td class="formLabel">{translate key="user.phone"}:</td>
	<td class="formField">{$user->getPhone()|escape}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="user.fax"}:</td>
	<td class="formField">{$user->getFax()|escape}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="user.mailingAddress"}:</td>
	<td class="formField">{$user->getMailingAddress()|escape|nl2br}</td>
</tr>
<tr>
	<td class="formLabel">{translate key="user.biography"}:</td>
	<td class="formField">{$user->getBiography()|escape|nl2br}</td>
</tr>
</table>
</div>

<br />

<div class="blockTitle">{translate key="manager.people.enrollment"}</div>
<div class="block">
<ul>
{section name=role loop=$userRoles}
	<li><a href="{$pageUrl}/{$userRoles[role]->getRolePath()}">{translate key=$userRoles[role]->getRoleName()}</a> <a href="#" onclick="confirmAction('{$pageUrl}/manager/unEnroll?userId={$user->getUserId()}&amp;roleId={$userRoles[role]->getRoleId()}', '{translate|escape:"javascript" key="manager.people.confirmUnenroll"}')" class="tableButton">{translate key="manager.people.unenroll"}</a></li>
{/section}
</ul>
</div>

&#187; <a href="{$pageUrl}/manager/people/all">{translate key="manager.people.allUsers"}</a>

{include file="common/footer.tpl"}
