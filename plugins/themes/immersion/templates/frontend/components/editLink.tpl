{**
 * templates/frontend/components/editLink.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view the editorial team.
 *
 * @uses $page string Page to pass to the url constructor
 * @uses $op string Op to pass to the url constructor
 * @uses $path string Path to pass to the url constructor
 * @uses $anchor string Anchor to pass to the url constructor
 * @uses $sectionTitle string Translated name of section to be edited
 * @uses SectionTitleKey string A key that must be translated to get the
 *       $sectionTitle
 *}
{if in_array(ROLE_ID_MANAGER, (array) $userRoles)}

	{* Render the $sectionTitle if we only have a translation key *}
	{if $sectionTitleKey}
		{capture assign='sectionTitle'}{translate key=$sectionTitleKey}{/capture}
	{/if}

	{if $anchor}
		{capture assign="editLinkUrl"}{url page=$page op=$op path=$path anchor=$anchor}{/capture}
	{else}
        {capture assign="editLinkUrl"}{url page=$page op=$op path=$path}{/capture}
	{/if}

	<a href="{$editLinkUrl}" class="cmp_edit_link">
		{translate key="common.edit"}

		{* Screen readers need more context *}
		<span class="visually-hidden">
			{translate key="help.goToEditPage" sectionTitle=$sectionTitle}
		</span>
	</a>
{/if}
