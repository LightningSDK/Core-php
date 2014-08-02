#!/bin/bash
cd "$( dirname "${BASH_SOURCE[0]}" )"
cp install/index.php ../
cp install/.htaccess-router ../.htaccess
chmod 777 Vendor/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer

# Create the source paths
mkdir ../Source
cp install/.htaccess-protected ../Source/.htaccess
