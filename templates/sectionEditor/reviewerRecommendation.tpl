{**
 * setDueDate.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to set the due date for a review.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.recommendation"}
{include file="common/header.tpl"}

<h3>{translate key="editor.article.enterReviewerRecommendation"}</h3>

<br />

<form method="post" action="{$requestPageUrl}/enterReviewerRecommendation">
<input type="hidden" name="articleId" value="{$articleId|escape}" />
<input type="hidden" name="reviewId" value="{$reviewId|escape}" />
<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{translate key="editor.article.recommendation"}</td>
	<td width="80%" class="value">
		<select name="recommendation" size="1" class="selectMenu">
			{html_options_translate options=$reviewerRecommendationOptions}
		</select>
	</td>
</tr>
</table>
<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/sectionEditor/submissionReview/{$articleId}';"/></p>
</form>

{include file="common/footer.tpl"}
