<?php

/**
 * @defgroup FormBuilderVocabulary Form Builder Vocabulary
 * Implements a form construction toolkit for generating standard form markup.
 */

/**
 * @file classes/form/FormBuilderVocabulary.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Fbv
 * @ingroup core
 *
 * @brief Class defining Form Builder Vocabulary methods.
 *
 * Form Builder Vocabulary - FBV
 * Generates form markup in templates using {fbvX} calls.
 * Group form areas with the {fbvFormArea} call.  These sections mark off groups of semantically
 *  related form sections.
 *  Parameters:
 *   id: The form area ID
 *   class (optional): Any additional classes
 *   title (optional): Title of the area
 * Group form sections with the {fbvFormSection} call.  These sections organize directly related form elements.
 *  Parameters:
 *   id: The section ID
 *   class (optional): Any additional classes
 *   title (optional): Title of the area
 * Form submit/cancel buttons should be created with {fbvFormButtons}
 *  Parameters:
 *   submitText (optional): Text to display for the submit link (default is 'Ok')
 *   submitDisabled (optional): Whether the submit button should be disabled
 *   confirmSubmit (optional): Text to display in a confirmation dialog that must be okayed
 * 		before the form is submitted
 *   cancelText (optional): Text to display for the cancel link (default is 'Cancel')
 *   hideCancel (optional): Whether the submit button should be disabled
 *   cancelAction (optional): A LinkAction object to execute when cancel is clicked
 *   cancelUrl (optional): URL to redirect to when cancel is clicked
 * Form elements are created with {fbvElement type="type"} plus any additional parameters.
 * Each specific element type may have other additional attributes (see their method comments)
 *  Parameters:
 *   type: The form element type (one of the cases in the smartyFBVElement method)
 *   id: The element ID
 *   class (optional): Any additional classes
 *   required (optional) whether the section should have a 'required' label (adds span.required)
 *   for (optional): What the section's label is for
 *   inline: Adds .inline to the element's parent container and causes it to display inline with other elements
 *   size: One of $fbvStyles.size.SMALL (adds .quarter to element's parent container) or $fbvStyles.size.MEDIUM (adds
 *    .half to element's parentcontainer)
 *   required: Adds an asterisk and a .required class to the element's label
 */

class FormBuilderVocabulary {
	/** Form associated with this object, if any.  Will inform smarty which forms to label as required **/
	var $_form;

	/** Styles organized by parameter name */
	var $_fbvStyles;

	/**
	 * Constructor.
	 * @param $form object Form associated with this object
	 */
	function __construct($form = null) {
		$this->_fbvStyles = array(
			'size' => array('SMALL' => 'SMALL', 'MEDIUM' => 'MEDIUM', 'LARGE' => 'LARGE'),
			'height' => array('SHORT' => 'SHORT', 'MEDIUM' => 'MEDIUM', 'TALL' => 'TALL')
		);
	}


	//
	// Setters and Getters
	//
	/**
	 * Set the form
	 * @param $form object
	 */
	function setForm($form) {
		if ($form) assert(is_a($form, 'Form'));
		$this->_form = $form;
	}

	/**
	 * Get the form
	 * @return Object
	 */
	function getForm() {
		return $this->_form;
	}

	/**
	 * Get the form style constants
	 * @return array
	 */
	function getStyles() {
		return $this->_fbvStyles;
	}


	//
	// Public Methods
	//
	/**
	 * A form area that contains form sections.
	 * @param $params array
	 * @param $content string
	 * @param $smarty object
	 * @param $repeat
	 */
	function smartyFBVFormArea($params, $content, &$smarty, &$repeat) {
		assert(isset($params['id']));
		if (!$repeat) {
			$smarty->assign(array(
				'FBV_class' => isset($params['class']) ? $params['class'] : null,
				'FBV_id' => $params['id'],
				'FBV_content' => isset($content) ? $content : null,
				'FBV_translate' => isset($params['translate']) ? $params['translate'] : true,
				'FBV_title' => isset($params['title']) ? $params['title'] : null,
			));
			return $smarty->fetch('form/formArea.tpl');
		}
		return '';
	}

