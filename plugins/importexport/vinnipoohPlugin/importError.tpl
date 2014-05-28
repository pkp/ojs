{**
 * plugins/importexport/vinnipoohPlugin/importError.tpl
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2003-2013 John Willinsky
 * Copyright (c) 2013-2014 Artem Gusarenko Ufa State Aviation Technical University (redactormail@gmail.com)
 * Copyright (c) 2013-2014 Valeriy Mironov Ufa State Aviation Technical University
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display an error message for an aborted import process.
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.vinnipoohPlugin.import.error"}
{include file="common/header.tpl"}
{/strip}
<div id="importError">
<p>{translate key="plugins.importexport.vinnipoohPlugin.import.error.description"}</p>
{if $error}
	<!-- A single error occurred. -->
	<p>{translate key=$error}</p>
{else}
	<!-- Multiple errors occurred. List them. -->
	<ul>
	{foreach from=$errors item=error}
		<li>{translate key=$error[0] params=$error[1]}</li>
	{/foreach}
	</ul>
{/if}
</div>
{include file="common/footer.tpl"}
