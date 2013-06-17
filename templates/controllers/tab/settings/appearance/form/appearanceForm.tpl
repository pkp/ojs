{**
 * controllers/tab/settings/appearance/form/appearanceForm.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Website appearance management form.
 *
 *}
{capture assign="additionalHomepageContent"}
	{* Journal thumbnail *}
	<div {if $wizardMode}class="pkp_form_hidden"{/if}>
		{fbvFormArea id="thumbnailArea" title="manager.setup.journalThumbnail" class="border"}
			{fbvFormSection description="manager.setup.journalThumbnail.description"}
				<div id="{$uploadImageLinkActions.journalThumbnail->getId()}" class="pkp_linkActions">
					{include file="linkAction/linkAction.tpl" action=$uploadImageLinkActions.journalThumbnail contextId="appearanceForm"}
				</div>
				<div id="journalThumbnail">
					{$imagesViews.journalThumbnail}
				</div>
			{/fbvFormSection}
		{/fbvFormArea}
	</div>
{/capture}
{include file="core:controllers/tab/settings/appearance/form/appearanceForm.tpl"}