	/**
	 * A form section that contains controls in a variety of layout possibilities.
	 * @param $params array
	 * @param $content string
	 * @param $smarty object
	 * @param $repeat
	 */
	function smartyFBVFormSection($params, $content, &$smarty, &$repeat) {
		$form = $this->getForm();
		if (!$repeat) {
			$smarty->assign('FBV_required', isset($params['required']) ? $params['required'] : false);
			$smarty->assign('FBV_id', isset($params['id']) ? $params['id'] : null);

			// Since $content will contain input fields that may have unique Ids appended, the 'for'
			// attribute on the form section's label needs to include this.  Look for the assigned
			// form element within $content and extract the full id.  Default to the passed in param
			// otherwise.
			if (!empty($params['for'])) {
				if (preg_match('/id="(' . preg_quote($params['for'], '/') . '\-[^"]+)"/', $content, $matches)) {
					$smarty->assign('FBV_labelFor', $matches[1]);
				} else {
					$smarty->assign('FBV_labelFor', $params['for']);
				}
			} else {
				$smarty->assign('FBV_labelFor', null);
			}
			$smarty->assign(array(
				'FBV_title' => isset($params['title']) ? $params['title'] : null,
				'FBV_label' => isset($params['label']) ? $params['label'] : null,
				'FBV_layoutInfo' => $this->_getLayoutInfo($params),
				'FBV_description' => isset($params['description']) ? $params['description'] : null,
				'FBV_content' => isset($content) ? $content: null,
				'FBV_translate' => isset($params['translate']) ? $params['translate'] : true,
			));

			$class = $params['class'];

			// Check if we are using the Form class and if there are any errors
			$smarty->clear_assign(array('FBV_sectionErrors'));
			if (isset($form) && !empty($form->formSectionErrors)) {
				$class = $class . (empty($class) ? '' : ' ') . 'error';
				$smarty->assign('FBV_sectionErrors', $form->formSectionErrors);
				$form->formSectionErrors = array();
			}

			// If we are displaying checkboxes or radio options, we'll need to use a
			//  list to organize our elements -- Otherwise we use divs and spans
			if (isset($params['list']) && $params['list'] != false) {
				$smarty->assign('FBV_listSection', true);
			} else {
				// Double check that we don't have lists in the content.
				//  This is a kludge but the only way to make sure we've
				//  set the list parameter when we're using lists
				if (substr(trim($content), 0, 4) == "<li>") {
					$smarty->trigger_error('FBV: list attribute not set on form section containing lists');
				}

				$smarty->assign('FBV_listSection', false);
			}

			$smarty->assign('FBV_class', $class);
			$smarty->assign('FBV_layoutColumns', empty($params['layout']) ? false : true);

			return $smarty->fetch('form/formSection.tpl');
		} else {
			if (isset($form)) $form->formSectionErrors = array();
		}
		return '';
	}

	/**
	 * Submit and (optional) cancel button for a form.
	 * @param $params array
	 * @param $smarty object
	 * @param $repeat
	 */
	function smartyFBVFormButtons($params, &$smarty) {
		$smarty->assign(array(
			'FBV_submitText' => isset($params['submitText']) ? $params['submitText'] : 'common.ok',
			'FBV_submitDisabled' => isset($params['submitDisabled']) ? (boolean)$params['submitDisabled'] : false,
			'FBV_confirmSubmit' => isset($params['confirmSubmit']) ? $params['confirmSubmit'] : null,
			'FBV_cancelText' => isset($params['cancelText']) ? $params['cancelText'] : 'common.cancel',
			'FBV_hideCancel' => isset($params['hideCancel']) ? (boolean)$params['hideCancel'] : false,
			'FBV_cancelAction' => isset($params['cancelAction']) ? $params['cancelAction'] : null,
			'FBV_cancelUrl' => isset($params['cancelUrl']) ? $params['cancelUrl'] : null,
			'FBV_cancelUrlTarget' => isset($params['cancelUrlTarget']) ? $params['cancelUrlTarget'] : '',
			'FBV_translate' => isset($params['translate']) ? $params['translate'] : true,
		));
		return $smarty->fetch('form/formButtons.tpl');
	}

