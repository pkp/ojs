{**
 * searchUsers.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Search form for enrolled users.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.people.enrollment"}
{include file="common/header.tpl"}

<form name="searchUsers" action="{$pageUrl}/manager/enroll" method="post">
<input type="hidden" name="roleId" value="{$roleId}" />

<select name="searchField">
	<option value="username">{translate key="user.username"}</option>
	<option value="lastName">{translate key="user.lastName"}</option>
	<option value="firstName">{translate key="user.firstName"}</option>
</select>

<select name="searchMatch">
	<option value="contains">{translate key="form.contains"}</option>
	<option value="is">{translate key="form.is"}</option>
</select>

<input type="text" name="searchValue" size="30" maxlength="60" class="textField" />

<br />

<input type="submit" value="{translate key="navigation.search"}" class="formButton" />
</form>

<script type="text/javascript">document.searchUsers.searchValue.focus();</script>

{include file="common/footer.tpl"}
