#!/bin/bash

cd "$( dirname "${BASH_SOURCE[0]}" )"
DIR=${PWD}
PLATFORM=`uname`

if [[ "$PLATFORM" == 'Linux' ]]; then
  # add repo for nodejs
  sudo apt-get install python-software-properties
  sudo add-apt-repository -y ppa:chris-lea/node.js
  sudo apt-get update

  # install ruby for compass and grunt
  sudo apt-get -y install ruby-full rubygems python-software-properties python g++ make nodejs ruby-bundler
elif [[ -f /etc/redhat-release ]]; then
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

  # for centos, install with bundler
  if [[ -f /etc/redhat-release ]]; then
    sudo bundle install
  fi

  # install other dependences
  npm install
  bower install --allow-root
  # If github times out, try: git config --global url."https://".insteadOf git://
  grunt build

# Install jquery Validation
cd ${DIR}/Vendor/jquery-validation
sudo npm install
bower install --allow-root
grunt

# Install ckeditor config.
if [[ ! -f "${DIR}/../Source/foundation/js/ckeditor_config.js" ]]; then
  echo "Copying Lightning CKEditor config to foundation"
  cp ${DIR}/install/ckeditor_config.js ${DIR}/../Source/Resources/js/
  echo "Copy CKEditor css to foundation"
  cp ${DIR}/Vendor/ckeditor/contents.css ${DIR}/../Source/foundation/scss/ckeditor_contents.scss
  echo "Copy lightning Gruntfile to foundation"
  cp ${DIR}/install/Gruntfile.js ${DIR}/../Source/Resources/
fi

# Install lightning dependencies with grunt
cd ${DIR}/../Source/Resources
npm install
grunt build
