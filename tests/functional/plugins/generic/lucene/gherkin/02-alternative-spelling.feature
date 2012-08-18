FEATURE: alternative spelling suggestions

BACKGROUND:
  GIVEN I enabled the alternative spelling feature

SCENARIO: alternative spelling proposal
   WHEN I execute a simple search with the keyword "nutela"
   THEN I'll see an additional link "Did you mean 'nutella'" above the
        result list.

SCENARIO: alternative spelling search
  GIVEN I have executed a simple search with the keyword "nutela"
    AND I see a link "Did you mean 'nutella'" above the result list
   WHEN I click this link
   ThEN I'll see the result set corresponding to the keyword "nutella".