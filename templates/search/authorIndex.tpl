{**
 * templates/search/authorIndex.tpl
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of published articles by author.
 *
 *}
{strip}
{assign var="pageTitle" value="search.authorIndex"}
{include file="common/header.tpl"}
{/strip}

<p>{foreach from=$alphaList item=letter}<a href="{url op="authors" searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter|escape}</strong>{else}{$letter|escape}{/if}</a> {/foreach}<a href="{url op="authors"}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

{translate|assign:'settingsString' key='authorList.sortOrder'}

<div id="authors">
{iterate from=authors item=author}
	{assign var=lastFirstLetter value=$firstLetter}
	{assign var=firstLetter value=$author->getLastName()|String_substr:0:1}

	{if $lastFirstLetter|lower != $firstLetter|lower}
			<div id="{$firstLetter|escape}">

		{php}
			/**
			 * Complex letter heading for authorIndex
			 *
			 * This code offers an improved handling of letter headings in the alphabetically
			 * sorted authorIndex. While the sorting order is coming from the database, this code allows
			 * to avoid new headings appearing when a last name's first letter contains diacritics.
			 *
			 * to do:
			 *  - adapt to fit in with OJS architecture
			 */

			/* Accessing variables from the smarty loop outside this php code. */
			$firstLetter = $this->get_template_vars('firstLetter');
			$lastFirstLetter = $this->get_template_vars('lastFirstLetter');
			$settingsString = $this->get_template_vars('settingsString');
			
			/* If no setting is defined for current locale, use default alphabet */
			if (strpos($settingsString, "#")) {
				$settingsString = "A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z";
			}

			/* Parse each setting and write all letters to be grouped under one heading in an array field */
			$explodedSettingsString = explode(',', $settingsString);
			$settingsArray = array();
			foreach ($explodedSettingsString as $currentGroup) {
				$explodedCurrentGroup = explode(' ', $currentGroup);
				$firstInGroup = $currentGroup[0];
				$settingsArray[$firstInGroup] = $explodedCurrentGroup;
			}

			/* If current first letter and last first letter are members of the same group in the settings
			   string, the letter heading will not be displayed. */
			$showLetterHeading = True;
			foreach ($settingsArray as $value) {
				if (in_array($firstLetter, $value)) {
					if (in_array($lastFirstLetter, $value)) {
						$showLetterHeading = False;
					}
				}
			}
			if  ($showLetterHeading) {
				echo "<h3>" . $firstLetter . "</h3>";
			}
		{/php}
			</div>
	{/if}

	{assign var=lastAuthorName value=$authorName}

	{assign var=authorAffiliation value=$author->getLocalizedAffiliation()}
	{assign var=authorCountry value=$author->getCountry()}

	{assign var=authorFirstName value=$author->getFirstName()}
	{assign var=authorMiddleName value=$author->getMiddleName()}
	{assign var=authorLastName value=$author->getLastName()}
	{assign var=authorName value="$authorLastName, $authorFirstName"}

	{if $authorMiddleName != ''}{assign var=authorName value="$authorName $authorMiddleName"}{/if}
	{strip}
		<a href="{url op="authors" path="view" firstName=$authorFirstName middleName=$authorMiddleName lastName=$authorLastName affiliation=$authorAffiliation country=$authorCountry}">{$authorName|escape}</a>
		{if $authorAffiliation}, {$authorAffiliation|escape}{/if}
		{if $authorCountry} ({$author->getCountryLocalized()}){/if}
	{/strip}
	<br />
{/iterate}
{if !$authors->wasEmpty()}
	<br />
	{page_info iterator=$authors}&nbsp;&nbsp;&nbsp;&nbsp;{page_links anchor="authors" iterator=$authors name="authors" searchInitial=$searchInitial}
{else}
{/if}
</div>
{include file="common/footer.tpl"}

