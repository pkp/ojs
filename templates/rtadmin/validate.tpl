{**
 * validate.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Validate URLs for searches.
 *
 * $Id$
 *}

{assign var="pageTitle" value="rt.admin.validateUrls"}
{include file="common/header.tpl"}

<p>{translate key="rt.admin.validateUrls.description"}</p>

{iterate from=versions item=version}
	<h3>{$version->getTitle()|escape}</h3>
	<ul>
	{foreach from=$version->getContexts() item=context}
		<li>{$context->getTitle()|escape}
		{assign var=errors value=0}
		{foreach from=$context->getSearches() item=search}
			{assign var=errors value=$search|validate_url:$errors}.
		{/foreach}
		{foreach from=$errors item=error}
			<br />
			{translate key="rt.admin.validateUrls.urlIsInvalid" url=$error.url|truncate:50|escape}&nbsp;&nbsp;<a href="{url op="editSearch" path=$version->getVersionId()|to_array:$context->getContextId():$error.id}" class="action">{translate key="common.edit"}</a>
		{foreachelse}
			{translate key="rt.admin.validateUrls.ok"}
		{/foreach}
		</li>
	{/foreach}
	</ul>
{/iterate}
<p>{translate key="rt.admin.validateUrls.complete"}</p>

{include file="common/footer.tpl"}
