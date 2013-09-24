# Beteiligungskompass

## Description

Originally, the web platform "Beteiligungskompass.org" was initiated by the Bertelsmann Stiftung as a portal to help people in the public, private and not-for-profit sectors who need to involve a wider group of people in their work. The portal was created to provide information, advice, case studies and opportunities to share experiences with others helping to make public participation activities as effective as possible.

The portal is mainly aimed at people who are directly involved in planning, running or commissioning participation activities.

This is the Open Source release of the web platform.

## System requirements
* Unix/Linux style server
* PHP 5.4.* (PHP 5.4.10 recommended)
* MySQL 5.1 or newer
* Apache HTTP server
* Apache Solr search server
* Apache Tomcat (web container to run Solr)
* wkhtmltopdf (command line tool to render HTML to PDF)
* ImageMagick (with installed "convert" command line tool)

### Developer Requirements (optional)
* Ruby 1.9.3 with Ruby Gems
* compass (for local development)
	* `gem install compass`
* capistrano (for deployment)
	* `gem install capistrano`

## Installation/Setup

### 1. Getting all the sources
1. clone into the repository
2. update the kohana system sources
	* `git submodule update`

### 2. Configuring the server
* Add an entry to your Apache's VHOSTS file and set it to the folder `/PROJECTFOLDER_PATH/public/` inside the project folder.
* Create a Database with collation `utf8_general_ci`

### 3. Update connection Settings
* All connection settings can be found in `/PROJECTFOLDER_PATH/application/config/`
* Open the `doctrine.php` and enter your Database connection settings.
* Open the `solr.php` and enter the connection settings for your instance of the Solr search engine server.
* Open the `project.php` and update the settings of the `$baseConfig` to reflect your environment variables.
* Open the `environment.php` and enter your system paths accordingly.

### 4. Create DB schema and dummy users
* in the terminal navigate to the folder `/PROJECTFOLDER_PATH/modules/doctrine2/`
* enter the command `php doctrine.php orm:schema-tool:create`
* in the terminal navigate to the folder `/PROJECTFOLDER_PATH/public/`
* enter command: `php index.php --uri=backend/users/fixtures`
* this will create the following users:
	* **Admin:** admin@admin.com pw: admin
	* **Editor:** editor@editor.com pw: editor
	* **User:** user@user.com pw: user

### 4. Initiate Solr
* Put the Solr schema file `SCHEMA.XML` from the root repository into your Solr instances schema directory under the solr home (i.e. ./solr/conf/schema.xml by default) or located where the classloader for the Solr webapp can find it.
* in the terminal navigate to the folder `/PROJECTFOLDER_PATH/public/`
* enter command: `php index.php --uri=backend/search/buildSolrIndex`
* the Solr index will be initialised or updated accordingly.

### 5. Generating App-Thumbnails
* in the terminal navigate to the folder `/PROJECTFOLDER_PATH/public/`
* enter command: `php index.php --uri=api/update_thumbs`

### 6. Creating CRON tasks
* RSS Import: `0 2 * * * /usr/bin/php -c /PATH/TO/YOUR/php.ini /PATH/TO/YOUR/public/index.php --uri=backend/rss/importrss >> /PATH/TO/YOUR/LOG/FOLDER 2>&1`
* Solr Index: `0 3 * * * /usr/bin/php -c /PATH/TO/YOUR/php.ini /PATH/TO/YOUR/public/index.php --uri=backend/search/buildSolrIndex`
* Thumbnail Cache: `0,30 * * * * /usr/bin/php -c /PATH/TO/YOUR/php.ini /PATH/TO/YOUR/public/index.php --uri=api/update_thumbs >> /PATH/TO/YOUR/LOG/FOLDER 2>&1`


## Capistrano Deployment Settings
Capistrano was chosen for the deployment workflow of the Beteiligungskompass web application. All Capistrano settings can be found in `/PROJECTFOLDER_PATH/config`.

There are different environment settings for typical deployment and testing workflows:

* **Local:** For your local working copy. This is only used for development.
* **Test:** For deploying to a server that works as a testing environment.
* **Stage:** For deploying to a server that works as a Staging environment.
* **Production:** For deploying to a server that works as the Live/Production website environment.

There are 3 different localizations available:

* **DE:** German language
* **EN:** English language
* **SE:** Swedish language

For each localization there are different message files with localized content.

## Folder structure
The website is build with the [KohanaPHP Framework](http://kohanaframework.org/) and follows its structure for building web applications.

	PROJECTFOLDER
	   |--/application
	     |--/cache
	     |--/classes
	     |--/config
	     |--/data
	     |--/i18n
	     |--/logs
	     |--/messages
	     |--/smarty_plugins
	     |--/stylesheets
	     |--/views
	   |--/config
	     |--/envs
	     |--/tools
	   |--/modules
	     |--/cssmin
	     |--/doctrine2
	     |--/jsmin
	     |--/smarty
	     |--/yaml
	   |--/public
	   |--/system

* **`/application/stylesheets`**: This folder contains all SCSS files to build the sites' CSS files.