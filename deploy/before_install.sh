#!/bin/bash

# get the current working dir
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# fetch the script files
aws s3api get-object --bucket aelr-code-deploy --key journal/before_install.sh "$DIR"/before.sh
aws s3api get-object --bucket aelr-code-deploy --key journal/after_install.sh "$DIR"/after_install.sh
aws s3api get-object --bucket aelr-code-deploy --key journal/application_start.sh "$DIR"/application_start.sh
aws s3api get-object --bucket aelr-code-deploy --key journal/validate_service.sh "$DIR"/validate_service.sh

# make all scripts executable
chmod +x "$DIR"/before.sh "$DIR"/after_install.sh "$DIR"/application_start.sh "$DIR"/validate_service.sh

# run the before
"$DIR"/before.sh
