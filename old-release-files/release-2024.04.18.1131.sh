#!/bin/bash

# running the migration for wp stateless
yes | wp stateless migrate 20240219175240 --allow-root
