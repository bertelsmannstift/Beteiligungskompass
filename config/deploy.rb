################################################################################
# Capistrano recipe for deploying the Beteiligungskompass web application
################################################################################

# Used for all deploys
# WARNING: the "stages" hash keys are used in the filepath to the DB config
stages = Hash[
	"test" =>
		Hash[
			"user" => "USER",
			"password" => "PASSWORD",
			"port" => "PORT",
			"url" => "http://SD.HOST.TLD",
			"application" => "SD.HOST.TLD",
			"deploy_to" => "/home/SD.HOST.TLD/DEPLOY_PATH/",
			"lang" => "LOCALE",
		],

	#####################
	# Stage environments
	#####################
	"stage-de" =>
		Hash[
			"user" => "USER",
			"password" => "PASSWORD",
			"port" => "PORT",
			"url" => "http://SD.HOST.TLD",
			"application" => "SD.HOST.TLD",
			"deploy_to" => "/home/SD.HOST.TLD/DEPLOY_PATH/",
			"lang" => "LOCALE",
		],

    "stage-en" =>
		Hash[
			"user" => "USER",
			"password" => "PASSWORD",
			"port" => "PORT",
			"url" => "http://SD.HOST.TLD",
			"application" => "SD.HOST.TLD",
			"deploy_to" => "/home/SD.HOST.TLD/DEPLOY_PATH/",
			"lang" => "LOCALE",
		],

    "stage-se" =>
		Hash[
			"user" => "USER",
			"password" => "PASSWORD",
			"port" => "PORT",
			"url" => "http://SD.HOST.TLD",
			"application" => "SD.HOST.TLD",
			"deploy_to" => "/home/SD.HOST.TLD/DEPLOY_PATH/",
			"lang" => "LOCALE",
		],

	##########################
	# Production environments
	##########################

	# Production German
	"production-de" =>
		Hash[
			"user" => "USER",
			"password" => "PASSWORD",
			"port" => "PORT",
			"url" => "http://SD.HOST.TLD",
			"application" => "SD.HOST.TLD",
			"deploy_to" => "/home/SD.HOST.TLD/DEPLOY_PATH/",
			"lang" => "LOCALE",
		],

	# Production English
	"production-en" =>
		Hash[
			"user" => "USER",
			"password" => "PASSWORD",
			"port" => "PORT",
			"url" => "http://SD.HOST.TLD",
			"application" => "SD.HOST.TLD",
			"deploy_to" => "/home/SD.HOST.TLD/DEPLOY_PATH/",
			"lang" => "LOCALE",
		],

	# Production Swedish
	"production-se" =>
		Hash[
			"user" => "USER",
			"password" => "PASSWORD",
			"port" => "PORT",
			"url" => "http://SD.HOST.TLD",
			"application" => "SD.HOST.TLD",
			"deploy_to" => "/home/SD.HOST.TLD/DEPLOY_PATH/",
			"lang" => "LOCALE",
		],
]

begin
	# try to read the command line argument "env"
	environment = env
rescue Exception => e
	puts "Missing argument 'env'. Example usage: 'cap deploy -S env=stage'. Possible values for the 'env' argument are:"
	p stages.keys
	abort()
end
if !stages.has_key? environment
	puts "Invalid value for argument 'env'. Possible values are:"
	p stages.keys
	abort()
end
puts "\nEnvironment: '%s'\n\n" % [stages.fetch("#{environment}").fetch("url")]


# list all environment dependent message files here
# (they will be backuped and merged during deploy)
lang = stages.fetch("#{environment}").fetch("lang");
msgfiles = [
    "mobile.messages.#{lang}",
    "mobile_message_groups.#{lang}",
    "project.messages.frontend.#{lang}",
    "project.messages.backend.#{lang}",
    "backend_message_groups.#{lang}",
    "frontend_message_groups.#{lang}",
]

# this must match a key in the "stages" hash
# set(:environment) { "staging" }

##### Settings #####
# the url of the website
set :url, stages.fetch("#{environment}").fetch("url")

