{**
 * templates/manager/reviewForms/previewReviewForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Preview of a review form.
 *
 *}
<h3>{$title|escape}</h3>
<p>{$description}</p>
{iterate from=reviewFormElements item=reviewFormElement}
		<p>{$reviewFormElement->getLocalizedQuestion()}{if $reviewFormElement->getRequired()}*{/if}</p>
		<p>
				{if $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_SMALL_TEXT_FIELD}
						<input type="text" size="10" maxlength="40" class="textField" />
				{elseif $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_TEXT_FIELD}
						<input type="text" size="40" class="textField" />
				{elseif $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_TEXTAREA}
						<textarea rows="4" cols="40" class="textArea"></textarea>
				{elseif $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES}
						{assign var=possibleResponses value=$reviewFormElement->getLocalizedPossibleResponses()}
						{foreach name=responses from=$possibleResponses key=responseId item=responseItem}
								<input id="check-{$responseId|escape}" type="checkbox"/>
								<label for="check-{$responseId|escape}">{$responseItem}</label>
								<br/>
						{/foreach}
				{elseif $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_RADIO_BUTTONS}
						{assign var=possibleResponses value=$reviewFormElement->getLocalizedPossibleResponses()}
						{foreach name=responses from=$possibleResponses key=responseId item=responseItem}
								<input id="radio-{$responseId|escape}" name="{$reviewFormElement->getId()}" type="radio"/>
								<label for="radio-{$responseId|escape}">{$responseItem}</label>
								<br/>
						{/foreach}
				{elseif $reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_DROP_DOWN_BOX}
						<select size="1" class="selectMenu">
								{assign var=possibleResponses value=$reviewFormElement->getLocalizedPossibleResponses()}
								{foreach name=responses from=$possibleResponses key=responseId item=responseItem}
										<option>{$responseItem}</option>
								{/foreach}
						</select>
				{/if}
		</p>
{/iterate}
