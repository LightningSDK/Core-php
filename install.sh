#!/bin/bash

DIR=`realpath -s "$(cd "$(dirname "$0")" && pwd)/../"`
if [[ -f /etc/redhat-release ]]
then
    PLATFORM="RedHat"
else
    PLATFORM=`uname`
fi

shouldInstall() {
    CONTINUE=0

    while [ $CONTINUE != 1 ]
    do
        read -p "$1 [Y/n] " response
        if [[ $response =~ ^([yY][eE][sS]|[yY])$ ]]
        then
            CONTINUE=1
            echo 1
        elif [[ $response =~ ^([nN][oO]|[nN])$ ]]
        then
            CONTINUE=1
            echo 0
        fi
    done
}

if [ `shouldInstall "Install PHP dependencies and set permissions?"` -eq 1 ]
then
    cd $DIR/Lightning
    git submodule update --init Vendor/BounceHandler
    git submodule update --init Vendor/htmlpurifier
    git submodule update --init Vendor/PHPMailer
    git submodule update --init Vendor/plancakeEmailParser
    chmod 777 $DIR/Vendor/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer
fi

if [ `shouldInstall "Install compass and foundation dependencies? This is needed for advanced scss includes."` -eq 1 ]
then
    cd $DIR/Lightning
    git submodule update --init Vendor/compass
    git submodule update --init Vendor/foundation
fi

if [ `shouldInstall "Install lightning source dependencies? This is needed if you intend to rebuild lightning files."` -eq 1 ]
then
    cd $DIR/Lightning
    git submodule update --init Vendor/compass
    git submodule update --init Vendor/foundation

    if [[ "$PLATFORM" == 'RedHat' ]]; then
        # todo: pick repo based on centos version in /etc/redhat-release
        # 5.x
        # wget http://dl.fedoraproject.org/pub/epel/5/x86_64/epel-release-5-4.noarch.rpm
        # sudo rpm -Uvh epel-release-5*.rpm
        # 6.x
        wget http://dl.fedoraproject.org/pub/epel/6/x86_64/epel-release-6-8.noarch.rpm
        sudo rpm -Uvh epel-release-6*.rpm
        # 7.x
        # wget http://dl.fedoraproject.org/pub/epel/7/x86_64/e/epel-release-7-1.noarch.rpm
        # sudo rpm -Uvh epel-release-7*.rpm
        # install for centos
        sudo yum -y update
        sudo yum -y install npm
        sudo yum -y install ruby
        gem install bundle
    elif [[ "$PLATFORM" == 'Linux' ]]; then
        # add repo for nodejs
        sudo apt-get -y install python-software-properties curl
        curl -sL https://deb.nodesource.com/setup | sudo bash -

        # install ruby for compass and grunt
        sudo apt-get -y install ruby-full python-software-properties python g++ make nodejs
        if [[ -f /etc/debian_version ]]; then
          sudo apt-get -y install bundler rubygems
        else
          sudo apt-get -y install ruby-bundler ruby
        fi
    elif [[ "$PLATFORM" == 'Darwin' ]]; then
        # install ruby for mac
        \curl -L https://get.rvm.io | bash -s stable
    fi

    # Install gems.
    sudo gem install sass
    sudo gem install compass
    sudo gem install foundation

    # Some git repos will rime out on git://
    git config --global url.https://.insteadOf git://

    # Install foundation dependencies with npm and bower.
    cd ${DIR}/Vendor/foundation
    sudo npm install -g bower grunt-cli

    # for centos, install with bundler
    if [[ -f /etc/redhat-release || -f /etc/debian_version ]]; then
      sudo bundle install
    fi

    # install other dependences
    npm install
    bower install --allow-root
    grunt build

    # Install jquery Validation
    cd ${DIR}/Vendor/jquery-validation
    sudo npm install
    grunt
fi

if [ `shouldInstall "Install social signin dependencies?"` -eq 1 ]
then
    cd $DIR/Lightning
    git submodule update --init Vendor/googleapiclient
    git submodule update --init Vendor/facebooksdk
    git submodule update --init Vendor/twitterapiclient
fi

