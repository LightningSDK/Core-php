#!/bin/bash

cd "$( dirname "${BASH_SOURCE[0]}" )"
DIR=${PWD}
PLATFORM=`uname`

if [[ "$PLATFORM" == 'Linux' ]]; then
  # add repo for nodejs
  apt-get install python-software-properties
  add-apt-repository -y ppa:chris-lea/node.js
  apt-get update

  # install ruby for compass and grunt
  apt-get -y install ruby-full rubygems python-software-properties python g++ make nodejs ruby-bundler
elif [[ "$PLATFORM" == 'Darwin' ]]; then
  # install ruby for mac
  \curl -L https://get.rvm.io | bash -s stable
fi

# Install gems.
sudo gem install sass
sudo gem install compass
sudo gem install foundation

# Install foundation dependencies with npm and bower.
cd ${DIR}/Vendor/foundation
sudo npm install -g bower grunt-cli
# is this one needed?
sudo npm install
bower install --allow-root
grunt

cd ${DIR}/Vendor/jquery-validation
sudo npm install
bower install --allow-root
grunt build:assets

# Install lightning dependencies with grunt
cd ${DIR}/../Source/Resources
sudo npm install
grunt build

# Install ckeditor config.
if [[ ! -f "${DIR}/../Source/foundation/js/ckeditor_config.js" ]]; then
  echo "Copying Lightning CKEditor config to foundation"
  cp ${DIR}/install/ckeditor_config.js ${DIR}/../Source/Resources/js/
#  echo "Copy CKEditor css to foundation"
  cp ${DIR}/Vendor/ckeditor/contents.css ${DIR}/../Source/foundation/scss/ckeditor_contents.scss
  echo "Copy lightning Gruntfile to foundation"
  cp ${DIR}/install/Gruntfile.js ${DIR}/../Source/Resources/
fi
