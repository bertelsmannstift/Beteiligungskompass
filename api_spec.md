# API spec

Disclaimer: All calls are using the HTTP method GET, even if the requests are not idempotent (like adding a new favorite group).

## API Key

All calls use the API - key as get parameter in the form:

	?api_key=j90inhuf1qx4myb8tqo5otrlpdzEXAMPLE

**If missing, a 403 Response is sent**

## Export

	HOST/api/export

* Returns the non-user specific data as a sqlite DB file

## Timezone

	HOST/api/get_timezone

* Call only once per sync

## JSON App Texts

	HOST/api/get_strings

## Module Types

	HOST/api/get_module_types

## Module Status

	HOST/api/is_module_active

* **GET-Parameters:**

  * param=module_type_name

## get_terms - Get Privacy/Terms as HTML

    HOST/api/get_terms?api_key=xyz

* **Returns**

  * on success: HTML formatted content of the page, e.g. {"response":{"pagecontent":"<p>Foo</p>"}}
  * on error: 404 (Not Found)

## login - Get user id and token for user identified by email and password

* **GET-Parameter**

  * email - the email address of the user
  * password - cleartext password, encrypted via https

  * Example request:

		HOST/api/login?api_key=KEY&email=abc@example.org&password=test1234
---

* **Returns**

  * on success: JS property "**token**" and "userid" in JSON (application/json), e.g. {"response":{"token":"abc123", "userid":123}}
  * on error "Missing parameters": **400 (Bad Request)**
  * on error "wrong credentials or missing API key": **403 (Forbidden)**
  * on error "user token could not be generated": **500 (Internal Server Error)**

## add_favorite - Add an article to user's favorites

* **GET-Parameter**

    * token - the user token as received through the login call
    * article_id - the article to be added to the user's favorites

    HOST/api/add_favorite?api_key=KEY&token=abc&article_id=1

* **Returns**

        {
            "response": {
                "success": true
            }
        }

        * on error "Missing article ID": **400 (Bad Request)**
        * on error "Missing API token"/"Invalid API token": **403 (Forbidden)**

## remove_favorite - Remove an article from user's favorites

* **GET-Parameter**

    * token - the user token as received through the login call
    * article_id - the article to be removed from the user's favorites

    HOST/api/remove_favorite?api_key=KEY&token=abc&article_id=1

* **Returns**

        {
            "response": {
                "success": true
            }
        }

        * on error "Missing article ID": **400 (Bad Request)**
        * on error "Missing API token"/"Invalid API token": **403 (Forbidden)**

## add_favorite_group

* **GET-Parameter**

    * token - the user token as received through the login call
    * title - the title of the new group

* **Returns**

        {
            "response": {
                "success": true,
                "group_id": 1,
            }
        }

    HOST/api/add_favorite_group?api_key=KEY&token=abc&title=Hallo%20Welt

## remove_favorite_group

* **GET-Parameter**

    * token - the user token as received through the login call
    * group_id - the id of the group to be deleted

    HOST/api/remove_favorite_group?api_key=xyz&token=abc&group_id=1

## add_article_to_favorite_group

* **GET-Parameter**

    * token - the user token as received through the login call
    * group_id - the id of the group
    * article_id - the id of the article

    HOST/api/add_article_to_favorite_group?api_key=KEY&token=abc&group_id=1&article_id=1

## remove_article_from_favorite_group

* **GET-Parameter**

    * token - the user token as received through the login call
    * group_id - the id of the group
    * article_id - the id of the article

    HOST/api/remove_article_from_favorite_group?api_key=KEY&token=abc&group_id=1&article_id=1

## get_favorites - Get all favorites for logged in user

* **GET-Parameter**

    * token - the user token as received through the login call

* **Returns**

  * on success: JS property "**favoriteInfo**" in JSON, e.g.

        {
            "response": {
                "favoriteInfo": {
                    "allFavorites": [
                        274,
                        381,
                        221,
                        220
                    ],
                    "favoriteGroups": {
                        "Name der Gruppe": {
                            "id": 1,
                            "articles": [
                                521,
                                274
                            ],
                            "sharelink": "favorites/showgroup/5/91922800eb16af310683b5173f465b25"
                        }
                    }
                }
            }
        }

  * Minimal-Response:

        {
            "response": {
                "favoriteInfo": {
                    "allFavorites": [],
                    "favoriteGroups": {}
                }
            }
        }

  * on error "Missing API token"/"Invalid API token": **403 (Forbidden)**

## add_article - POST a new article

* **GET-Parameter**

    * token - the user token as received through the login call

    HOST/api/add_article?api_key=KEY&token=abc

* **POST-Body** - containing JSON representation of article

    {
        "article": {
            [...]
        }
    }


* **Returns**

* Positive Response:

    {
        "response": {
            "success": true,
            "article_id": 1,
            "images": [1,2,3]
        }
    }

* Negative Response:

    {
        "response": {
            "success": false,
            "article_id": null,
            "images": []
        }
    }