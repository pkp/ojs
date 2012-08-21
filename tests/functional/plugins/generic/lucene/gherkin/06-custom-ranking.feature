FEATURE: custom ranking

BACKGROUND:
  GIVEN I enabled the custom-ranking feature

SCENARIO: ranking weight selector
   WHEN I go to the section editing page
   THEN I see a drop down box with custom ranking factors:
        "never show", "less important", "normal" and
        "most important"
    AND the ranking weight "normal" is selected by default.

SCENARIO: ranking weight editing and effect
  GIVEN I executed a search that shows four articles from four
        different sections when those sections have a "normal"
        ranking weight
    AND their ranking score is exactly equal
   WHEN I go to the editing page of the first section
    AND I save a ranking weight "never show"
    AND I go to the editing page of the second section
    AND I save a ranking weight "less important"
    AND I go to the editing page of the fourth section
    AND I save a ranking weight "most important"
    AND I re-execute the exact same search
   THEN I'll no longer see the article from the first section
        in the result set
    AND I'll see the article from the second, third and fourth
        sections in the third, second and first positions, repectively
    AND their ranking scores will be multiplied by the factors
        0.5, 1 and 2 respectively.
