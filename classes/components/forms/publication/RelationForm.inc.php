<?php
/**
 * @file classes/components/form/publication/RelationForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RelationForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for setting a publication's relation
 */
namespace APP\components\forms\publication;
use \PKP\components\forms\FormComponent;
use \PKP\components\forms\FieldOptions;
use \PKP\components\forms\FieldText;

define('FORM_ID_RELATION', 'relation');

class RelationForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_ID_RELATION;

	/** @copydoc FormComponent::$method */
	public $method = 'PUT';

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $locales array Supported locales
	 * @param $publication Publication The publication to change settings for
	 * @param $publicationContext Context The context of the publication
	 * @param $baseUrl string Site's base URL. Used for image previews.
	 * @param $temporaryFileApiUrl string URL to upload files to
	 */
	public function __construct($action, $locales, $publication, $publicationContext, $baseUrl, $temporaryFileApiUrl) {
		$this->action = $action;
		$this->successMessage = __('publication.relation.success');
		$this->locales = $locales;

		// Relation options
		$relationOptions = \Services::get('publication')->getRelationOptions();
		$this->addField(new FieldOptions('relationStatus', [
					'label' => __('publication.relation.label'),
					'type' => 'radio',
					'value' => (int) $publication->getData('relationStatus'),
					'options' => $relationOptions,
				]))
				->addField(new FieldText('vorDoi', [
					'label' => __('publication.relation.vorDoi'),
					'value' => $publication->getData('vorDoi'),
					'size' => 'large',
					'showWhen' => ['relationStatus', PUBLICATION_RELATION_PUBLISHED],
				]));
	}
}
