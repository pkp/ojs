{**
 * templates/sectionEditor/reviewerRecommendation.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to set the due date for a review.
 *
 *}
{strip}
{assign var="pageTitle" value="submission.recommendation"}
{include file="common/header.tpl"}
{/strip}
<div id="reviewerRecommendation">
<h3>{translate key="editor.article.enterReviewerRecommendation"}</h3>

<br />
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#recomendationForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="recommendationForm" method="post" action="{url op="enterReviewerRecommendation"}">
<input type="hidden" name="articleId" value="{$articleId|escape}" />
<input type="hidden" name="reviewId" value="{$reviewId|escape}" />
<table class="data">
<tr>
	<td class="label">{translate key="editor.article.recommendation"}</td>
	<td class="value">
		<select name="recommendation" size="1" class="selectMenu">
			{html_options_translate options=$reviewerRecommendationOptions}
		</select>
	</td>
</tr>
</table>
<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="submissionReview" path=$articleId escape=false}';"/></p>
</form>
</div>
{include file="common/footer.tpl"}

