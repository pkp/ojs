{**
 * searchUsers.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Search form for enrolled users.
 *
 * $Id$
 *
 *
 *
 * NOTE -- This template is used by the (section)Editor for enrolling
 * reviewers directly from the "select reviewer" template in the submission
 * review section. Some conditional function here is unused by the Manager
 * component but is REQUIRED for the (section)Editor component. (Specifically,
 * the generation of the form target URL.)
 *
 *
 *}

{assign var="pageTitle" value="manager.people.enrollment"}
{include file="common/header.tpl"}

<form name="searchUsers" action="{$pageUrl}/{$handlerName}/enroll{if $articleId}/{$articleId}{/if}" method="post">
<input type="hidden" name="roleId" value="{$roleId}" />

<select name="searchField">
	{html_options_translate options=$fieldOptions}
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
