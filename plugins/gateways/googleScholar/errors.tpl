{**
 * errors.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Google Scholar error list
 *
 * $Id$
 *}

{assign var="pageTitle" value="plugins.gateways.googleScholar.displayName"}
{include file="common/header.tpl"}

<br/>

<h3>{translate key="plugins.gateways.googleScholar.errors"}</h3>

<ul>
{foreach from=$errors item=error}
	<li>{$error|escape}</li>
{foreachelse}
	<li>{translate key="plugins.gateways.googleScholar.errors.noErrors"}</li>
{/foreach}
</ul>

<a class="action" href="{plugin_url path="settings"}">{translate key="common.back"}</a>

{include file="common/footer.tpl"}
