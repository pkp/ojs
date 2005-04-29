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

<script type="text/javascript">
{literal}

function checkAll (allOn) {
	var elements = document.submit.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].name = 'bcc') {
			elements[i].checked = allOn;
		}
	}
}
{/literal}
</script>

<h3>{translate key=$roleName}</h3>
{assign var="start" value="A"|ord}
<form name="submit" method="post" action="{$pageUrl}/manager/people/{$roleSymbolic}">
	<select name="roleSymbolic" class="selectMenu">
		<option {if $roleSymbolic=='all'}selected {/if}value="all">{translate key="manager.people.allUsers"}</option>
		<option {if $roleSymbolic=='managers'}selected {/if}value="managers">{translate key="user.role.managers"}</option>
		<option {if $roleSymbolic=='editors'}selected {/if}value="editors">{translate key="user.role.editors"}</option>
		<option {if $roleSymbolic=='sectionEditors'}selected {/if}value="sectionEditors">{translate key="user.role.sectionEditors"}</option>
		<option {if $roleSymbolic=='layoutEditors'}selected {/if}value="layoutEditors">{translate key="user.role.layoutEditors"}</option>
		<option {if $roleSymbolic=='copyeditors'}selected {/if}value="copyeditors">{translate key="user.role.copyeditors"}</option>
		<option {if $roleSymbolic=='proofreaders'}selected {/if}value="proofreaders">{translate key="user.role.proofreaders"}</option>
		<option {if $roleSymbolic=='reviewers'}selected {/if}value="reviewers">{translate key="user.role.reviewers"}</option>
		<option {if $roleSymbolic=='authors'}selected {/if}value="authors">{translate key="user.role.authors"}</option>
		<option {if $roleSymbolic=='readers'}selected {/if}value="readers">{translate key="user.role.readers"}</option>
	</select>
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains">{translate key="form.contains"}</option>
		<option value="is">{translate key="form.is"}</option>
	</select>
	<input type="text" size="10" name="search" class="textField" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{section loop=26 name=letters}<a href="{$pageUrl}/manager/people/{$roleSymbolic}?search_initial={$smarty.section.letters.index+$start|chr}">{$smarty.section.letters.index+$start|chr}</a> {/section}</p>

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

<form action="{$requestPageUrl}/email" method="post" name="submit">
<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">&nbsp;</td>
		<td width="12%">{translate key="user.username"}</td>
		<td width="20%">{translate key="user.name"}</td>
		<td width="23%">{translate key="user.email"}</td>
		<td width="40%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	{foreach name=users from=$users item=user}
	{assign var=userExists value=1}
	<tr valign="top">
		<td><input type="checkbox" name="bcc[]" value="{$user->getEmail()|escape}"/></td>
		<td><a href="{$pageUrl}/manager/userProfile/{$user->getUserId()}">{$user->getUsername()}</a></td>
		<td>{$user->getFullName()}</td>
		<td>
			{assign var=emailString value="`$user->getFullName()` <`$user->getEmail()`>"}
			{assign var=emailStringEscaped value=$emailString|escape:"url"}
			<nobr>{$user->getEmail()}&nbsp;{icon name="mail" url="`$requestPageUrl`/email?to[]=$emailStringEscaped"}</nobr>
		</td>
		<td align="right">
			<nobr>
			{if $roleId}
			<a href="{$pageUrl}/manager/unEnroll?userId={$user->getUserId()}&amp;roleId={$roleId}" onclick="return confirm('{translate|escape:"javascript" key="manager.people.confirmUnenroll"}')" class="action">{translate key="manager.people.unenroll"}</a>
			{/if}
			<a href="{$pageUrl}/manager/editUser/{$user->getUserId()}" class="action">{translate key="common.edit"}</a>
			{if $thisUser->getUserId() != $user->getUserId()}
				<a href="{$pageUrl}/manager/signInAsUser/{$user->getUserId()}" class="action">{translate key="manager.people.signInAs"}</a>
				{if !$roleId}<a href="{$pageUrl}/manager/removeUser/{$user->getUserId()}" onclick="return confirm('{translate|escape:"javascript" key="manager.people.confirmRemove"}')" class="action">{translate key="manager.people.remove"}</a>{/if}
				{if $user->getDisabled()}
					<a href="{$pageUrl}/manager/enableUser/{$user->getUserId()}" class="action">{translate key="manager.people.enable"}</a>
				{else}
					<a href="{$pageUrl}/manager/disableUser/{$user->getUserId()}" onclick="return confirm('{translate|escape:"javascript" key="manager.people.confirmDisable"}')" class="action">{translate key="manager.people.disable"}</a>
				{/if}
			{/if}
			</nobr>
		</td>
	</tr>
	<tr>
		<td colspan="5" class="{if $smarty.foreach.users.last}end{/if}separator">&nbsp;</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="5" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
	{/foreach}
</table>

{if $userExists}
	<p><input type="submit" value="{translate key="email.compose"}" class="button defaultButton"/>&nbsp;<input type="button" value="{translate key="common.selectAll"}" class="button" onClick="checkAll(true)"/>&nbsp;<input type="button" value="{translate key="common.selectNone"}" class="button" onClick="checkAll(false)"/></p>
{/if}
</form>

{if $roleId}
<a href="{$pageUrl}/manager/enrollSearch/{$roleId}" class="action">{translate key="manager.people.enrollExistingUser"}</a> |
{/if}
<a href="{$pageUrl}/manager/createUser" class="action">{translate key="manager.people.createUser"}</a>

{include file="common/footer.tpl"}
