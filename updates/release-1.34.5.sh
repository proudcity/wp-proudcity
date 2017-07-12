# Updates for the 1.34.5 Hotfix release

PHP=${1}

curl http://workhorse.albatrossdigital.com/labspace/a0.json | wp option set wp_auth0_settings --format=json --allow-root

wp option get wp_auth0_settings --format=json --allow-root | php -r "
\$var = json_decode( fgets(STDIN) );
\$var->default_login_redirection = '`wp option get siteurl --allow-root`';
print json_encode(\$var);
" | wp option set wp_auth0_settings --format=json --allow-root
