#!/bin/bash

# Remove stale wpses_options row that ships from the site template.
# Holds a now-burned AWS SES access key + secret. See pc-dev-issues #274 and #273.
if wp option get wpses_options --allow-root > /dev/null 2>&1; then
  wp option delete wpses_options --allow-root
  echo "Deleted wpses_options"
else
  echo "wpses_options not present, skipping"
fi
