{**
 * @file plugins/generic/objectsForReview/templates/objectForReview.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Detailed public object for review view.
 *
 *}
{assign var="pageTitle" value=plugins.generic.objectsForReview.public.objectForReview}
{include file="common/header.tpl"}

<br/>

<div id="objectForReviewDetails">

{include file="$ofrTemplatePath/objectForReviewMetadata.tpl"}

</div>

{include file="common/footer.tpl"}
