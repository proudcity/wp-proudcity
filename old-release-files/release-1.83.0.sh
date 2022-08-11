# Updates for the 1.83.0 release
# (no manual updates)

PHP=${1}
KEY=$(wp option get google_api_key --allow-root)
if [ "$KEY" = "AIzaSyCwfrA-_2rE-IiNf1z74xe1YeLolSeapnU" ]; then
    echo "Updating google maps api key"
    wp option update google_api_key AIzaSyASOGcX0_VY7W-Jucv01IdPOM-4Qml7ckA --allow-root
else
    echo "Strings are equal"
fi


