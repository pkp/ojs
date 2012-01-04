{**
 * loginChangePassword.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to change a user's password in order to login.
 *
 * $Id$
 *}
{strip}
{assign var="passwordLengthRestrictionLocaleKey" value="user.register.passwordLengthRestriction"}
{include file="core:user/loginChangePassword.tpl"}
{/strip}