	/**
	 * Base form element.
	 * @param $params array
	 * @param $smarty object-
	 */
	function smartyFBVElement($params, &$smarty, $content = null) {
		if (!isset($params['type'])) $smarty->trigger_error('FBV: Element type not set');
		if (!isset($params['id'])) $smarty->trigger_error('FBV: Element ID not set');

		// Set up the element template
		$smarty->assign(array(
			'FBV_id' => $params['id'],
			'FBV_class' => isset($params['class']) ? $params['class'] : null,
			'FBV_required' => isset($params['required']) ? $params['required'] : false,
			'FBV_layoutInfo' => $this->_getLayoutInfo($params),
			'FBV_label' => isset($params['label']) ? $params['label'] : null,
			'FBV_for' => isset($params['for']) ? $params['for'] : null,
			'FBV_tabIndex' => isset($params['tabIndex']) ? $params['tabIndex'] : null,
			'FBV_translate' => isset($params['translate']) ? $params['translate'] : true,
			'FBV_keepLabelHtml' => isset($params['keepLabelHtml']) ? $params['keepLabelHtml'] : false,
		));

		// Unset these parameters so they don't get assigned twice
		unset($params['class']);

		// Find fields that the form class has marked as required and add the 'required' class to them
		$params = $this->_addClientSideValidation($params);
		$smarty->assign('FBV_validation', isset($params['validation']) ? $params['validation'] : null);

		// Set up the specific field's template
		switch (strtolower_codesafe($params['type'])) {
			case 'autocomplete':
				$content = $this->_smartyFBVAutocompleteInput($params, $smarty);
				break;
			case 'button':
			case 'submit':
				$content = $this->_smartyFBVButton($params, $smarty);
				break;
			case 'checkbox':
				$content = $this->_smartyFBVCheckbox($params, $smarty);
				unset($params['label']);
				break;
			case 'checkboxgroup':
				$content = $this->_smartyFBVCheckboxGroup($params, $smarty);
				unset($params['label']);
				break;
			case 'file':
				$content = $this->_smartyFBVFileInput($params, $smarty);
				break;
			case 'hidden':
				$content = $this->_smartyFBVHiddenInput($params, $smarty);
				break;
			case 'keyword':
				$content = $this->_smartyFBVKeywordInput($params, $smarty);
				break;
			case 'interests':
				$content = $this->_smartyFBVInterestsInput($params, $smarty);
				break;
			case 'radio':
				$content = $this->_smartyFBVRadioButton($params, $smarty);
				unset($params['label']);
				break;
			case 'rangeslider':
				$content = $this->_smartyFBVRangeSlider($params, $smarty);
				break;
			case 'select':
				$content = $this->_smartyFBVSelect($params, $smarty);
				break;
			case 'text':
				$content = $this->_smartyFBVTextInput($params, $smarty);
				break;
			case 'textarea':
				$content = $this->_smartyFBVTextArea($params, $smarty);
				break;
			case 'colour':
				$content = $this->_smartyFBVColour($params, $smarty);
				break;
			default: assert(false);
		}

		unset($params['type']);

		$parent = $smarty->_tag_stack[count($smarty->_tag_stack)-1];
		$group = false;

		if ($parent) {
			$form = $this->getForm();
			if (isset($form) && isset($form->errorFields[$params['id']])) {
				array_push($form->formSectionErrors, $form->errorsArray[$params['id']]);
			}

			if (isset($parent[1]['group']) && $parent[1]['group']) {
				$group = true;
			}
		}

		return $content;
	}

	//
	// Private methods
	//

	/**
	 * Form button.
	 * parameters: label (or value), disabled (optional), type (optional)
	 * @param $params array
	 * @param $smarty object
	 */
	function _smartyFBVButton($params, &$smarty) {
		// the type of this button. the default value is 'button' (but could be 'submit')

		$buttonParams = '';
		$smarty->clear_assign(array('FBV_label', 'FBV_disabled'));
		foreach ($params as $key => $value) {
			switch ($key) {
				case 'inline':
				case 'translate':
					break;
				case 'label':
				case 'type':
				case 'disabled':
					$smarty->assign('FBV_' . $key, $value);
					break;
				default: $buttonParams .= htmlspecialchars($key, ENT_QUOTES, LOCALE_ENCODING) . '="' . htmlspecialchars($value, ENT_QUOTES, LOCALE_ENCODING) . '" ';
			}
		}

		$smarty->assign('FBV_buttonParams', $buttonParams);

		return $smarty->fetch('form/button.tpl');
	}

