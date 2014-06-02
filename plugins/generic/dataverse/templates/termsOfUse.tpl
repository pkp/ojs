{**
 * plugins/generic/dataverse/templates/termsOfUse.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display terms of use of Dataverse configured for journal
 *
 *}
{strip}
{assign var=pageTitle value="plugins.generic.dataverse.termsOfUse.title"}
{include file="rt/header.tpl"}
{/strip}
<div>
	{$termsOfUse|strip_unsafe_html}
</div>
<div class="separator"></div>	
{include file="rt/footer.tpl"}
