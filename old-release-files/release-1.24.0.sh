# Updates for the 1.24.0 release
# (no manual updates)

PHP=${1}

# Change Agency to Department #793
echo "INSERT INTO wp_say_what_strings (orig_string, domain, replacement_string, context) VALUES ('Agency contact info','wp-agency','Department contact info',''),('Agency hours','wp-agency','Department hours',''),('Agency menu','wp-agency','Department menu',''),('Agency social media','wp-agency','Department social media',''),('View Agency','wp-agency','View Department',''),('Search agency','wp-agency','Search departments','');" | wp db cli --allow-root