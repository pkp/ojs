{**
 * viewPage.tpl
 *
 * MH 2012-01-01:
 *
 * For eScholarship journals, we don't want to show the OJS-generated front-end page,
 * but rather redirect to our front-end on escholarship.org. So this little redirect
 * page replaces the normal issue page.
 *}

{* 
 *}
<html>
  <head>
    <title>eScholarship redirect</title>
    <meta http-equiv="REFRESH" content="0;url=http://escholarship.org/uc/search?entity={$currentJournal->getPath()};volume={$issue->getVolume()};issue={$issue->getNumber()}"></meta>
  </head>
  <body>
  </body>
</html>

