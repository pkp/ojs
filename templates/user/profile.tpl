{**
 * profile.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User profile form.
 *
 * $Id$
 *}

{assign var="pageTitle" value="user.profile.editProfile"}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/user/saveProfile">
{translate key="Username"}: <b>{$username}</b>
<br />
<td class="formLabel">{formLabel name="firstName"}{translate key="First name"}{/formLabel}</td>: <input type="text" name="firstName" value="{$firstName|escape}" />
<br />
<td class="formLabel">{formLabel name="middleName"}{translate key="Middle Name"}{/formLabel}</td>: <input type="text" name="middleName" value="{$middleName|escape}" />
<br />
<td class="formLabel">{formLabel name="lastName"}{translate key="Last name"}{/formLabel}</td>: <input type="text" name="lastName" value="{$lastName|escape}" /><br />
{translate key="Affiliation"}: <input type="text" name="affiliation" value="{$affiliation|escape}" />
<br />
<td class="formLabel">{formLabel name="email"}{translate key="Email"}{/formLabel}</td>: <input type="text" name="email" value="{$email|escape}" />
<br />
{translate key="Phone"}: <input type="text" name="phone" value="{$phone|escape}" />
<br />
{translate key="Fax"}: <input type="text" name="fax" value="{$fax|escape}" />
<br />
{translate key="Mailing address"}: <input type="text" name="mailingAddress" value="{$mailingAddress|escape}" />
<br />
{translate key="Biography"}: <input type="text" name="biography" value="{$biography|escape}" />
<br />
<input type="submit" value="Save" /> <input type="button" value="Cancel" onclick="document.location.href='{$pageUrl}/user'" />
</form>

{include file="common/footer.tpl"}
