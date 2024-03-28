<?php

namespace APP\orcid;

use APP\author\Author;
use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\journal\Journal;
use APP\plugins\generic\citationStyleLanguage\CitationStyleLanguagePlugin;
use APP\publication\Publication;
use APP\submission\Submission;
use Carbon\Carbon;
use PKP\context\Context;
use PKP\plugins\PluginRegistry;

class OrcidWork
{
    public const PUBID_TO_ORCID_EXT_ID = ['doi' => 'doi', 'other::urn' => 'urn'];
    public const USER_GROUP_TO_ORCID_ROLE = ['Author' => 'AUTHOR', 'Translator' => 'CHAIR_OR_TRANSLATOR', 'Journal manager' => 'AUTHOR'];

    private array $data = [];

    public function __construct(
        private Publication $publication,
        private Context $context,
        private array $authors,
        private ?Issue $issue = null
    ) {
        $this->data = $this->build();
    }

    public function toArray(): array
    {
        return $this->data;
    }

    private function build(): array
    {
        $submission = Repo::submission()->get($this->publication->getData('submissionId'));

        $applicationName = Application::get()->getName();
        $bibtexCitation = '';

        $publicationLocale = ($this->publication->getData('locale')) ? $this->publication->getData('locale') : 'en';
        $supportedSubmissionLocales = $this->context->getSupportedSubmissionLocales();


        $publicationUrl = Application::get()->getDispatcher()->url(
            Application::get()->getRequest(),
            Application::ROUTE_PAGE,
            $this->context->getPath(),
            'article',
            'view',
            $submission->getId()
        );

        $orcidWork = [
            'title' => [
                'title' => [
                    'value' => trim(strip_tags($this->publication->getLocalizedTitle($publicationLocale))) ?? ''
                ],
                'subtitle' => [
                    'value' => trim(strip_tags($this->publication->getLocalizedData('subtitle', $publicationLocale))) ?? ''
                ]
            ],
            'journal-title' => [
                'value' => $this->context->getName($publicationLocale) ?? ''
            ],
            'short-description' => trim(strip_tags($this->publication->getLocalizedData('abstract', $publicationLocale))) ?? '',

            'external-ids' => [
                'external-id' => $this->buildOrcidExternalIds($submission, $this->publication, $this->context, $this->issue, $publicationUrl)
            ],
            'publication-date' => $this->buildOrcidPublicationDate($this->publication, $this->issue),
            'url' => $publicationUrl,
            'language-code' => substr($publicationLocale, 0, 2),
            'contributors' => [
                'contributor' => $this->buildOrcidContributors($this->authors, $this->context, $this->publication)
            ]
        ];

        if ($applicationName == 'ojs2') {
            PluginRegistry::loadCategory('generic');
            $citationPlugin = PluginRegistry::getPlugin('generic', 'citationstylelanguageplugin');
            /** @var CitationStyleLanguagePlugin $citationPlugin */
            $bibtexCitation = trim(strip_tags($citationPlugin->getCitation($this->request, $submission, 'bibtex', $this->issue, $this->publication)));
            $orcidWork['citation'] = [
                'citation-type' => 'bibtex',
                'citation-value' => $bibtexCitation,
            ];
            $orcidWork['type'] = 'journal-article';
        } elseif ($applicationName == 'ops') {
            $orcidWork['type'] = 'preprint';
        }

        $translatedTitleAvailable = false;
        foreach ($supportedSubmissionLocales as $defaultLanguage) {
            if ($defaultLanguage !== $publicationLocale) {
                $iso2LanguageCode = substr($defaultLanguage, 0, 2);
                $defaultTitle = $this->publication->getLocalizedData($iso2LanguageCode);
                if (strlen($defaultTitle) > 0 && !$translatedTitleAvailable) {
                    $orcidWork['title']['translated-title'] = ['value' => $defaultTitle, 'language-code' => $iso2LanguageCode];
                    $translatedTitleAvailable = true;
                }
            }
        }

        return $orcidWork;
    }