if [ `shouldInstall "Install ckeditor?"` -eq 1 ]
then
    cd $DIR/js
    wget http://download.cksource.com/CKEditor/CKEditor/CKEditor%204.5.7/ckeditor_4.5.7_standard.zip
    unzip ckeditor_4.5.7_standard.zip
    if [[ ! -f "${DIR}/Source/Resources/sass/ckeditor_contents.scss" ]]; then
        cp $DIR/ckeditor/content.css $DIR/Source/Resources/sass/ckeditor_contents.scss
        cd $DIR/Source/Resources
        grunt build
    fi
fi

if [ `shouldInstall "Install ckfinder?"` -eq 1 ]
then
    # Install ckfinder
fi

if [ `shouldInstall "Install tinymce?"` -eq 1 ]
then
    cd $DIR/js
    wget http://download.ephox.com/tinymce/community/tinymce_4.3.4.zip
    unzip tinymce_4.3.4.zip
    mv tinymce tinymce-remove
    mv tinymce-remove/js/tinymce ./
    rm -rf tinymce-remove
fi

if [ `shouldInstall "Install elfinder?"` -eq 1 ]
then
    cd $DIR/js
    wget https://github.com/Studio-42/elFinder/archive/2.1.6.zip -O elfinder-2.1.6.zip
    unzip elfinder-2.1.6.zip
    mv elFinder-2.1.6 elfinder
    cp $DIR/Lightning/install/elfinder/elfinder.html $DIR/js/elfinder/elfinder.html
fi

if [ `shouldInstall "Install index file?"` -eq 1 ]
then
    cp $DIR/Lightning/install/index.php $DIR/index.php
fi

if [ `shouldInstall "Install index file for Apache web server?"` -eq 1 ]
then
    cp $DIR/install/.htaccess-router $DIR/.htaccess
fi

# Create the source paths
if [ ! -d $DIR/../Source ]; then
    echo "Making Source Directory"
    mkdir $DIR/../Source
    echo "Copying Source htaccess file"
    cp $DIR/Lightning/install/.htaccess-protected $DIR/Source/.htaccess
fi

# Install the cache directory.
if [ ! -d $DIR/../cache ]; then
    echo "Creating cache directory"
    cp -r $DIR/Lightning/install/cache $DIR/
fi

# Copy Source/Resources.
if [ ! -d $DIR/Source/Resources -o `shouldInstall "Reset the Source/Resources file?"` -eq 1 ]
then
    echo "Linking compass files"
    cp -r ${DIR}/Lightning/install/Resources ${DIR}/Source/

    if [[ "$PLATFORM" == 'Linux' ]]; then
        sudo apt-get -y nodejs build-essential
    fi

    # Install lightning dependencies with grunt
    cd $DIR/Source/Resources
    npm install
    grunt build
fi

# Install the sample config file as active.
# TODO: Add option to reset database config
if [ ! -f $DIR/Source/Config/config.inc.php ]; then
    if [ ! -d $DIR/Source/Config ]; then
        echo "Creating Source/Config directory"
        mkdir $DIR/Source/Config
    fi

    echo "Copying initial config files"
    cp -r $DIR/Lightning/install/Config/* $DIR/Source/Config/

    #Collect database information.
    echo -n "Database host: "; read DBHOST
    echo -n "Database name: "; read DBNAME
    echo -n "Database user: "; read USER
    echo -n "Database password: "; read -s PASS

    echo "Copying sample config file to Source/Config with DB configuration"
    # TODO: This needs to be escaped to prevent sed from using the original line.
    sed "s|'database.*,|'database' => 'mysql:user=${USER};password=${PASS};host=${DBHOST};dbname=${DBNAME}',|"\
        <$DIR/Lightning/install/Config/config.inc.php >$DIR/Source/Config/config.inc.php

    # Conform the databases
    echo "Conforming the database"
    $DIR/Lightning/lightning database conform-schema
fi

if [ `shouldInstall "Install/reset default templates and images?"` -eq 1 ]
then
    # Install main templates.
    echo "Copying default templates to source folder."
    cp -rf $DIR/install/Templates $DIR/../Source/

    echo "Copying default images to web root."
    cp -rf $DIR/install/images $DIR/../
elif [ `shouldInstall "Install missing default templates and images?"` -eq 1 ]
then
    # Install main templates.
    echo "Copying default templates to source folder."
    cp -r $DIR/install/Templates $DIR/../Source/

    echo "Copying default images to web root."
    cp -r $DIR/install/images $DIR/../
fi

echo "Lightning Installation Complete."
