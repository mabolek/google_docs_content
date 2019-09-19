# TYPO3 Extension `google_docs_content`

This extension is currently a Proof of concept, everything might change ;)

## Installation

- Install with composer `composer require georgringer/google-docs-content`
- Click on <https://developers.google.com/docs/api/quickstart/js> at the **Enable Google Docs API** button.
- Download the Client Configuration and save it somewhere on the server
- Setup Extension by using `./bin/typo3 googledocs:setup ./<path-to>/credentials.json` and follow the steps

## Usage

In Backend switch to new page type *Google Docs Content* and add the id of the document which you can find by checking the url if a google docs is opened.
E.g. `https://docs.google.com/document/d/1XXXXXqtDJGae67PmfU7aHSOEj5BY1Y7j4ZlcH-0ePw/edit` the ID is `1XXXXXqtDJGae67PmfU7aHSOEj5BY1Y7j4ZlcH-0ePw`

## Todos

- Improve page tca
- make a selector for finding files
- ...
