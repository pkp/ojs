{**
 * @file plugins/generic/objectsForReview/templates/editor/objectsForReview.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display page for all objects for review for editor management.
 *
 *}
{assign var="pageTitle" value="plugins.generic.objectsForReview.objectsForReview.pageTitle"}
{include file="common/header.tpl"}

<div id="objectsForReview">
<ul class="menu">
	<li><a href="{url op="objectsForReview" path="all"}">{translate key="plugins.generic.objectsForReview.editor.assignments"}</a></li>
	<li class="current"><a href="{url op="objectsForReview"}">{translate key="plugins.generic.objectsForReview.editor.objectsForReview"}</a></li>
	<li><a href="{url op="objectsForReviewSettings"}">{translate key="plugins.generic.objectsForReview.settings"}</a></li>
</ul>
<br />

{include file="../plugins/generic/objectsForReview/templates/editor/objectsForReviewList.tpl"}

<form id="createObjectForReview" action="{url op="createObjectForReview"}" method="post"><select name="reviewObjectTypeId" class="selectMenu" size="1">{html_options options=$createTypeOptions}</select>&nbsp;<input type="submit" value="{translate key="common.create"}" class="button defaultButton"/></form>

</div>

{include file="common/footer.tpl"}