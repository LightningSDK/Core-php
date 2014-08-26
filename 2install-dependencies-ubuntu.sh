#!/bin/bash

# add repo for nodejs
apt-get install python-software-properties
add-apt-repository -y ppa:chris-lea/node.js
apt-get update

# install ruby for compass and grunt
apt-get -y install ruby-full rubygems

# install sass/compass gems
gem install sass
gem install compass

# install grunt
apt-get install python-software-properties python g++ make nodejs
npm install -g bower grunt-cli
gem install foundation

DIR="$( dirname "${BASH_SOURCE[0]}" )"
cd $DIR/../Source/foundation
npm install -g bower grunt-cli
npm install
bower install --allow-root

# Install ckeditor config.
if [ ! -f $DIR/../Source/foundation/js/ckeditor_config.js ]; then
  echo "Copying Lightning CKEditor config to foundation"
  cp $DIR/install/ckeditor_config.js $DIR/../Source/foundation/js/
  echo "Copy CKEditor css to foundation"
  cp $DIR/Vendor/ckeditor/contents.css $DIR/../Source/foundation/scss/ckeditor_contents.scss
  echo "Copy lightning Gruntfile to foundation"
  cp $DIR/install/Gruntfile.js $DIR/../Source/foundation/
fi
