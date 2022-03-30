<?php
/**
 * @file classes/articleGalley/maps/Schema.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class articleGalley
 *
 * @brief Map articleGalleys to the properties defined in the articleGalley schema
 */

namespace APP\articleGalley\maps;

use APP\articleGalley\ArticleGalley;
use APP\core\Request;
use APP\publication\Publication;
use APP\submission\Submission;
use Illuminate\Support\Enumerable;
use PKP\context\Context;
use PKP\core\PKPApplication;
use PKP\services\PKPSchemaService;

class Schema extends \PKP\core\maps\Schema
{
    /**  */
    public Enumerable $collection;

    /** @var Submission */
    public $submission;

    /** @var Publication */
    public $publication;


    /** @copydoc EntityDAO::$schema */
    public string $schema = PKPSchemaService::SCHEMA_GALLEY;

    public function __construct(Submission $submission, Publication  $publication, Request $request, Context $context, PKPSchemaService $schemaService)
    {
        parent::__construct($request, $context, $schemaService);
        $this->publication = $publication;
        $this->submission = $submission;
    }
    /**
     * Map an articleGalley
     *
     * Includes all properties in the articleGalley schema.
     */
    public function map(ArticleGalley $item): array
    {
        return $this->mapByProperties($this->getProps(), $item);
    }

    /**
     * Summarize an articleGalley
     *
     * Includes properties with the apiSummary flag in the articleGalley schema.
     */
    public function summarize(ArticleGalley $item): array
    {
        return $this->mapByProperties($this->getSummaryProps(), $item);
    }

    /**
     * Map a collection of articleGalleys
     *
     * @see self::map
     */
    public function mapMany(Enumerable $collection): Enumerable
    {
        $this->collection = $collection;
        return $collection->map(function ($item) {
            return $this->map($item);
        });
    }

    /**
     * Summarize a collection of articleGalleys
     *
     * @see self::summarize
     */
    public function summarizeMany(Enumerable $collection): Enumerable
    {
        $this->collection = $collection;
        return $collection->map(function ($item) {
            return $this->summarize($item);
        });
    }

    /**
     * Map schema properties of an articleGalley to an assoc array
     */
    protected function mapByProperties(array $props, ArticleGalley $item): array
    {
        $output = [];
        foreach ($props as $prop) {
            switch ($prop) {
                case 'urlPublished':

                    $output['urlPublished'] = $this->request->getDispatcher()->url(
                        $this->request,
                        PKPApplication::ROUTE_PAGE,
                        $this->context->getData('urlPath'),
                        'article',
                        'view',
                        [
                            $this->submission->getBestId(),
                            'version',
                            $this->publication->getId(),
                            $item->getBestGalleyId()
                        ]
                    );
                    break;
                default:
                    $output[$prop] = $item->getData($prop);
                    break;
            }
        }

        $output = $this->schemaService->addMissingMultilingualValues($this->schema, $output, $this->context->getSupportedFormLocales());

        ksort($output);

        return $this->withExtensions($output, $item);
    }
}
