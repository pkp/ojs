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

<div class="subTitle">{translate key="editor.article.enterReviewerRecommendation"}</div>

<br /><br />

<form method="post" action="{$requestPageUrl}/enterReviewerRecommendation">
<input type="hidden" name="articleId" value="{$articleId}">
<input type="hidden" name="reviewId" value="{$reviewId}">
<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="editor.article.enterReviewerRecommendation"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
			<tr>
				<td valign="top" width="20%">{translate key="editor.article.recommendation"}:</td>
				<td width="80%">
					<select name="recommendation">
						{html_options_translate options=$reviewerRecommendationOptions}
					</select>
				</td>
			</tr>
			<tr>
				<td></td>
				<td><input type="submit" value="{translate key="form.submit"}"></td>
			</tr>
		</table>
	</td>
</tr>
</table>
</div>
</form>

{include file="common/footer.tpl"}
