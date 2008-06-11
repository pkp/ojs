{**
 * reviewFormResponse.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Review Form to enter responses/comments/answers.
 *
 *}
{if $editorPreview}
{include file="submission/comment/header.tpl"}
{else}
{translate|assign:"pageTitleTranslated" key="submission.reviewFormResponse"}{assign var="pageCrumbTitle" value="submission.reviewFormResponse"}
{include file="common/header.tpl"}

{include file="common/formErrors.tpl"}
{/if}

{assign var=disabled value=""}
{if $isLocked || $editorPreview}
	{assign var=disabled value="disabled=\"disabled\" "}
{/if}

<h3>{$reviewForm->getReviewFormTitle()}</h3>
<p>{$reviewForm->getReviewFormDescription()}</p>

<form name="saveReviewFormResponse" method="post" action="{url op="saveReviewFormResponse" path=$reviewId|to_array:$reviewForm->getReviewFormId()}">
	{foreach from=$reviewFormElements name=reviewFormElements key=elementId item=reviewFormElement}
		<p>{$reviewFormElement->getReviewFormElementQuestion()} {if $reviewFormElement->getRequired() == 1}*{/if}</p>
		<p>
			{if $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_SMALL_TEXT_FIELD}
				<input {$disabled}type="text" name="reviewFormResponses[{$elementId}]" id="reviewFormResponses-{$elementId}" value="{$reviewFormResponses[$elementId]|escape}" size="10" maxlength="40" class="textField" />
			{/if}
			{if $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_TEXT_FIELD}
				<input {$disabled}type="text" name="reviewFormResponses[{$elementId}]" id="reviewFormResponses-{$elementId}" value="{$reviewFormResponses[$elementId]|escape}" size="40" maxlength="120" class="textField" />
			{/if}
			{if $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_TEXTAREA}
				<textarea {$disabled}name="reviewFormResponses[{$elementId}]" id="reviewFormResponses-{$elementId}" value="{$reviewFormResponses[$elementId]|escape}" rows="4" cols="40" class="textArea" />{$reviewFormResponses[$elementId]|escape}</textarea>
			{/if}
			{if $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES}
				{assign var=possibleResponses value=$reviewFormElement->getReviewFormElementPossibleResponses()}
				{foreach name=responses from=$possibleResponses key=responseId item=responseItem}
					<input {$disabled}type="checkbox" name="reviewFormResponses[{$elementId}][]" id="reviewFormResponses-{$elementId}-{$smarty.foreach.responses.iteration}" value="{$smarty.foreach.responses.iteration}"{if !empty($reviewFormResponses[$elementId]) && in_array($smarty.foreach.responses.iteration, $reviewFormResponses[$elementId])} checked="checked"{/if} /><label for="reviewFormResponses-{$elementId}-{$smarty.foreach.responses.iteration}">{$responseItem.content}</label><br/>
				{/foreach}
			{/if}
			{if $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_RADIO_BUTTONS}
				{assign var=possibleResponses value=$reviewFormElement->getReviewFormElementPossibleResponses()}
				{foreach name=responses from=$possibleResponses key=responseId item=responseItem}
					<input {$disabled}type="radio"  name="reviewFormResponses[{$elementId}]" id="reviewFormResponses-{$elementId}-{$smarty.foreach.responses.iteration}" value="{$smarty.foreach.responses.iteration}"{if $smarty.foreach.responses.iteration == $reviewFormResponses[$elementId]} checked="checked"{/if}/><label for="reviewFormResponses-{$elementId}-{$smarty.foreach.responses.iteration}">{$responseItem.content}</label><br/>
				{/foreach}
			{/if}
			{if $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_DROP_DOWN_BOX}
				<select {$disabled}name="reviewFormResponses[{$elementId}]" id="reviewFormResponses-{$elementId}" size="1" class="selectMenu">
					<option label="" value=""></option>
					{assign var=possibleResponses value=$reviewFormElement->getReviewFormElementPossibleResponses()}
					{foreach name=responses from=$possibleResponses key=responseId item=responseItem}
						<option label="{$responseItem.content}" value="{$smarty.foreach.responses.iteration}"{if $smarty.foreach.responses.iteration == $reviewFormResponses[$elementId]} selected="selected"{/if}>{$responseItem.content}</option>
					{/foreach}
				</select>
			{/if}
		</p>
	{/foreach}

	<br />

	{if $editorPreview}
		<p><input type="button" value="{translate key="common.close"}" class="button defaultButton" onclick="window.close()" /></p>
	{else}
		<p><input {$disabled}type="submit" name="save" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.close"}" class="button" onclick="document.location.href='{url op="submission" path=$reviewId}'" /></p>
	{/if}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="submission/comment/footer.tpl"}
