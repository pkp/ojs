FEATURE: auto-completion

BACKGROUND:
  GIVEN I enabled the auto-completion feature

SCENARIO OUTLINE: auto-completion for simple search
  GIVEN I am on a page in {search scope}
   WHEN I enter a {letter} combination into the
        search field
   THEN I'll see an auto-completion drop down with at
        least {proposals} for auto-completion from {search
        scope}
    AND I'll not see proposals that are {not in scope}.

EXAMPLES:
  search scope | letter | proposals                     | not in scope
  ====================================================================
  all journals | ch     | chicken, chickenwings, chilly |
  lucene-test  | ch     | chicken, chickenwings         | chilly


SCENARIO OUTLINE: auto-completion for advanced search
  GIVEN I am on the advanced search page in {search scope}
        context
   WHEN I enter a {letter} combination into a {search field}
   THEN I'll see an auto-completion drop down with at
        least {proposals} for auto-completion from {search
        scope}
    AND I'll not see proposals that are {not in scope}.

EXAMPLES:
  search scope | search field   | letter | proposals            | not in scope
  ==============================================================================
  all journals | all categories | te     | test, tester, tests  |
  lucene-test  | all categories | te     | test, tests          | tester
  lucene-test  | authors        | au     | author, authorname   |
  all journals | title          | te     | test, testartikel    |
  lucene-test  | title          | te     | test                 | testartikel
  lucene-test  | full text      | nu     | nutella              |
  lucene-test  | suppl. files   | ma     | mango                |
  lucene-test  | disciplines    | die    | dietary              |
  all journals | keywords       | t      | topology, time       |
  all journals | keywords       | to     | topology             | time 
  lucene-test  | keywords       | t      | time                 | topology, test
  lucene-test  | types          | ex     | experienc            | exotic
  lucene-test  | coverage       | c      | century              | chicken


SCENARIO: auto-completion selection
  GIVEN I have entered the letter combination 'ch' in
        journal context
    AND I am seeing an auto-completion drop down with
        'chicken' as its first entry
   WHEN I press the 'enter' key
   THEN the word 'chicken' will automatically be
        entered into the search field
