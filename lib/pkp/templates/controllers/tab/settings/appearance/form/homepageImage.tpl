{**
 * controllers/tab/settings/appearance/form/homepageImage.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Form fields for uploading a frontend homepage image
 *
 *}
{assign var="uploadImageFieldId" value=$uploadImageLinkActions.homepageImage->getId()}
{fbvFormSection for="$uploadImageFieldId" label="manager.setup.homepageImage" description="manager.setup.homepageImageDescription"}
	<div id="homepageImage">
		{$imagesViews.homepageImage}
	</div>
	<div id="{$uploadImageFieldId}" class="pkp_linkActions">
		{include file="linkAction/linkAction.tpl" action=$uploadImageLinkActions.homepageImage contextId="appearanceForm"}
	</div>
{/fbvFormSection}
