#!/bin/bash
cd "$( dirname "${BASH_SOURCE[0]}" )"

# Install main templates.
cp -r Templates ../Source/

# Install ckeditor config.
cp install/ckeditor_config.js ../Source/foundation/js/
cp Vendor/ckeditor/contents.css ../Source/foundation/scss/ckeditor_contents.scss
