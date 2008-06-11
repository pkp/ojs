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

{if not $reviewForm->getPublished()}
	<ul class="menu">
		<li><a href="{url op="editReviewForm" path=$reviewForm->getReviewFormId()}">{translate key="manager.reviewForms.edit"}</a></li>
		<li><a href="{url op="reviewFormElements" path=$reviewForm->getReviewFormId()}">{translate key="manager.reviewFormElements"}</a></li>
		<li class="current"><a href="{url op="previewReviewForm" path=$reviewForm->getReviewFormId()}">{translate key="manager.reviewForms.preview"}</a></li>
	</ul>
{/if}

<br/>

<h3>{$reviewForm->getReviewFormTitle()}</h3>
<p>{$reviewForm->getReviewFormDescription()}</p>

{foreach from=$reviewFormElements name=reviewFormElements item=reviewFormElement}
<p>{$reviewFormElement->getReviewFormElementQuestion()}</p>
<p>
{if $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_SMALL_TEXT_FIELD}
<input type="text" size="10" maxlength="40" class="textField" />
{/if}
{if $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_TEXT_FIELD}
<input type="text" size="40" maxlength="120" class="textField" />
{/if}
{if $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_TEXTAREA}
<textarea rows="4" cols="40" class="textArea" /></textarea>
{/if}
{if $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES}
{assign var=possibleResponses value=$reviewFormElement->getReviewFormElementPossibleResponses()}
{foreach name=responses from=$possibleResponses key=responseId item=responseItem}
<input type="checkbox"/> {$responseItem.content}<br/>
{/foreach}
{/if}
{if $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_RADIO_BUTTONS}
{assign var=possibleResponses value=$reviewFormElement->getReviewFormElementPossibleResponses()}
{foreach name=responses from=$possibleResponses key=responseId item=responseItem}
<input type="radio"> {$responseItem.content}<br/>
{/foreach}
{/if}
{if $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_DROP_DOWN_BOX}
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

<form name="previewReviewForm" method="post" action="{if $reviewForm->getPublished()}{url op="publishedReviewForms"}{else}{url op="editReviewForm" path=$reviewForm->getReviewFormId()}{/if}">
	<p><input type="submit" value="{translate key="common.close"}" class="button defaultButton" /></p>
</form>

{include file="common/footer.tpl"}
