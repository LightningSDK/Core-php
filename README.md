A lightning fast PHP framework.

INSTALLATION:
In your web folder, run the following:

# Create a git repo.
git init

# Add the Lightning repo
git submodule add git@github.com:macdabby/lightning.git Lightning

# Download the dependencies
cd Lightning
git submodule update --init

# Run the main install script.
php -f install.php

# Install the base template package - This can be done to reset the site at any time.
php -f install-templates.php