	/**
	 * Form Autocomplete text input. (actually two inputs, label and value)
	 * parameters: disabled (optional), name (optional - assigned value of 'id' by default)
	 * @param $params array
	 * @param $smarty object
	 */
	function _smartyFBVAutocompleteInput($params, &$smarty) {
		assert(isset($params['autocompleteUrl']) && isset($params['id']));

		// This id will be used for the hidden input that should be read by the Form.
		$autocompleteId = $params['id'];

		// We then override the id parameter to differentiate it from the hidden element
		//  and make sure that the text input is not read by the Form class.
		$params['id'] = $autocompleteId . '_input';

		$smarty->clear_assign(array('FBV_id', 'FBV_autocompleteUrl', 'FBV_autocompleteValue'));
		// We set this now, so that we unset the param for the text input.
		$smarty->assign('FBV_autocompleteUrl', $params['autocompleteUrl']);
		$smarty->assign('FBV_autocompleteValue', isset($params['autocompleteValue']) ? $params['autocompleteValue'] : null);
		$smarty->assign('FBV_disableSync', isset($params['disableSync']) ? true : null);

		unset($params['autocompleteUrl']);
		unset($params['autocompleteValue']);

		$smarty->assign('FBV_textInput', $this->_smartyFBVTextInput($params, $smarty));

		$smarty->assign('FBV_id', $autocompleteId);
		return $smarty->fetch('form/autocompleteInput.tpl');
	}

	/**
	 * Range slider input.
	 * parameters: min, max
	 * @param $params array
	 * @param $smarty object
	 */
	function _smartyFBVRangeSlider($params, &$smarty) {
		// Make sure our required fields are included
		assert(isset($params['min']) && isset($params['max']));
		$smarty->assign(array(
			'FBV_min' => $params['min'],
			'FBV_max' => $params['max'],
			'FBV_value_min' => isset($params['valueMin']) ? $params['valueMin'] : $params['min'],
			'FBV_value_max' => isset($params['valueMax']) ? $params['valueMax'] : $params['max'],
			'FBV_toggleable' => isset($params['toggleable']) ? $params['toggleable'] : false,
			'FBV_toggleable_label' => isset($params['toggleable_label']) ? $params['toggleable_label'] : '',
			'FBV_enabled' => isset($params['enabled']) ? $params['enabled'] : false,
		));
		return $smarty->fetch('form/rangeSlider.tpl');
	}

	/**
	 * Form text input.
	 * parameters: disabled (optional), name (optional - assigned value of 'id' by default), multilingual (optional)
	 * @param $params array
	 * @param $smarty object
	 */
	function _smartyFBVTextInput($params, &$smarty) {
		$params['name'] = isset($params['name']) ? $params['name'] : $params['id'];
		$params['subLabelTranslate'] = isset($params['subLabelTranslate']) ? (boolean) $params['subLabelTranslate'] : true;
		$params['uniqId'] = uniqid();
		$smarty->assign('FBV_isPassword', isset($params['password']) ? true : false);

		$textInputParams = '';
		$smarty->clear_assign(array('FBV_disabled', 'FBV_readonly', 'FBV_multilingual', 'FBV_name', 'FBV_value', 'FBV_label_content', 'FBV_uniqId'));
		foreach ($params as $key => $value) {
			switch ($key) {
				case 'label': $smarty->assign('FBV_label_content', $this->_smartyFBVSubLabel($params, $smarty)); break;
				case 'type': break;
				case 'size': break;
				case 'inline': break;
				case 'subLabelTranslate': break;
				case 'disabled':
				case 'readonly':
				case 'multilingual':
				case 'name':
				case 'id':
				case 'value':
				case 'uniqId':
					$smarty->assign('FBV_' . $key, $value); break;
				case 'required': if ($value != 'true') $textInputParams .= 'required="' + htmlspecialchars($value, ENT_QUOTES, LOCALE_ENCODING) +'"'; break;
				default: $textInputParams .= htmlspecialchars($key, ENT_QUOTES, LOCALE_ENCODING) . '="' . htmlspecialchars($value, ENT_QUOTES, LOCALE_ENCODING). '" ';
			}
		}

		$smarty->assign('FBV_textInputParams', $textInputParams);

		return $smarty->fetch('form/textInput.tpl');
	}

