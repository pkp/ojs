FEATURE: search configuration

BACKGROUND:
  GIVEN I am on the lucene plugin search page

SCENARIO OUTLINE: enable search feature
   WHEN I check the checkbox near to {search feature}
    AND I click the "Save" button
   THEN I'll see the {effect of the search feature}
        when executing a search.

SCENARIO OUTLIN: disable search feature
  WHEN I uncheck the checkbox near to {search feature}
   AND I click the "Save" button
  THEN I'll no longer see any {effect of the search feature}
       when executing a search.

EXAMPLES:
  search feature       | effect of the search feature
  ===================================================================================
  auto-completion      | auto-completion drop-down when entering a keyword
  alternative spelling | the text "did you mean: ..." apears on the result page
  similar documents    | a button "find similar" appears next to results
  highlighting         | results contain an excerpt of the article
  faceting             | a faceting navigation box to select facet filters
  custom ranking       | a ranking weight drop-down on the section editing page
  instant search       | instant results when entering a term on the result set page
  pull indexing        | a web service with articles to be indexed is accessible
