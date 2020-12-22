<?php
/**
 * @file classes/components/form/announcement/AnnouncementForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for creating a new announcement
 */
namespace APP\components\forms\announcement;
use \PKP\components\forms\FieldText;
use \PKP\components\forms\announcement\PKPAnnouncementForm;

define('FORM_ANNOUNCEMENT', 'announcement');

class announcementForm extends PKPAnnouncementForm {
    /**
     * Constructor
     *
     * @param $action string URL to submit the form to
     * @param $locales array Supported locales
     * @param $announcementContext Context The context to get supported announcement types
     */
    public function __construct($action, $locales, $announcementContext) {
        parent::__construct($action, $locales, $announcementContext);

        $this->addField(new FieldText('keyword', [
            'label' => __('manager.announcements.form.keyword'),
            'description' => __('manager.announcements.form.keywordInstructions'),
            'isMultilingual' => true,
            'size' => 'small',
        ]));
    }
}
