{**
 * current.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Current.
 *
 * $Id$
 *}

{assign var="hierarchyCurrentTitle" value=$issueIdentification}
{assign var="pageTitle" value=$issueTitle}
{assign var="currentUrl" value="$pageUrl/issue/current"}
{include file="issue/header.tpl"}

{include file="issue/issue.tpl"}

{include file="common/footer.tpl"}
