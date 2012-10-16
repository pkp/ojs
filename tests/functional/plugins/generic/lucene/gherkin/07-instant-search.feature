FEATURE: instant search

BACKGROUND:
  GIVEN I enabled the instant search feature

SCENARIO: simple search box on the advanced result page
   WHEN I execute an advanced search
   THEN I'll see a simple search box above the result set

SCENARIO: instant search
  GIVEN I am on the search results page
    AND I start entering a search query into the
        search box
   THEN I'll see the result set changing immediately
        to correspond to the query terms I'm entering
        in the search box.

SCENARIO: undo instant search
  GIVEN I entered a search query into the search box
    AND I'm seeing a dynamic result set
   WHEN I clear the search box
   THEN I'll see the result of the last non-instant
        search.
