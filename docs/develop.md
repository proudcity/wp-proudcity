# Developing

## The pc-cli

We have developed a cli to help set up your development enviornment and interact with the ProudCity Platform.

### Installing

Create an alias in your terminal profile. For example, on a Mac, assuming I cloned the `wp-proudcity` repo
into `~/Workspace/proudcity/wp-proudcity`:
```
echo "alias pc=~/Workspace/proudcity/wp-proudcity/bin/pc" >> ~/.bash_profile && source ~/.bash_profile
```

### Using

Simply type `pc` and you will get a list of available commands. For example:

```
$ pc
Available commands:
  pc dev <action>                Start/stop Docker. Connect to Docker.             
  pc watch <handle|all>          Grunt watch. Handles: proudcity-dashboard, wp-proud-theme, wp-proud-admin
  pc publish <handle> [push]     Grunt publish. When push is `true`, publish to firebase-hosting

$ pc dev
Available commands:
  pc dev start    Start Docker                                                          
  pc dev stop     Stop Docker                                                           
  pc dev status   Get list of running Docker containers                                 
  pc dev logs     View the last 1000 logs from the Docker container                     
  pc dev ssh      SSH into the Docker container                                         
  pc dev wp       Run wp-cli command                                                    
  pc dev db       Connect to the MySQL database                                         
  pc dev install  Runs composer install                                                 
  pc dev update   Runs composer update                                                  
  pc dev docs     Open the ProudCity Developer Docs in your browser                     
  pc dev repo [plugin] Open the ProudCity Github repo in your browser. Optionally accepts a plugin name to view.
```

---

## For advanced users

### Composer

#### Building

```
composer install
```
Wordpress will be in the `./wordpress` dir.

You can automate the management of custom plugins and themes using the bash scripts in ./scripts:
```
cd ./wp-proudcity
bash scripts/cmd.sh "git status"
```


#### Installing
```
wp db drop
wp db create
wp core install \
  --url=wordpress.local \
  --title="Proud" \
  --admin_user=admin \
  --admin_password=demo \
  --admin_email="jeff@getproudcity.com"
wp plugin activate --all
```

Or from an existing db:
```
wp db drop
wp db create
wp db import dump.sql
wp option update siteurl "http://wordpress.local"
wp option update home "http://wordpress.local"
```


