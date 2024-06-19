#!/bin/bash

# turning off events manager google maps integration
wp option update dbem_gmap_is_active 0 --autoload=no --allow-root
