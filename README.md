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
install.sh

# Install the base template package - This can be done to reset the site at any time.
php -f install-templates.php
