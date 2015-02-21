{**
 * plugins/generic/dataverse/templates/dataAvailabilityPolicy.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display journal's data availability policy in editorial policies
 *
 *}
{strip}
	{assign var="pageTitle" value="plugins.generic.dataverse.dataAvailabilityPolicy.title"}
	{include file="common/header.tpl"}
{/strip}

<div>
	{$dataAvailabilityPolicy|strip_unsafe_html}
</div>

{include file="common/footer.tpl"}