	/**
	 * Form text area.
	 * parameters:
	 *  - value: string for single language inputs, array (xx_YY => language_value) for multilingual
	 *  - name: string (optional - assigned value based on ID by default)
	 *  - disabled: boolean (default false)
	 *  - multilingual: boolean (default false)
	 *  - rich: false (default), true, or 'extended'
	 * @param $params array
	 * @param $smarty object
	 */
	function _smartyFBVTextArea($params, &$smarty) {
		$params['name'] = isset($params['name']) ? $params['name'] : $params['id'];
		$params['rows'] = isset($params['rows']) ? $params['rows'] : 10;
		$params['cols'] = isset($params['cols']) ? $params['cols'] : 80;
		$params['subLabelTranslate'] = isset($params['subLabelTranslate']) ? (boolean) $params['subLabelTranslate'] : true;
		$params['uniqId'] = uniqid();

		$textAreaParams = '';
		$smarty->clear_assign(array('FBV_label_content', 'FBV_disabled', 'FBV_readonly', 'FBV_multilingual', 'FBV_name', 'FBV_value', 'FBV_height', 'FBV_uniqId', 'FBV_rows', 'FBV_cols', 'FBV_rich', 'FBV_variables'));
		foreach ($params as $key => $value) {
			switch ($key) {
				case 'name':
				case 'value':
				case 'rows':
				case 'cols':
				case 'rich':
				case 'disabled':
				case 'readonly':
				case 'multilingual':
				case 'uniqId':
				case 'variables':
					$smarty->assign('FBV_' . $key, $value);
					break;
				case 'label': $smarty->assign('FBV_label_content', $this->_smartyFBVSubLabel($params, $smarty)); break;
				case 'type': break;
				case 'size': break;
				case 'inline': break;
				case 'subLabelTranslate': break;
				case 'height':
					$styles = $this->getStyles();
					switch($value) {
						case $styles['height']['SHORT']: $smarty->assign('FBV_height', 'short'); break;
						case $styles['height']['MEDIUM']: $smarty->assign('FBV_height', 'medium'); break;
						case $styles['height']['TALL']: $smarty->assign('FBV_height', 'tall'); break;
						default:
							$smarty->trigger_error('FBV: invalid height specified for textarea.');
					}
					break;
				case 'id': break; // if we don't do this, the textarea ends up with two id attributes because FBV_id is also set.
				default: $textAreaParams .= htmlspecialchars($key, ENT_QUOTES, LOCALE_ENCODING) . '="' . htmlspecialchars($value, ENT_QUOTES, LOCALE_ENCODING) . '" ';
			}
		}

		$smarty->assign('FBV_textAreaParams', $textAreaParams);

		return $smarty->fetch('form/textarea.tpl');
	}

	/**
	 * Form colour input.
	 * parameters: disabled (optional), name (optional - assigned value of 'id' by default)
	 * @param $params array
	 * @param $smarty object
	 */
	function _smartyFBVColour($params, &$smarty) {
		$params['name'] = isset($params['name']) ? $params['name'] : $params['id'];
		$params['subLabelTranslate'] = isset($params['subLabelTranslate']) ? (boolean) $params['subLabelTranslate'] : true;
		$params['uniqId'] = uniqid();
		$smarty->assign('FBV_isPassword', isset($params['password']) ? true : false);

		$colourParams = '';
		$smarty->clear_assign(array('FBV_disabled', 'FBV_readonly', 'FBV_multilingual', 'FBV_name', 'FBV_value', 'FBV_label_content', 'FBV_uniqId', 'FBV_default'));
		foreach ($params as $key => $value) {
			switch ($key) {
				case 'label': $smarty->assign('FBV_label_content', $this->_smartyFBVSubLabel($params, $smarty)); break;
				case 'type': break;
				case 'size': break;
				case 'inline': break;
				case 'subLabelTranslate': break;
				case 'disabled':
				case 'readonly':
				case 'name':
				case 'id':
				case 'value':
				case 'uniqId':
				case 'default':
					$smarty->assign('FBV_' . $key, $value); break;
					break;
				case 'required': if ($value != 'true') $colourParams .= 'required="' + htmlspecialchars($value, ENT_QUOTES, LOCALE_ENCODING) +'"'; break;
				default: $colourParams .= htmlspecialchars($key, ENT_QUOTES, LOCALE_ENCODING) . '="' . htmlspecialchars($value, ENT_QUOTES, LOCALE_ENCODING). '" ';
			}
		}

		$smarty->assign('FBV_textInputParams', $colourParams);

		return $smarty->fetch('form/colour.tpl');
	}

	/**
	 * Hidden input element.
	 * parameters: value, id, name (optional - assigned value of 'id' by default), disabled (optional), multilingual (optional)
	 * @param $params array
	 * @param $smarty object
	 */
	function _smartyFBVHiddenInput($params, &$smarty) {
		$params['name'] = isset($params['name']) ? $params['name'] : $params['id'];

		$hiddenInputParams = '';
		$smarty->clear_assign(array('FBV_id', 'FBV_value'));
		foreach ($params as $key => $value) {
			switch ($key) {
				case 'name':
				case 'id':
				case 'value':
					$smarty->assign('FBV_' . $key, $value);
					break;
				case 'label': break;
				case 'type': break;
				default: $hiddenInputParams .= htmlspecialchars($key, ENT_QUOTES, LOCALE_ENCODING) . '="' . htmlspecialchars($value, ENT_QUOTES, LOCALE_ENCODING) . '" ';
			}
		}

		$smarty->assign('FBV_hiddenInputParams', $hiddenInputParams);

		return $smarty->fetch('form/hiddenInput.tpl');
	}

