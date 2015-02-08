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

# Install dependencies for building your project css and js files.
./2install-project-dependencies.sh

# Install dependencies for building lightning files.
# This is not required if you do not intend to tweak or contribute.
./3install-dependencies-ubuntu.sh

# Install the base template package - This can be done to reset the site at any time.
./4install-templates.sh

# Install default content.
./lightning database import-defaults

# Create an admin user.
./lightning user create-admin

# Build the CSS and JS files
cd ../Source/Resources
grunt
