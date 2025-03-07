# CSV Import/Export Plugin

## Table of Contents
- [Overview](#overview)
- [Usage](#usage)
  - [Parameters](#parameters)
  - [Examples](#examples)
- [CSV Fields](#csv-fields)
  - [Issues Import](#issues-import)
    - [File Structure](#file-structure)
    - [Field Descriptions](#field-descriptions)
  - [Users Import](#users-import)
    - [Field Descriptions](#field-descriptions-1)
- [Multiple Values](#multiple-values)

## Overview

This plugin allows you to import issues and users into OJS using CSV files.

The tool processes each row (issue or user) individually. If an error is found during processing:
1. The problematic row will be saved to a new CSV file
2. A new column called 'reason' will be added to this CSV, explaining what went wrong
3. The tool will continue processing the remaining rows
4. At the end of processing, you can check the error CSV file to fix and reprocess the failed entries

For example, if your original CSV had 10 issues and 2 failed, you'll get:
- 8 issues successfully imported
- A new CSV file containing the 2 failed rows with their error descriptions
- Processing will complete for all rows, regardless of individual failures

## Usage

```bash
php tools/importExport.php CSVImportExportPlugin [command] [username] [directory] [sendWelcomeEmail]
```

### Parameters:

- `command`: Either 'issues' or 'users'
- `username`: Username of an existing user in the system who will perform the import
- `directory`: Path to the directory containing CSV files. Can be absolute (e.g., `/full/path/to/directory`) or relative to the current directory (e.g., `./relative/path`). For issues import, this directory must contain both the CSV files and all referenced assets (PDFs, images, etc.)
- `sendWelcomeEmail`: (Optional, users only) Set to true to send welcome emails to imported users

### Examples:

```bash
# Import issues
php tools/importExport.php CSVImportExportPlugin issues admin /path/to/csv/directory

# Import users with welcome email
php tools/importExport.php CSVImportExportPlugin users admin /path/to/csv/directory true
```

## CSV Fields

### Issues Import

Complete field list (in order):
```
journalPath,locale,articleTitle,articlePrefix,articleSubtitle,articleAbstract,articleGalleyFilename,authors,keywords,subjects,coverage,categories,doi,coverImageFilename,coverImageAltText,suppFilenames,suppLabels,genreName,sectionTitle,sectionAbbrev,issueTitle,issueVolume,issueNumber,issueYear,issueDescription,datePublished,startPage,endPage
```

Required fields only:
```
journalPath,locale,articleTitle,articleAbstract,articleGalleyFilename,authors,issueTitle,issueVolume,issueNumber,issueYear,datePublished
```

> **Important**: Even when using only required fields, always maintain the same field order as shown in the "Complete field list". For unused optional fields, keep them empty but preserve their position in the CSV.

#### File Structure

All files referenced in the CSV must be placed in the same directory as your CSV file. Required files:
- The CSV file(s) containing issue metadata
- Article files referenced in `articleGalleyFilename` column
- Galley files referenced in `suppFilenames` column
- Cover images referenced in `coverImageFilename` column

For example, if your CSV contains:
```
articleGalleyFilename=articleGalley.pdf,suppFilenames=suppFile1.pdf;suppFile2.pdf,coverImageFilename=cover.png
```

Your directory should contain:
```
/your/import/directory/
  ├── issues.csv
  ├── articleGalley.pdf
  ├── suppFile1.pdf
  ├── suppFile2.pdf
  └── cover.png
```

Field descriptions:

- `journalPath`: Journal path identifier
- `locale`: Content language (e.g., 'en')
- `articleTitle`: Title of the article
- `articlePrefix`: Prefix for the article title
- `articleSubtitle`: Subtitle of the article
- `articleAbstract`: Article abstract
- `articleGalleyFilename`: Name of the article's primary galley file
- `authors`: Author information with the following rules:
  - Each author's data must follow the format: "GivenName,FamilyName,email,affiliation"
  - Multiple authors must be separated by semicolons (;)
  - FamilyName, email, and affiliation are optional and can be left empty (e.g., "John,,,")
  - If email is empty, the system will use the primary contact email
  - The first author in the list will be set as the primary contact
  - Example with multiple authors:
    ```
    "John,Doe,john@email.com,University A;Jane,,jane@email.com,;Robert,Smith,,"
    ```
- `keywords`: Keywords (semicolon-separated)
- `subjects`: Subjects (semicolon-separated)
- `coverage`: Coverage information
- `categories`: Categories (semicolon-separated)
- `doi`: Digital Object Identifier
- `coverImageFilename`: Cover image file name
- `coverImageAltText`: Alt text for cover image
- `suppFilenames`: Names of supplementary files (semicolon-separated). Note only supplementary files that doesn't require dependent files are supported on this field.
- `suppLabels`: Labels for supplementary files (semicolon-separated). Must have the same number of items as `suppFilenames` to ensure correct pairing between files and labels
- `genreName`: Genre name
- `sectionTitle`: Journal section title
- `sectionAbbrev`: Section abbreviation
- `issueTitle`: Title of the issue
- `issueVolume`: Issue volume number
- `issueNumber`: Issue number
- `issueYear`: Year of publication
- `issueDescription`: Description of the issue
- `datePublished`: Publication date (YYYY-MM-DD)
- `startPage`: Starting page number
- `endPage`: Ending page number

### Users Import

Complete field list (in order):
```
journalPath,firstname,lastname,email,affiliation,country,username,tempPassword,roles,reviewInterests
```

Required fields only:
```
journalPath,firstname,lastname,email,roles
```

> **Important**: Even when using only required fields, always maintain the same field order as shown in the "Complete field list". For unused optional fields, keep them empty but preserve their position in the CSV.

Field descriptions:

- `journalPath`: Journal path identifier
- `firstname`: User's first name
- `lastname`: User's last name
- `email`: User's email address
- `affiliation`: User's institutional affiliation
- `country`: Two-letter country code
- `username`: Desired username
- `tempPassword`: Temporary password
- `roles`: User roles (semicolon-separated, e.g., "Reader;Author")
- `reviewInterests`: Review interests (semicolon-separated)

For users import, only the CSV(s) file(s) is(are) needed in your import directory.

## Multiple Values

For fields that accept multiple values:
- Use semicolons (;) to separate multiple values within a field
- For authors field:
  - Format for each author: "GivenName,FamilyName,email,affiliation"
  - FamilyName, email, and affiliation are optional (can be left empty)
  - If email is empty, the system will use the primary contact email
  - The first author in the list will be set as the primary contact
  - Multiple authors must be separated by semicolons
  - Example: "John,Doe,john@email.com,University A;Jane,,jane@email.com,;Robert,Smith,,"
- For galleys:
  - Both `suppFilenames` and `suppLabels` support multiple values
  - They must have the same number of items to ensure correct pairing between files and their labels
  - Example: if `suppFilenames=suppFile1.pdf;suppFile2.pdf`, then `suppLabels` must be something like `PDF;PDF`
