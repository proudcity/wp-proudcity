#!/bin/bash

# running WP Stateless migration
yes | wp stateless migrate 20240423174109 --progress=1 --allow-root
