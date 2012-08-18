FEATURE: pull indexing (OJS side only)

BACKGROUND:
  GIVEN I enabled the pull indexing feature

SCENARIO: publishing or changing an article
   WHEN I publish or change an article
    BUT I do not unpublish the article
   THEN an article setting "dirty" will be set to "1" which 
        indicates that the article must be re-indexed
    AND the article will appear in the public XML
        web service for pull-indexing
    BUT the article will not be marked for deletion.

SCENARIO: unpublishing an article
   WHEN I unpublish a previously published article
   THEN an article setting "dirty" will be set to "1" which 
        means that the article must be deleted from the index
    AND the article will appear in the public XML
        web service for pull-indexing.
    AND the article will be marked for deletion.

SCENARIO: pull request
  WHEN the server receives a pull request
  THEN all articles appearing in the request will be marked
       "clean" once the request was successfully transferred to the
       server side.

For a specification of server side processing and for a full picture
of pull processing, please see http://pkp.sfu.ca/wiki/index.php/OJSdeSearchConcept#Pull_Processing.