    /**
     * Build the external identifiers ORCID JSON structure from article, journal and issue meta data.
     *
     * @see  https://pub.orcid.org/v2.0/identifiers Table of valid ORCID identifier types.
     *
     * @param Submission $submission The Article object for which the external identifiers should be build.
     * @param Publication $publication The Article object for which the external identifiers should be build.
     * @param Journal $context Context the Submission is part of.
     * @param Issue $issue The Issue object the Article object belongs to.
     *
     * @return array            An associative array corresponding to ORCID external-id JSON.
     */
    private function buildOrcidExternalIds($submission, $publication, $context, $issue, $articleUrl)
    {
        $contextId = $context->getId();

        $externalIds = [];
        $pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $contextId);
        // Add doi, urn, etc. for article
        $articleHasStoredPubId = false;
        if (is_array($pubIdPlugins) || $context->areDoisEnabled()) {
            // Handle non-DOI pubIds
            if (is_array($pubIdPlugins)) {
                foreach ($pubIdPlugins as $plugin) {
                    if (!$plugin->getEnabled()) {
                        continue;
                    }

                    $pubIdType = $plugin->getPubIdType();

                    # Add article ids
                    $pubId = $publication->getStoredPubId($pubIdType);

                    if ($pubId) {
                        $externalIds[] = [
                            'external-id-type' => self::PUBID_TO_ORCID_EXT_ID[$pubIdType],
                            'external-id-value' => $pubId,
                            'external-id-url' => [
                                'value' => $plugin->getResolvingURL($contextId, $pubId)
                            ],
                            'external-id-relationship' => 'self'
                        ];

                        $articleHasStoredPubId = true;
                    }

                    # Add issue ids if they exist
                    $pubId = $issue->getStoredPubId($pubIdType);
                    if ($pubId) {
                        $externalIds[] = [
                            'external-id-type' => self::PUBID_TO_ORCID_EXT_ID[$pubIdType],
                            'external-id-value' => $pubId,
                            'external-id-url' => [
                                'value' => $plugin->getResolvingURL($contextId, $pubId)
                            ],
                            'external-id-relationship' => 'part-of'
                        ];
                    }
                }

                // Handle DOIs
                if ($context->areDoisEnabled()) {
                    # Add article ids
                    $doiObject = $publication->getData('doiObject');

                    if ($doiObject) {
                        $externalIds[] = [
                            'external-id-type' => self::PUBID_TO_ORCID_EXT_ID['doi'],
                            'external-id-value' => $doiObject->getData('doi'),
                            'external-id-url' => [
                                'value' => $doiObject->getResolvingUrl()
                            ],
                            'external-id-relationship' => 'self'
                        ];

                        $articleHasStoredPubId = true;
                    }
                }

                # Add issue ids if they exist
                if ($issue) {
                    $doiObject = $issue->getData('doiObject');
                    if ($doiObject) {
                        $externalIds[] = [
                            'external-id-type' => self::PUBID_TO_ORCID_EXT_ID['doi'],
                            'external-id-value' => $doiObject->getData('doi'),
                            'external-id-url' => [
                                'value' => $doiObject->getResolvingUrl()
                            ],
                            'external-id-relationship' => 'part-of'
                        ];
                    }
                }
            }
        } else {
            error_log('OrcidProfilePlugin::buildOrcidExternalIds: No pubId plugins could be loaded');
        }

        if (!$articleHasStoredPubId) {
            // No pubidplugins available or article does not have any stored pubid
            // Use URL as an external-id
            $externalIds[] = [
                'external-id-type' => 'uri',
                'external-id-value' => $articleUrl,
                'external-id-relationship' => 'self'
            ];
        }

        // Add journal online ISSN
        // TODO What about print ISSN?
        if ($context->getData('onlineIssn')) {
            $externalIds[] = [
                'external-id-type' => 'issn',
                'external-id-value' => $context->getData('onlineIssn'),
                'external-id-relationship' => 'part-of'
            ];
        }

        return $externalIds;
    }

    /**
     * Parse issue year and publication date and use the older on of the two as
     * the publication date of the ORCID work.
     *
     * @param null|mixed $issue
     *
     * @return array Associative array with year, month and day or only year
     */
    private function buildOrcidPublicationDate($publication, $issue = null)
    {
        $publicationPublishDate = Carbon::parse($publication->getData('datePublished'));

        return [
            'year' => ['value' => $publicationPublishDate->format('Y')],
            'month' => ['value' => $publicationPublishDate->format('m')],
            'day' => ['value' => $publicationPublishDate->format('d')]
        ];
    }

    /**
     * Build associative array fitting for ORCID contributor mentions in an
     * ORCID work from the supplied Authors array.
     *
     * @param Author[] $authors Array of Author objects
     *
     * @return array[]           Array of associative arrays,
     *                           one for each contributor
     */
    private function buildOrcidContributors($authors, $context, $publication)
    {
        $contributors = [];
        $first = true;

        foreach ($authors as $author) {
            // TODO Check if e-mail address should be added
            $fullName = $author->getLocalizedGivenName() . ' ' . $author->getLocalizedFamilyName();

            if (strlen($fullName) == 0) {
                OrcidManager::logError('Contributor Name not defined' . $author->getAllData());
            }
            $contributor = [
                'credit-name' => $fullName,
                'contributor-attributes' => [
                    'contributor-sequence' => $first ? 'first' : 'additional'
                ]
            ];

            $userGroup = $author->getUserGroup();
            $role = self::USER_GROUP_TO_ORCID_ROLE[$userGroup->getName('en')];

            if ($role) {
                $contributor['contributor-attributes']['contributor-role'] = $role;
            }

            if ($author->getOrcid()) {
                $orcid = basename(parse_url($author->getOrcid(), PHP_URL_PATH));

                if ($author->getData('orcidSandbox')) {
                    $uri = ORCID_URL_SANDBOX . $orcid;
                    $host = 'sandbox.orcid.org';
                } else {
                    $uri = $author->getOrcid();
                    $host = 'orcid.org';
                }

                $contributor['contributor-orcid'] = [
                    'uri' => $uri,
                    'path' => $orcid,
                    'host' => $host
                ];
            }

            $first = false;

            $contributors[] = $contributor;
        }

        return $contributors;
    }
}