	/**
	 * Form select control.
	 * parameters: from [array], selected [array index], defaultLabel (optional), defaultValue (optional), disabled (optional),
	 * 	translate (optional), name (optional - value of 'id' by default)
	 * @param $params array
	 * @param $smarty object
	 */
	function _smartyFBVSelect($params, &$smarty) {
		$params['name'] = isset($params['name']) ? $params['name'] : $params['id'];
		$params['translate'] = isset($params['translate']) ? $params['translate'] : true;
		$params['subLabelTranslate'] = isset($params['subLabelTranslate']) ? (boolean) $params['subLabelTranslate'] : true;

		$selectParams = '';

		$smarty->clear_assign(array('FBV_label', 'FBV_from', 'FBV_selected', 'FBV_label_content', 'FBV_defaultValue', 'FBV_defaultLabel', 'FBV_required', 'FBV_disabled'));
		foreach ($params as $key => $value) {
			switch ($key) {
				case 'from':
				case 'selected':
				case 'translate':
				case 'defaultValue':
				case 'defaultLabel':
				case 'disabled':
				case 'required':
					$smarty->assign('FBV_' . $key, $value);
					break;
				case 'type':
				case 'inline':
				case 'size':
					break;
				case 'subLabelTranslate': break;
				case 'label': $smarty->assign('FBV_label_content', $this->_smartyFBVSubLabel($params, $smarty)); break;
				default: $selectParams .= htmlspecialchars($key, ENT_QUOTES, LOCALE_ENCODING) . '="' . htmlspecialchars($value, ENT_QUOTES, LOCALE_ENCODING) . '" ';
			}
		}

		$smarty->assign('FBV_selectParams', $selectParams);

		return $smarty->fetch('form/select.tpl');
	}

	/**
	 * Form checkbox group control.
	 * parameters: from [array], selected [array index], defaultLabel (optional), defaultValue (optional), disabled (optional),
	 * 	translate (optional), name (optional - value of 'id' by default)
	 * @param $params array
	 * @param $smarty object
	 */
	function _smartyFBVCheckboxGroup($params, &$smarty) {
		$params['name'] = isset($params['name']) ? $params['name'] : $params['id'];
		$params['translate'] = isset($params['translate']) ? (boolean)$params['translate'] : true;
		$params['subLabelTranslate'] = isset($params['subLabelTranslate']) ? (boolean) $params['subLabelTranslate'] : true;
		$checkboxParams = '';

		$smarty->clear_assign(array('FBV_from', 'FBV_selected', 'FBV_label_content', 'FBV_defaultValue', 'FBV_defaultLabel', 'FBV_name'));
		foreach ($params as $key => $value) {
			switch ($key) {
				case 'from':
				case 'selected':
				case 'defaultValue':
				case 'defaultLabel':
				case 'translate':
				case 'name':
				case 'validation':
				case 'disabled':
					$smarty->assign('FBV_' . $key, $value);
					break;
				case 'type': break;
				case 'inline': break;
				case 'subLabelTranslate': break;
				default: $checkboxParams .= htmlspecialchars($key, ENT_QUOTES, LOCALE_ENCODING) . '="' . htmlspecialchars($value, ENT_QUOTES, LOCALE_ENCODING) . '" ';
			}
		}

		$smarty->assign('FBV_checkboxParams', $checkboxParams);

		return $smarty->fetch('form/checkboxGroup.tpl');
	}

	/**
	 * Checkbox input control.
	 * parameters: label, disabled (optional), translate (optional), name (optional - value of 'id' by default)
	 * @param $params array
	 * @param $smarty object
	 */
	function _smartyFBVCheckbox($params, &$smarty) {
		$params['name'] = isset($params['name']) ? $params['name'] : $params['id'];
		$params['translate'] = isset($params['translate']) ? (boolean)$params['translate'] : true;

		$checkboxParams = '';
		$smarty->clear_assign(array('FBV_id', 'FBV_label', 'FBV_checked', 'FBV_disabled'));
		foreach ($params as $key => $value) {
			switch ($key) {
				case 'type': break;
				case 'id':
				case 'label':
				case 'translate':
				case 'checked':
				case 'disabled':
					$smarty->assign('FBV_' . $key, $value);
					break;
				default: $checkboxParams .= htmlspecialchars($key, ENT_QUOTES, LOCALE_ENCODING) . '="' . htmlspecialchars($value, ENT_QUOTES, LOCALE_ENCODING) . '" ';
			}
		}

		$smarty->assign('FBV_checkboxParams', $checkboxParams);

		return $smarty->fetch('form/checkbox.tpl');
	}

