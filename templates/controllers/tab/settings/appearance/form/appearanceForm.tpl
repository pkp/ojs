{**
 * controllers/tab/settings/appearance/form/appearanceForm.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Website appearance management form.
 *
 *}

{* In wizard mode, these fields should be hidden *}
{if $wizardMode}
	{assign var="wizard_class" value="is_wizard_mode"}
{else}
	{assign var="wizard_class" value=""}
{/if}

{capture assign="additionalHomepageContent"}
	{* Journal thumbnail *}
	{assign var="journal_thumbnail_field_id" value=$uploadImageLinkActions.journalThumbnail->getId()}
	{fbvFormArea id="thumbnailArea" class="$wizard_class"}
		{fbvFormSection for=$journal_thumbnail_field_id label="manager.setup.journalThumbnail" description="manager.setup.journalThumbnail.description"}
			<div id="{$uploadImageLinkActions.journalThumbnail->getId()}" class="pkp_linkActions">
				{include file="linkAction/linkAction.tpl" action=$uploadImageLinkActions.journalThumbnail contextId="appearanceForm"}
			</div>
			<div id="journalThumbnail">
				{$imagesViews.journalThumbnail}
			</div>
		{/fbvFormSection}
	{/fbvFormArea}
{/capture}
{include file="core:controllers/tab/settings/appearance/form/appearanceForm.tpl"}
