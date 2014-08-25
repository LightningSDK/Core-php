#!/bin/bash

DIR="$( dirname "${BASH_SOURCE[0]}" )"
cd $DIR

# Updating git submodules
echo "Updating git submodules"
git submodule update --init --recursive

if [ ! -f $DIR/../index.php ]; then
  echo "Copying index.php to webroot"
  cp $DIR/install/index.php $DIR/../index.php
fi

if [ ! -f $DIR/../.htaccess ]; then
  echo "Copying main router to webroot"
  cp $DIR/install/.htaccess-router $DIR/../.htaccess
fi

echo "Making HTMLPurifier/DefinitionCache/Serializer Writable"
chmod 777 $DIR/Vendor/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer

# Create the source paths
if [ ! -d $DIR/../Source ]; then
  echo "Making Source Directory"
  mkdir $DIR/../Source
  echo "Copying Source htaccess file"
  cp install/.htaccess-protected ../Source/.htaccess
fi

# Copy the core foundation files.
if [ ! -d $DIR/../Source/foundation ]; then
  echo "Copying foundation files"
  cp -r $DIR/Vendor/foundation $DIR/../Source/
  echo "Copying lightning default foundation configs"
  cp -R $DIR/install/foundation/* $DIR/../Source/foundation/
fi

# Install the sample config file as active.
if [ ! -f $DIR/../Source/Config/config.inc.php ]; then
  if [ ! -d $DIR/../Source/Config ]; then
    echo "Creating Source/Config directory"
    mkdir $DIR/../Source/Config
  fi
  echo -n "Database host: "; read DBHOST
  echo -n "Database name: "; read DBNAME
  echo -n "Username: "; read USER
  echo -n "Password: "; read -s PASS

  echo "Copying sample config file to Source/Config with DB configuration"
  sed "s|'database.*,|'database' => 'mysql:user=${USER};password=${PASS};host=${DBHOST};dbanme=${DBNAME}'|"\
    <$DIR/install/config.inc.php >$DIR/../Source/Config/config.inc.php

  # Conform the databases
  echo "Conforming the database"
  $DIR/lightning conform-database
fi

# Install CKEditor
if [ ! -d $DIR/../js/ckeditor ]; then
  if [ ! -d $DIR/../js ]; then
    "Making /js directory"
    mkdir $DIR/../js
  fi
  echo "Copying ckeditor files to web directory"
  mkdir $DIR/../js/ckeditor
  cp -r Vendor/ckeditor/ckeditor.js ../js/ckeditor/
  cp -r Vendor/ckeditor/skins ../js/ckeditor/
  cp -r Vendor/ckeditor/plugins ../js/ckeditor/
  cp -r Vendor/ckeditor/lang ../js/ckeditor/


  # Install ckeditor config.
  if [ ! -f $DIR/../Source/foundation/js/ckeditor_config.js ]; then
    echo "Copying Lightning CKEditor config to foundation"
    cp $DIR/install/ckeditor_config.js $DIR/../Source/foundation/js/
  fi
  if [ ! -f $DIR/../Source/foundation/scss/ckeditor_contents.scss ]; then
    echo "Copy CKEditor css to foundation"
    cp $DIR/Vendor/ckeditor/contents.css $DIR/../Source/foundation/scss/ckeditor_contents.scss
  fi
fi
