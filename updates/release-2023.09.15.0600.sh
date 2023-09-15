#!/bin/bash

# deactivating Admin Menu Editor
wp plugin deactivate admin-menu-editor --allow-root

# deleting huge option for Admin Menu Editor
wp option delete ws_menu_editor --allow-root

# activating new menu plugin
wp plugin activate wp-proud-admin-menu --allow-root
