#!/bin/bash

# run DB upgrade routines
echo "release core db update"
wp core update-db --allow-root
