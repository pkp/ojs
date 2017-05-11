{**
 * controllers/tab/settings/appearance/form/favicon.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Form fields for uploading a journal favicon
 *
 *}
{assign var="uploadImageFieldId" value=$uploadImageLinkActions.favicon->getId()}
{fbvFormSection for="$uploadImageFieldId" label="manager.setup.favicon" description="manager.setup.faviconDescription"}
	<div id="favicon">
		{$imagesViews.favicon}
	</div>
	<div id="{$uploadImageFieldId}" class="pkp_linkActions">
		{include file="linkAction/linkAction.tpl" action=$uploadImageLinkActions.favicon contextId="appearanceForm"}
	</div>
{/fbvFormSection}
