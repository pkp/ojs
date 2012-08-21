FEATURE: faceting

BACKGROUND:
  GIVEN I enabled the faceting feature

SCENARIO: faceting filter navigation
   WHEN I execute a search that produces results with (at least)
        the articles with ids 1, 3 and 4
   THEN I see a faceting block plugin wich offers the categories
        "Discipline", "Keyword", "Type (method/approach)",
        "Coverage", "Publication Month" and "Journal"
    AND I'll see one or more clickable faceting filters
        below each category.

SCENARIO: faceting filter navigation with empty categories
   WHEN I execute a search that produces results with
        the article with id 3
   THEN I see a faceting block plugin wich offers the categories
        "Discipline", "Keyword", "Type (method/approach)",
        "Coverage", "Publication Month" and "Journal"
    BUT the "Keyword", "Type (method/approach)" and "Coverage"
        categories will be inactive and no faceting filters
        will be listed below them.

SCENARIO: disabled categories
  GIVEN I disable one of the keyword categories in the journal
        setup
   WHEN I execute a search
   THEN I see a faceting block plugin which does not show
        the disabled category.

SCENARIO OUTLINE: facet filter selection
  GIVEN I executed a search containing the articles with {id 1}
        and {id 2}
   WHEN I click on {facet filter}
   THEN I'll see a refined result list containing the article
        with {id 1}
    BUT I'll no longer see the article with {id 2} in the result
    AND I'll see the selected facet above the result set with the
        text "filtered by: {facet filter} [X]"
    AND the category of {facet filter} no longer appears in the
        facet filter navigation block.

EXAMPLES:
  id 1 | id 2 | facet filter
  ============================================
  3    | 4    | discipline: "exotic food"
  4    | 3    | keyword: "exotic food"
  4    | 3    | type: "personal experience"
  4    | 3    | coverage: "21st century"
  3    | 4    | publ. date: "2012-08"
  3    | 1    | journal: "lucene-test"
  3    | 4    | author: "Authorname Second, A"    // FIXME: Will authors be shown as a list or separately?  

SCENARIO: multiple facet filter selection
  GIVEN I executed a search originally containing the
        articles with the ids 1, 3 and 4
    BUT I selected a publication date facet "2012-08" so
        that the article with id 3 is no longer in
        the result set
   WHEN I select an additional journal facet "lucene-test"
   THEN I will only see the article with id 4 in the
        remaining result set.
    AND I'll see the selected facets above the result set with the
        text "filtered by: publ. date: ... [X], journal: ... [X]"
    AND I'll no longer see the publ. date or journal categories
        in the facet filter navigation block.

SCENARIO: facet filter deletion
  GIVEN I executed a faceted search with a publication date
        and a journal facet
    AND the result set contains the article with id 4 but
        not articles 1 or 3
   WHEN I click the "X"-button near to the publication date
        facet above the result list
   THEN I'll see articles 3 and 4 in the result set
    AND I'll see the publication date category in the filter
        navigation block.
