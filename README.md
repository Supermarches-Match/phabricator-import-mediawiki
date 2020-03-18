Utilities for importing wiki page from mediawiki https://www.mediawiki.org/wiki/MediaWiki

Setup
=====

Install Phabricator, then check this out next to it. Your directory should look
like this:

```
$ ls
arcanist/
phabricator/
phabricator-import-mediawiki/
```

Config file
===============
Import use config file with JSON format :

Mandatory field : 
---------
```
{
  "wiki": {
    "url": "http(s)://mediawiki.url/",              - MediaWiki url
    "user": "xxxxxxxxxxx",                          - Login
    "pass": "xxxxxxxxxxx!"                          - Password
  },
  "conduit": {
    "token": "api-xxxxxxxxxxxxxxxxx"                - Phabricator API-token
  }
}
```

If you want to specify pages or category you can add a list of element :

```
  "categories": [                                   - Array of category name (only for import category)
    "catName1",
    "catName2",
    ...
  ],
  "pages": [                                        - Array of page name (only for import page)
    "pageName1",
    "pageName2",
    ...
  ],
```

Config 
===============
Categories will be in **catégories/** path and pages in **pages/** path. You can change it in class **src/domain/PhrictionCategory.php** and **src/domain/PhrictionPage.php**
Do not forget to modify converter service (src/service/ConverterService.php) to be sure regex rules match new prefix.

by default script are in replace mode, if page are different, script will push to phabricator. If you want to modify only page which does not exist you can use option :
```
--action insert
```

Importing category with their page
=================

Importing category will import all categories (or specified categories) with their pages. 
Categories will be add with path like **catégories/categoryName** and will contain the list of their page with links
(prefix of category are specify in French, you can change it in class src/domain/PhrictionCategory.php)

Pages will be prefixed with **pages/**

```
$ ./bin/import-mediawiki categories --config import-config.json
```


Importing pages 
=================

Importing page will import all pages without update categories link. 
All page will be in path **pages/**

```
$ ./bin/import-mediawiki pages --config import-config.json
```

Contributing
=================
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

Support
================= 
Supermarches Match does not provide support for this extension and cannot be held responsible for the use of this extension