# the deployment user-account on the server
set :user, stages.fetch("#{environment}").fetch("user")
set :password, stages.fetch("#{environment}").fetch("password")
set :port, stages.fetch("#{environment}").fetch("port") # the ssh port to connect to
# set(:password, Capistrano::CLI.password_prompt("SSH password: "))

# the name of the website - should also be the name of the directory
set :application, stages.fetch("#{environment}").fetch("application")

# the path to your new deployment directory on the server
# by default, the name of the application (e.g. "/var/www/sites/example.com")
set :deploy_to, stages.fetch("#{environment}").fetch("deploy_to")

# the git-clone url for your repository
# set :repository, "~/Documents/workspace-php/beteiligungskompass/.git"

# the branch you want to clone (default is master)
# set :branch, "master"

# scm credentials
#set :scm_username, ''
#set :scm_password, ''

##### You shouldn't need to edit below unless you're customizing #####

# Additional SCM settings
#set :scm, :git
#set :deploy_via, :checkout
set :ssh_options, { :forward_agent => true }

set :repository, "."
set :scm, :none
set :deploy_via, :copy
set :copy_exclude, [	".git", "**" "/" ".gitignore", ".git/*", "**/.svn",
						".svn/*", ".DS_Store", ".sass-cache",
						"./public/merged/*",
						"./application/data/files",
						"./public/files"
					#	"./application/config/base.config"
					#	"./application/messages/project.messages"
					]

#set :deploy_via, :rsync_with_remote_cache

set :keep_releases, 10
set :use_sudo, false
set :copy_compression, :bz2

# Roles
role :app, "#{application}"
role :web, "#{application}"
#role :db,	 "#{application}", :primary => true

# ================================================================================ #

# Deployment process
# before "deploy", "deploy:get_git_info"
after "deploy:update", "deploy:cleanup"
after "deploy",
"deploy:create_custom_symlinks",
"deploy:copy_environment_configuration",
"deploy:copy_environment_htaccess",
"deploy:merge_project_files",
"deploy:remove_dotunderscores",
"deploy:set_permissions",
"deploy:set_cache_permissions",
"deploy:set_custom_permissions",
"deploy:update_database_schema",
"deploy:regenerate_doctrine_proxies"

