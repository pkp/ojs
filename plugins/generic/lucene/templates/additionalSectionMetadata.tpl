{**
 * plugins/generic/lucene/templates/additionalSectionMetadata.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A template to be included via Templates::Manager::Sections::SectionForm::AdditionalMetadata hook.
 *}
<tr valign="top">
	<td class="label">{fieldLabel name="rankingBoost" key="plugins.generic.lucene.sectionForm.rankingBoost"}</td>
	<td class="value">
		<span class="instruct">{translate key="plugins.generic.lucene.sectionForm.rankingBoostInstructions"}</span><br />
		<p><select name="rankingBoostOption" size="1" id="rankingBoostOption" class="selectMenu">
			{html_options options=$rankingBoostOptions selected=$rankingBoostOption}
		</select></p>
	</td>
</tr>
