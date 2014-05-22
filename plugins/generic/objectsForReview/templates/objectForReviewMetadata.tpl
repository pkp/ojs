{**
 * @file plugins/generic/objectsForReview/templates/objectForReviewMetadata.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display public object for review metadata.
 *
 *}

{assign var=coverPage value=$objectForReview->getCoverPage()}
{if $coverPage}
	<div class="coverPage">
		<img src="{$coverPagePath|escape}{$coverPage.fileName|escape}"{if $coverPage.coverPageAltText != ''} alt="{$coverPage.coverPageAltText|escape}"{else} alt="{translate key="plugins.generic.objectsForReview.public.coverPage.altText"}"{/if}/>
	</div>
	<div class="coverPageDetails">
{else}
	<div class="details">
{/if}
	<h3>
	{if $ofrListing}
		<a href="{url page="objectsForReview" op="viewObjectForReview" path=$objectForReview->getId()}">{$objectForReview->getTitle()|escape}</a>
	{else}
		{$objectForReview->getTitle()|escape}
	{/if}
	</h3>

	<table class="data">
	{assign var=typeId value=$objectForReview->getReviewObjectTypeId()}
	{foreach from=$allReviewObjectsMetadata[$typeId] key=metadataId item=metadata}
		{* If this is the listing of all OFR, consider what metadata to display *}
		{* Esle, if this is an OFR view, display all metadata *}
		{if ($ofrListing && $metadata->getDisplay()) || (!$ofrListing)}
			{if $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_COVERPAGE}
				{* Ignore, because it is handled above *}
			{elseif $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_ROLE_DROP_DOWN_BOX}
				{foreach from=$objectForReview->getPersons() item=objectForReviewPerson}
					{assign var=roleId value=$objectForReviewPerson->getRole()}
					{assign var=metadataPossibleOptions value=$metadata->getLocalizedPossibleOptions()}
					<tr valign="top">
						<td class="label" width="20%">{$metadata->getLocalizedPossibleOptionContent($roleId)|escape}:</td>
						<td class="value" width="80%">{$objectForReviewPerson->getFullName()|escape}</td>
					</tr>
				{/foreach}
			{elseif in_array($metadata->getMetadataType(), $multipleOptionsTypes)}
				<tr valign="top">
					<td class="label" width="20%">{$metadata->getLocalizedName()|escape}:</td>
					<td class="value" width="80%">
						{assign var=possibleOptions value=$metadata->getLocalizedPossibleOptions()}
						{assign var=settingValues value=$objectForReview->getSetting($metadataId)}
						{foreach name=values from=$settingValues key=index item=optionId}
							{if $metadata->getLocalizedPossibleOptionContent($optionId)}{$metadata->getLocalizedPossibleOptionContent($optionId)|escape}{else}&mdash;{/if}{if not $smarty.foreach.values.last}; {/if}
						{foreachelse}&mdash;
						{/foreach}
					</td>
				</tr>
			{elseif $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_LANG_DROP_DOWN_BOX}
				<tr valign="top">
					<td class="label" width="20%">{$metadata->getLocalizedName()|escape}:</td>
					<td class="value" width="80%">{if $objectForReview->getLanguages()}{$objectForReview->getLanguages()|escape}{else}&mdash;{/if}</td>
				</tr>
			{else}
				{if $metadata->getKey() != REVIEW_OBJECT_METADATA_KEY_TITLE}
				<tr valign="top">
					<td class="label" width="20%">{$metadata->getLocalizedName()|escape}:</td>
					<td class="value" width="80%">
						{if $objectForReview->getSetting($metadataId)}
							{if $metadata->getMetadataType() == REVIEW_OBJECT_METADATA_TYPE_TEXTAREA}
								{$objectForReview->getSetting($metadataId)|strip_unsafe_html|nl2br}
							{else}
								{$objectForReview->getSetting($metadataId)|escape}
							{/if}
						{else}&mdash;
						{/if}
					</td>
				</tr>
				{/if}
			{/if} {* metadata types *}
		{/if} {* listing or OFR view *}
	{/foreach}
	</table>

	{if $isAuthor && !in_array($objectForReview->getId(), $authorAssignments)}
		<br />
		<br />
		<a href="{url page="author" op="requestObjectForReview" path=$objectForReview->getId()}" class="action">{translate key="plugins.generic.objectsForReview.author.requestObjectForReview}</a>
		<br />
		<br />
	{/if}
	</div>

