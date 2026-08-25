#!/bin/bash

# fixing recurring event urls
wp proud fix-em-slugs --yes

# flushing cache with new URLs
wp cache flush
