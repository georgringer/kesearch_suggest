# TYPO3 Extension `kesearch_suggest`

Hacked together endpoint for the TYPO3 extension ke_search to build an autocomplete/suggest search.

## Setup

Create any page and use the following TypoScript. Modify/Remove the condition to your own needs.
Imagine the page slug is "endpoint".

```typo3_typoscript
[traverse(page, "uid") == 170]
    config >
    config.no_cache = 1
    config.disableAllHeaderCode = 1
    config.additionalHeaders.10.header = Content-type:application/json
    page >
    page = PAGE
    page.10 = USER
    page.10 {
        userFunc = GeorgRinger\KesearchSuggest\Controller\SuggestController->main
    }
[END]
```

The endpoint can be reached with the url
`https://domain.tld/endpoint?tx_kesearch_pi1%5Bsword%5D=XXXXXX&tx_kesearch_pi1[configuration]=123`

Replace the following values:
- `XXXXXX` with the search term
- `123` with the UID of the search plugin. It is the same element which is also referenced in the plugin "Faceted search - Results [ke_search_pi2]"


## Response

As ke_search is a fulltext search and not using an index based on words, the autocomplete suggestion uses **successful** search terms.
The response is a JSON object with the following structure:

```json
{
    "autocomplete": [
        "term1",
        "term2",
        "term3"
    ],
    "results": [
        {
            "uid": 123,
            "targetpid": 1,
            "params": "",
            "type": "page",
            "orig_uid": 23,
            "title": "The page title",
            "score": 4.91919
        },
        {
            "...": "..."
        }
    ]
}
```
