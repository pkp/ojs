{**
 * controllers/tab/settings/appearance/form/stylesheet.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Form fields for uploading a custom frontend stylesheet
 *
 *}
{assign var="stylesheetFieldId" value=$uploadCssLinkAction->getId()}
{fbvFormSection label="manager.setup.useStyleSheet" for=$stylesheetFieldId description="manager.setup.styleSheetDescription"}
	<div id="styleSheet">
		{$styleSheetView}
	</div>
	<div id="{$stylesheetFieldId}" class="pkp_linkActions">
		{include file="linkAction/linkAction.tpl" action=$uploadCssLinkAction contextId="appearanceForm"}
	</div>
{/fbvFormSection}
