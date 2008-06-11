{**
 * previewReviewForm.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Preview of a review form.
 *
 *}
{assign var="pageId" value="manager.reviewFormElements.previewReviewForm"}
{assign var="pageCrumbTitle" value=$pageTitle}
{include file="common/header.tpl"}

<h3>{$reviewForm->getReviewFormTitle()}</h3>
<p>{$reviewForm->getReviewFormDescription()}</p>

{foreach from=$reviewFormElements name=reviewFormElements item=reviewFormElement}
<p>{$reviewFormElement->getReviewFormElementQuestion()}</p>
<p>
{if $reviewFormElement->getElementType() == 1}
<input type="text" size="10" maxlength="40" class="textField" />
{/if}
{if $reviewFormElement->getElementType() == 2}
<input type="text" size="40" maxlength="120" class="textField" />
{/if}
{if $reviewFormElement->getElementType() == 3}
<textarea rows="4" cols="40" class="textArea" /></textarea>
{/if}
{if $reviewFormElement->getElementType() == 4}
{assign var=possibleResponses value=$reviewFormElement->getReviewFormElementPossibleResponses()}
{foreach name=responses from=$possibleResponses key=responseId item=responseItem}
<input type="checkbox"/> {$responseItem.content}<br/>
{/foreach}
{/if}
{if $reviewFormElement->getElementType() == 5}
{assign var=possibleResponses value=$reviewFormElement->getReviewFormElementPossibleResponses()}
{foreach name=responses from=$possibleResponses key=responseId item=responseItem}
<input type="radio"> {$responseItem.content}<br/>
{/foreach}
{/if}
{if $reviewFormElement->getElementType() == 6}
<select size="1" class="selectMenu">
{assign var=possibleResponses value=$reviewFormElement->getReviewFormElementPossibleResponses()}
{foreach name=responses from=$possibleResponses key=responseId item=responseItem}
<option>{$responseItem.content}</option>
{/foreach}
</select>
{/if}
</p>
{/foreach}

<br/>

<form name="previewReviewForm" method="post" action="{url op="selectReviewForm" path=$articleId|to_array:$reviewId}">
	<p><input type="submit" value="{translate key="common.close"}" class="button defaultButton" /></p>
</form>

{include file="common/footer.tpl"}