namespace :deploy do

	desc "This is here to override the original :restart task"
	task :restart, :roles => :app do
		# do nothing but override the default
	end

	task :finalize_update, :roles => :app do
		run "chmod -R g+w #{latest_release}" if fetch(:group_writable, true)
	end

	desc "Set the correct permissions for the config files and cache folder"
	task :set_permissions, :roles => :app do
		#run "chmod -R 777 #{current_release}/application/data/files/"
		run "sudo chmod -R 777 #{current_release}/public/files/"
		run "sudo chmod -R 777 #{current_release}/public/media/"
	end

	desc "Set the correct permissions for the cache folder"
	task :set_cache_permissions, :roles => :app do
		run "chmod -R 777 #{current_release}/application/cache/"
		run "chmod -R 777 #{current_release}/application/cache/smarty/cache"
		run "chmod -R 777 #{current_release}/application/cache/smarty/compile"
	end

	desc "Set the correct permissions for application files"
	task :set_custom_permissions, :roles => :app do

        # collect all files to chmod g+w
		filesToChmod = [
            "#{current_release}/application/config/base.config",
        ].concat(msgfiles.map { |msgfile|
            "#{current_release}/application/messages/#{msgfile}"
        })

        filesToChmod.each do |file|
			run "chmod g+w #{file}"
		end
	end

	desc "Create symlink to shared data such as config files and uploaded images"
	task :create_custom_symlinks, :roles => :app do
		# link data/files dir to shared folder
		run "ln -sf #{deploy_to}#{shared_dir}/files #{current_release}/application/data/files"
		run "ln -sf #{deploy_to}#{shared_dir}/files_public #{current_release}/public/files"
		run "ln -sf #{current_release}/public/js/libs/images #{current_release}/public/merged/images"
	end

	desc "Clear Smarty caches"
	task :clear_smarty_caches, :roles => :app do
		run "if [ -e #{current_release}/application/cache/smarty/cache ]; then rm -rf #{current_release}/application/cache/smarty/cache/*; fi"
		run "if [ -e #{current_release}/application/cache/smarty/compile ]; then rm -rf #{current_release}/application/cache/smarty/compile/*; fi"
	end

	desc "Copy environment configuration"
	task :copy_environment_configuration, :roles => :app do
        run "/bin/cp -f #{current_release}/config/envs/#{environment}/env.php #{current_release}/application/config/environment.php"
		run "/bin/cp -f #{current_release}/config/envs/#{environment}/doctrine.php #{current_release}/application/config/doctrine.php"
		run "/bin/cp -f #{current_release}/config/envs/#{environment}/solr.php #{current_release}/application/config/solr.php"
	end

	desc "Copy environment .htaccess file"
	task :copy_environment_htaccess, :roles => :app do
		run "if [ -e #{current_release}/config/envs/#{environment}/.htaccess ]; then /bin/cp -f #{current_release}/config/envs/#{environment}/.htaccess #{current_release}/public/.htaccess; fi";
	end

	desc "Update database schema"
	# WARNING: automatic database schema update should be used cautiously in production
	task :update_database_schema, :roles => :app do
		run "php #{current_release}/modules/doctrine2/doctrine.php orm:schema-tool:update --dump-sql"
		executeDatabaseUpdate = Capistrano::CLI.ui.ask("Database Schema Update durchführen (y[es]/n[o])?")
		if executeDatabaseUpdate === "y" || executeDatabaseUpdate === "yes"
			run "php #{current_release}/modules/doctrine2/doctrine.php orm:schema-tool:update --force"
		end
	end

	desc "Removes Mac OS X ._ (dot-underscore) files"
	task :remove_dotunderscores, :roles => :app do
		run "find #{current_release} -name '._*' -exec rm -v {} \\;"
	end

	desc "get git info"
	task :get_git_info, :roles => :app do
		shorthash = `git log --pretty=format:'%h' -n 1`
		commitinfo = `git log -1`
		#longhash = `git rev-list --max-count=1 HEAD`
		p commitinfo
		p shorthash
	end

	desc "Add new and remove unused message entries to/from several project specific files"
	task :merge_project_files, :roles => :app do

		now = Time.now.to_i
		lang = stages.fetch("#{environment}").fetch("lang");

        # collect all files that should be merged with the corresponding remote file
        # dir needs to be relative to /application/ folder
        filesToMerge = [
            Hash[
                "dir" => "config/",
                "name" => "base.config",
            ]
        ].concat(msgfiles.map { |msgfile|
            Hash[
                "dir" => "messages/",
                "name" => "#{msgfile}",
            ]
        })

        filesToMerge.each {
            |file|
            name = file.fetch("name")
            dir = file.fetch("dir")

            oldfile = "#{previous_release}/application/#{dir}#{name}"
            newfile = "#{current_release}/application/#{dir}#{name}"
            tempfile = "#{current_release}/application/#{dir}#{name}-temp"

            # 1: backup old file before merge
            run "if [ -f #{oldfile} ]; then cp #{oldfile} #{deploy_to}#{shared_dir}/#{now}-#{name}; fi"

            # 2: merge the old and new files into tempfile
            run "if [ -f #{oldfile} ]; then ruby #{current_release}/config/tools/merge-messages.rb #{oldfile} #{newfile} #{tempfile}; fi"

            # 3: and finally move the tempfile to the current release
            run "if [ -f #{oldfile} ]; then /bin/mv -f #{tempfile} #{newfile}; fi"
        }
	end

	desc "delete all doctrine proxies and regenerate them"
	task :regenerate_doctrine_proxies, :roles => :app do
		run "rm -rf #{current_release}/application/cache/doctrine/proxies/*.php"
		run "php #{current_release}/modules/doctrine2/doctrine.php orm:generate-proxies"
	end

end
