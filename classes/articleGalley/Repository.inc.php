<?php
/**
 * @file classes/articleGalley/Repository.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class articleGalley
 *
 * @brief A repository to find and manage articleGalleys.
 */

namespace APP\articleGalley;

use APP\core\Request;
use APP\core\Services;
use APP\facades\Repo;
use APP\publication\Publication;
use APP\submission\Submission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\LazyCollection;
use PKP\plugins\HookRegistry;
use PKP\services\PKPSchemaService;
use PKP\validation\ValidatorFactory;

class Repository
{
    /** @var DAO $dao */
    public $dao;

    /** @var string $schemaMap The name of the class to map this entity to its schemaa */
    public $schemaMap = maps\Schema::class;

    /** @var Request $request */
    protected $request;

    /** @var PKPSchemaService $schemaService */
    protected $schemaService;


    public function __construct(DAO $dao, Request $request, PKPSchemaService $schemaService)
    {
        $this->dao = $dao;
        $this->request = $request;
        $this->schemaService = $schemaService;
    }

    /** @copydoc DAO::newDataObject() */
    public function newDataObject(array $params = []): articleGalley
    {
        $object = $this->dao->newDataObject();
        if (!empty($params)) {
            $object->setAllData($params);
        }
        return $object;
    }

    /** @copydoc DAO::get() */
    public function get(int $id): ?articleGalley
    {
        return $this->dao->get($id);
    }

    /** @copydoc DAO::getCount() */
    public function getCount(Collector $query): int
    {
        return $this->dao->getCount($query);
    }

    /** @copydoc DAO::getIds() */
    public function getIds(Collector $query): Collection
    {
        return $this->dao->getIds($query);
    }

    /** @copydoc DAO::getMany() */
    public function getMany(Collector $query): LazyCollection
    {
        return $this->dao->getMany($query);
    }

    /** @copydoc DAO::getCollector() */
    public function getCollector(): Collector
    {
        return App::make(Collector::class);
    }

    /**
     * Get an instance of the map class for mapping
     * articleGalleys to their schema
     */
    public function getSchemaMap(Submission $submission, Publication $publication): maps\Schema
    {
        return app('maps')->withExtensions(
            $this->schemaMap,
            [
                'submission' => $submission,
                'publication' => $publication
            ]
        );
    }

    /**
     * Validate properties for an articleGalley
     *
     * Perform validation checks on data used to add or edit an articleGalley.
     *
     * @param array $props A key/value array with the new data to validate
     * @param array $allowedLocales The context's supported locales
     * @param string $primaryLocale The context's primary locale
     *
     * @return array A key/value array with validation errors. Empty if no errors
     */
    public function validate(?articleGalley $object, array $props, array $allowedLocales, string $primaryLocale): array
    {
        $validator = ValidatorFactory::make(
            $props,
            $this->schemaService->getValidationRules($this->dao->schema, $allowedLocales),
            [
                'locale.regex' => __('validator.localeKey'),
                'urlPath.regex' => __('validator.alpha_dash'),
            ]
        );

        // Check required fields if we're adding a context
        ValidatorFactory::required(
            $validator,
            $object,
            $this->schemaService->getRequiredProps($this->dao->schema),
            $this->schemaService->getMultilingualProps($this->dao->schema),
            $allowedLocales,
            $primaryLocale
        );

        // Check for input from disallowed locales
        ValidatorFactory::allowedLocales($validator, $this->schemaService->getMultilingualProps($this->dao->schema), $allowedLocales);

        $errors = [];

        // The publicationId must match an existing publication that is not yet published
        $validator->after(function ($validator) use ($props) {
            if (isset($props['publicationId']) && !$validator->errors()->get('publicationId')) {
                $publication = Repo::publication()->get($props['publicationId']);
                if (!$publication) {
                    $validator->errors()->add('publicationId', __('galley.publicationNotFound'));
                } elseif (in_array($publication->getData('status'), [Submission::STATUS_PUBLISHED, Submission::STATUS_SCHEDULED])) {
                    $validator->errors()->add('publicationId', __('galley.editPublishedDisabled'));
                }
            }
        });


        if ($validator->fails()) {
            $errors = $this->schemaService->formatValidationErrors($validator->errors(), $this->schemaService->get($this->dao->schema), $allowedLocales);
        }

        HookRegistry::call('ArticleGalley::validate', [&$errors, $object, $props, $allowedLocales, $primaryLocale]);

        return $errors;
    }

    /** @copydoc DAO::insert() */
    public function add(ArticleGalley $articleGalley): int
    {
        $id = $this->dao->insert($articleGalley);
        HookRegistry::call('ArticleGalley::add', [$articleGalley]);

        return $id;
    }

    /** @copydoc DAO::update() */
    public function edit(ArticleGalley $articleGalley, array $params)
    {
        $newArticleGalley = clone $articleGalley;
        $newArticleGalley->setAllData(array_merge($newArticleGalley->_data, $params));

        HookRegistry::call('ArticleGalley::edit', [$newArticleGalley, $articleGalley, $params]);

        $this->dao->update($newArticleGalley);
    }

    /** @copydoc DAO::delete() */
    public function delete(ArticleGalley $articleGalley)
    {
        HookRegistry::call('ArticleGalley::delete::before', [$articleGalley]);
        $this->dao->delete($articleGalley);

        // Delete related submission files
        $submissionFilesIterator = Services::get('submissionFile')->getMany([
            'assocTypes' => [ASSOC_TYPE_GALLEY],
            'assocIds' => [$articleGalley->getId()],
        ]);
        foreach ($submissionFilesIterator as $submissionFile) {
            Services::get('submissionFile')->delete($submissionFile);
        }

        HookRegistry::call('ArticleGalley::delete', [$articleGalley]);
    }
}
