#wp option update proud_stage test 
#wp user delete demo@proudcity.com

# Update url (this should have be caught elsewhere first)
#update wp_options set option_value="https://corvallis-or.proudcity.com" where option_name="siteurl" or option_name="home";

# Temporarily set blog title
update wp_options set option_value="corvallis_or" where option_name="blogname";

# Other cleanup
update wp_options set option_value="test" where option_name="proud_stage";
delete from wp_options where option_name="google_analytics_key";