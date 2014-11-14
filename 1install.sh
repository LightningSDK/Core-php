#!/bin/bash

cd "$( dirname "${BASH_SOURCE[0]}" )"
DIR=$PWD

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
if [ ! -d $DIR/../Source/Resources ]; then
  echo "Linking compass files"
  cp -r ${DIR}/install/Resources ${DIR}/../Source/
  ln -s ${DIR}/Vendor/foundation/scss/foundation.scss ${DIR}/../Source/Resources/sass/
  ln -s ${DIR}/Vendor/foundation/scss/normalize.scss ${DIR}/../Source/Resources/sass/
fi

# Install the sample config file as active.
if [ ! -f $DIR/../Source/Config/config.inc.php ]; then
  if [ ! -d $DIR/../Source/Config ]; then
    echo "Creating Source/Config directory"
    mkdir $DIR/../Source/Config
  fi

  echo "Copying initial config files"
  cp -r $DIR/install/Config/* $DIR/../Source/Config/

  #Collect database information.
  echo -n "Database host: "; read DBHOST
  echo -n "Database name: "; read DBNAME
  echo -n "Database user: "; read USER
  echo -n "Database password: "; read -s PASS

  echo "Copying sample config file to Source/Config with DB configuration"
  # TODO: This needs to be escaped to prevent sed from using the original line.
  sed "s|'database.*,|'database' => 'mysql:user=${USER};password=${PASS};host=${DBHOST};dbname=${DBNAME}',|"\
    <$DIR/install/Config/config.inc.php >$DIR/../Source/Config/config.inc.php

  # Conform the databases
  echo "Conforming the database"
  $DIR/lightning database conform-schema
fi

# Install CKEditor
if [ ! -d $DIR/../js/ckeditor ]; then
  if [ ! -d $DIR/../js ]; then
    echo "Making /js directory"
    mkdir $DIR/../js
  fi
  echo "Copying ckeditor files to web directory"
  mkdir $DIR/../js/ckeditor
  cp -r Vendor/ckeditor/ckeditor.js ../js/ckeditor/
  cp -r Vendor/ckeditor/skins ../js/ckeditor/
  cp -r Vendor/ckeditor/plugins ../js/ckeditor/
  cp -r Vendor/ckeditor/lang ../js/ckeditor/
  cp -r Vendor/ckeditor/contents.css ../js/ckeditor/
fi

# Install CKFinder
if [ ! -d $DIR/../js/ckfinder ]; then
  echo "Copying ckfinder files to web directory"
  mkdir $DIR/../js/ckfinder
  cp -r $DIR/Vendor/ckfinder/core ../js/ckfinder/
  cp -r $DIR/Vendor/ckfinder/help ../js/ckfinder/
  cp -r $DIR/Vendor/ckfinder/lang ../js/ckfinder/
  cp -r $DIR/Vendor/ckfinder/plugins ../js/ckfinder/
  cp -r $DIR/Vendor/ckfinder/skins ../js/ckfinder/
  cp $DIR/Vendor/ckfinder/ckfinder.js ../js/ckfinder/
  cp $DIR/Vendor/ckfinder/ckfinder.php ../js/ckfinder/
  cp $DIR/Vendor/ckfinder/ckfinder.html ../js/ckfinder/

  # Config files.
  cp $DIR/Vendor/ckfinder/config.js ../Source/Resources/js/ckfinder_config.js
  cp $DIR/install/ckfinder_config_ref.php ../js/ckfinder/config.php
fi

# Setup CKFinder content directory.
if [ ! -d $DIR/../content ]; then
  mkdir $DIR/../content
  OWNER=`stat -c '%U' $DIR`
  GROUP=`stat -c '%G' $DIR`
  # This might not work if web runs as nobody.
  chown $OWNER:$GROUP $DIR/../content
  chmod 775 $DIR/../content
fi
