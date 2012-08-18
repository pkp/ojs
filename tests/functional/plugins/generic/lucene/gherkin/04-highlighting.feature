FEATURE: highlighting

BACKGROUND:
  GIVEN I enabled the highlighting feature

SCENARIO: highlighting
   WHEN I execute a simple search that returns at
        least one result
   THEN I'll see a short excerpt of each article's full
        text containing my search keywords
    AND my search keywords are visually emphasized.
