CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Recommended modules
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

Tagify provide a widget to transform entity reference fields into a more
user-friendly tags component, in an easy, customizable way, with great
performance and small code footprint.

* For a full description of the module, visit the project page:
  https://www.drupal.org/project/tagify

* To submit bug reports and feature suggestions, or to track changes:
  https://www.drupal.org/project/issues/search/tagify


REQUIREMENTS
------------

No special requirements.


RECOMMENDED MODULES
-------------------

No recommended modules.

INSTALLATION
------------

The module is using CDNs to give flexibility to the installation unless you
want to add libraries.

See options below in the optional installation section:

OPTION 1 - DOWNLOAD REQUIRED LIBRARIES (OPTIONAL INSTALLATION)
--------------------------------------------------------------

1. Download required libraries (Tagify and Dragsort):
   https://github.com/yairEO/tagify/archive/refs/tags/v4.9.8.zip
   https://github.com/yairEO/dragsort/archive/refs/tags/v1.2.0.zip
2. [Drupal 8-9] Extract the libraries under libraries/tagify and
libraries/dragsort.
3. Download and enable the module.

* Install as you would normally install a contributed Drupal module.
  See: https://www.drupal.org/node/895232 for further information.

OPTION 2 - COMPOSER (OPTIONAL INSTALLATION)
-------------------------------------------

1. Copy the following into your project's composer.json file.
```json
"repositories": [
    {
      "type": "package",
      "package": {
        "name": "yairEO/tagify", 
        "version": "4.9.2",
        "type": "drupal-library",
        "dist": {
          "url": "https://github.com/yairEO/tagify/archive/refs/tags/v4.9.2.zip",
          "type": "zip"
        } 
      }
    },
    {
      "type": "package", 
      "package": {
        "name": "yairEO/dragsort",
        "version": "1.2.0",
        "type": "drupal-library",
        "dist": {
          "url": "https://github.com/yairEO/dragsort/archive/refs/tags/v1.2.0.zip",
          "type": "zip"
        }
      }
    }        
]
```

3. Ensure you have following mapping inside your composer.json.
```json
"extra": {
  "installer-paths": {
    "web/libraries/{$name}": ["type:drupal-library"]
  }
}
```

4. Run following command to download required library.
```php
composer require yairEO/tagify
composer require yairEO/dragsort
```

5. Enable the Tagify module

CONFIGURATION
-------------

No configuration is needed.

MAINTAINERS
-----------

Current maintainers:
* David Galeano (gxleano) - https://www.drupal.org/u/gxleano
