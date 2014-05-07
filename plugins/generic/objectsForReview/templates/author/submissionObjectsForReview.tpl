{**
 * @file plugins/generic/objectsForReview/templates/author/submissionObjectsForReview.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the list of objects for review assigned to this author during article submission.
 *
 *}

{if !empty($authorObjects)}
<h3>{translate key="plugins.generic.objectsForReview.author.objectsForReview"}</h3>
<p>{translate key="plugins.generic.objectsForReview.author.submitInstructions"}:</p>

<table width="100%" class="listing">
	<tr valign="top">
		<td>
			<select name="submissionObjectsForReview[]" id="submissionObjectsForReview" size="5" class="selectMenu" multiple="multiple">
				{html_options options=$authorObjects}
			</select>
	</tr>
</table>

<div class="separator"></div>
{/if}
