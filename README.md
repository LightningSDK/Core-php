A lightning fast PHP framework.

INSTALLATION:
In your web folder, run the following:

# Create a git repo.
git init

# Add the Lightning repo
git submodule add git@github.com:macdabby/lightning.git Lightning

# Download the dependencies
cd Lightning

# Run the main install script.
./1install.sh

# Install dependencies for ubuntu including foundation dependencies in the correct directory.
./2install-dependencies-ubuntu.sh

# Install the base template package - This can be done to reset the site at any time.
./3install-templates.php

# Install default content.
./lightning database import-defaults

# Create an admin user.
# TODO: This current returns an error although it works.
./lightning user create-admin

# Build the CSS and JS files
cd ../Source/foundation
grunt