	/**
	 * Radio input control.
	 * parameters: label, disabled (optional), translate (optional), name (optional - value of 'id' by default)
	 * @param $params array
	 * @param $smarty object
	 */
	function _smartyFBVRadioButton($params, &$smarty) {
		$params['name'] = isset($params['name']) ? $params['name'] : $params['id'];
		$params['translate'] = isset($params['translate']) ? $params['translate'] : true;

		if (isset($params['label']) && isset($params['content'])) {
			$smarty->trigger_error('FBV: radio button cannot have both a content and a label parameter.  Label has precedence.');
		}

		$radioParams = '';
		$smarty->clear_assign(array('FBV_id', 'FBV_label', 'FBV_content', 'FBV_checked', 'FBV_disabled'));
		foreach ($params as $key => $value) {
			switch ($key) {
				case 'type': break;
				case 'id':
				case 'label':
				case 'translate':
				case 'checked':
				case 'disabled':
				case 'content':
					$smarty->assign('FBV_' . $key, $value);
					break;
				default: $radioParams .= htmlspecialchars($key, ENT_QUOTES, LOCALE_ENCODING) . '="' . htmlspecialchars($value, ENT_QUOTES, LOCALE_ENCODING) . '" ';
			}
		}

		$smarty->assign('FBV_radioParams', $radioParams);

		return $smarty->fetch('form/radioButton.tpl');
	}

	/**
	 * File upload input.
	 * parameters: submit (optional - name of submit button to include), disabled (optional), name (optional - value of 'id' by default)
	 * @param $params array
	 * @param $smarty object
	 */
	function _smartyFBVFileInput($params, &$smarty) {
		$params['name'] = isset($params['name']) ? $params['name'] : $params['id'];
		$params['translate'] = isset($params['translate']) ? $params['translate'] : true;

		$smarty->clear_assign(array('FBV_id', 'FBV_label_content', 'FBV_checked', 'FBV_disabled', 'FBV_submit'));
		foreach ($params as $key => $value) {
			switch ($key) {
				case 'type': break;
				case 'id':
				case 'submit':
				case 'name':
				case 'disabled':
					$smarty->assign('FBV_' . $key, $value);
					break;
				case 'label': $smarty->assign('FBV_label_content', $this->_smartyFBVSubLabel($params, $smarty)); break;
			}
		}

		return $smarty->fetch('form/fileInput.tpl');
	}

	/**
	 * Keyword input.
	 * parameters: available - all available keywords (for autosuggest); current - user's current keywords
	 * @param $params array
	 * @param $smarty object
	 */
	function _smartyFBVKeywordInput($params, &$smarty) {
		$params['uniqId'] = uniqid();

		$smarty->clear_assign(array('FBV_id', 'FBV_label', 'FBV_label_content', 'FBV_currentKeywords', 'FBV_multilingual', 'FBV_sourceUrl', 'FBV_uniqId', 'FBV_disabled'));
		$emptyFormLocaleMap = array_fill_keys(array_keys($smarty->get_template_vars('formLocales')), array());
		$smarty->assign('FBV_availableKeywords', $emptyFormLocaleMap);
		foreach ($params as $key => $value) {
			switch ($key) {
				case 'type': break;
				case 'id':
				case 'uniqId':
				case 'disabled':
				case 'multilingual':
					$smarty->assign('FBV_' . $key, $value);
					break;
				case 'label': $smarty->assign('FBV_label_content', $this->_smartyFBVSubLabel($params, $smarty)); break;
				case 'available': $smarty->assign(
					'FBV_availableKeywords',
					$thing = array_merge(
						$emptyFormLocaleMap,
						$value
					)
				); break;
				case 'current': $smarty->assign('FBV_currentKeywords', $value); break;
				case 'source': $smarty->assign('FBV_sourceUrl', $value); break;
			}
		}

		return $smarty->fetch('form/keywordInput.tpl');
	}

