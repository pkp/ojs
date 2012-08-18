FEATURE: similar documents

BACKGROUND:
  GIVEN I enabled the similar documents feature

SCENARIO: propose similar documents
   WHEN I execute a simple search that returns at
        least one result
   THEN The result list will contain a button behind
        each item of the result list: "find similar"

SCENARIO: find similar documents
  GIVEN I executed a simple search that returned at
        least one result
    AND I see a "find similar" button behind each item
        of the result list
   WHEN I click the "find similar" button of an item
   THEN I'll see a result set containing articles containing
        similar keywords as defined by solr's default
        similarity algorithm.
