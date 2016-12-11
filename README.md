A lightning fast PHP framework.

INSTALLATION:
In your web folder, run the following:

# Create a git repo.
git init

# Add the Lightning repo
git submodule add git@github.com:macdabby/Lightning.git

# Run the main install script.
This will walk you through all the installation options, including connecting to the database and creating an admin user. You should already have a MySQL database set up with a username and password before starting this script.
Lightning/install.sh

# Install default content.
This is part of the installation process, but at any time you can import default data by running this script.
Lightning/lightning database import-defaults

# Create an admin user.
This is part of the installation process, but you can always add a new admin user by running this script.
Lightning/lightning user create-admin

# Build the CSS and JS files
cd Source/Resources
gulp