	/**
	 * Reviewing interests input.
	 * parameters: interests - current users's keywords (array)
	 * @param $params array
	 * @param $smarty object
	 */
	function _smartyFBVInterestsInput($params, &$smarty) {
		$smarty->clear_assign(array('FBV_id', 'FBV_label', 'FBV_label_content', 'FBV_interests'));
		$params['subLabelTranslate'] = isset($params['subLabelTranslate']) ? (boolean) $params['subLabelTranslate'] : true;
		foreach ($params as $key => $value) {
			switch ($key) {
				case 'type': break;
				case 'id':
				case 'interests':
					$smarty->assign('FBV_' . $key, $value);
					break;
				case 'label': $smarty->assign('FBV_label_content', $this->_smartyFBVSubLabel($params, $smarty)); break;
			}
		}

		return $smarty->fetch('form/interestsInput.tpl');
	}

	/**
	 * Custom Smarty function for labelling/highlighting of form fields.
	 * @param $params array can contain 'name' (field name/ID), 'required' (required field), 'key' (localization key), 'label' (non-localized label string), 'suppressId' (boolean)
	 * @param $smarty Smarty
	 */
	function _smartyFBVSubLabel($params, &$smarty) {
		assert(isset($params['label']));

		$returner = '';

		$form = $this->getForm();
		if (isset($form) && isset($form->errorFields[$params['name']])) {
			$smarty->assign('FBV_error', true);
		} else {
			$smarty->assign('FBV_error', false);
		}

		$smarty->clear_assign(array('FBV_suppressId', 'FBV_label', 'FBV_required', 'FBV_uniqId', 'FBV_multilingual', 'FBV_required'));
		foreach ($params as $key => $value) {
			switch ($key) {
				case 'subLabelTranslate': $smarty->assign('FBV_subLabelTranslate', $value); break;
				case 'label':
				case 'uniqId':
				case 'multilingual':
				case 'suppressId':
				case 'required':
					$smarty->assign('FBV_' . $key, $value);
					break;
			}
		}

		$returner = $smarty->fetch('form/subLabel.tpl');

		return $returner;
	}

	/**
	 * Assign the appropriate class name to the element for client-side validation
	 * @param $params array
	 * return array
	 */
	function _addClientSideValidation($params) {
		$form = $this->getForm();
		if (isset($form)) {
			// Assign the appropriate class name to the element for client-side validation
			$fieldId = $params['id'];
			if (isset($form->cssValidation[$fieldId])) {
				$params['validation'] = implode(' ', $form->cssValidation[$fieldId]);
			}
		}
		return $params;
	}

	/**
	 * Cycle through layout parameters to add the appropriate classes to the element's parent container
	 * @param $params array
	 * @return string
	 */
	function _getLayoutInfo($params) {
		$classes = array();
		foreach ($params as $key => $value) {
			switch ($key) {
				case 'size':
					switch($value) {
						case 'SMALL': $classes[] = 'pkp_helpers_quarter'; break;
						case 'MEDIUM': $classes[] = 'pkp_helpers_half'; break;
						CASE 'LARGE': $classes[] = 'pkp_helpers_threeQuarter'; break;
					}
					break;
				case 'inline':
					if($value) $classes[] = 'inline'; break;
			}
		}
		if(!empty($classes)) {
			return implode(' ', $classes);
		} else return null;
	}


	/**
	 * Custom Smarty function for labelling/highlighting of form fields.
	 * @param $params array can contain 'name' (field name/ID), 'required' (required field), 'key' (localization key), 'label' (non-localized label string), 'suppressId' (boolean)
	 * @param $smarty Smarty
	 */
	function smartyFieldLabel($params, &$smarty) {
		$returner = '';
		if (isset($params) && !empty($params)) {
			if (isset($params['key'])) {
				$params['label'] = __($params['key'], $params);
			}

			$form = $this->getForm();
			if (isset($form) && isset($form->errorFields[$params['name']])) {
				$smarty->assign('FBV_class', 'error ' . $params['class']);
			} else {
				$smarty->assign('FBV_class', $params['class']);
			}

			$smarty->clear_assign(array('FBV_suppressId', 'FBV_label', 'FBV_required', 'FBV_disabled', 'FBV_name'));
			foreach ($params as $key => $value) {
				switch ($key) {
					case 'label':
					case 'required':
					case 'suppressId':
					case 'disabled':
					case 'name':
						$smarty->assign('FBV_' . $key, $value);
						break;
				}
			}

			$returner = $smarty->fetch('form/fieldLabel.tpl');
		}
		return $returner;
	}
}

?>
