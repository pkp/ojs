#!/bin/bash

#
# lib/pkp/tools/mergePullRequest.sh
#
# Copyright (c) 2013-2017 Simon Fraser University
# Copyright (c) 2010-2017 John Willinsky
# Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
#
# Script to perform a pull request merge correctly updating the library submodule hashes.
# 
# Usage:
# Make sure your local repository is the same of the pull request (ojs, omp or ocs).
# Also, there is no library only pull requests. All library changes must have a app
# submodule commit, so run this script always in app folder.
#
# Also make sure that your local branch is the one the pull request will be merged into, 
# both for application and library. Make sure that both are updated and clean from other 
# local commits or files. This script will not check this.
#
# After that, just run the command passing the correct parameters.
# 
# ./lib/pkp/mergePullRequest.sh github_user feature_branch application_only bug_id
#
# github_user: 
#   The user where the pull request comes from. This script assumes users will
#   for the official repositories so they will have forks on their own github account where the
#   changed code will be.
#
# feature_branch:
#   The branch inside the user forked repository where the pull request code is.
#
# application_only:
#   The pull request changes only application code? Or it does change pkp library code also?
#   Use 0 to false and 1 to true.  
#
# bug_id:
#   The bug entry id related to this pull request (optional).


# Asks for confirmation and return user input as true of false.
confirm () {
    # call with a prompt string or use a default
    read -r -p "${1:-Are you sure? [y/N]} " response
    case $response in
        [yY][eE][sS]|[yY]) 
            true 
            ;;
        *)
            false
            ;;
    esac
}

# Configurable constants.
PKP_LIB_REPOSITORY="pkp-lib.git"
PKP_LIB_PATH="lib/pkp"
GITHUB_URL="git://github.com"
OFFICIAL_REPO_NAME="official"

# Script parameters as constants.
GITHUB_USER=$1
FEATURE_BRANCH=$2
APPLICATION_ONLY=$3
BUG_ID=$4

# Get the current working repository. This will be considered the repository where we will fetch the pull request code.
userRepository=$(git remote show -n origin | grep "Fetch URL" | grep -o "\/.*git$" | cut -d "/" -f 2)
if [ -z "$userRepository" ]; then
	echo "Not found a local git repository. Process stopped!"
	exit 1
fi


# Define the pull request remote and branch to be used later.
PR_REMOTE="$GITHUB_URL/$GITHUB_USER/$userRepository $FEATURE_BRANCH"
PR_LIB_REMOTE="$GITHUB_URL/$GITHUB_USER/$PKP_LIB_REPOSITORY $FEATURE_BRANCH"

# Define the temporary branch that this script creates to work.
TEMP_BRANCH="$FEATURE_BRANCH-temp"

# Make sure the developer knows the repository they are working with.
echo -e "\n"
confirm "Merge code into the official $userRepository repository. That's correct? [y/N]?"
ret=$?
if [ "$ret" -eq 1 ]; then
	echo "Merging process stopped."
	exit 0
fi

# Try to fetch the user application repository.
git fetch $PR_REMOTE
fetchResult=$?
if [ "$fetchResult" -ne 0 ]; then
	echo "Not found the user repository and/or branch. Process stopped!"
	exit 1
fi

# Pull code from pull request.
if [ "$APPLICATION_ONLY" -eq 0 ]; then
	# There are library changes.
	cd $PKP_LIB_PATH 
	chDir=$?
	if [ "$chDir" -ne 0 ]; then
		echo "You don't have the library submodule folder. You need to initiate your submodules first before being able to merge this pull request. Process stopped!"
		exit 1
	fi

	# Get the current library branch. This will be considered the branch where the library pull request will be merged in.
	libBranchToMerge=$(git branch | grep "^\* " | cut -d " " -f 2)
	if [ -z "$libBranchToMerge"  ]; then
		echo "No local library branch found. Process stopped!"
		cd ../..
		exit 1
	fi

	# Create temporary working branch for library.
	git checkout -b $TEMP_BRANCH $libBranchToMerge
	checkoutResult=$?
	if [ "$checkoutResult" -ne 0 ]; then
		echo "Can't create a local working library branch named $TEMP_BRANCH based on $libBranchToMerge. Process stopped!"
		cd ../..
		exit 1
	fi	

	# Pull library changes.
	git pull $PR_LIB_REMOTE
	cd ../..
fi

# Get the current application branch. This will be considered the branch where the application pull request will be merged in.
appBranchToMerge=$(git branch | grep "^\* " | cut -d " " -f 2)
if [ -z "$appBranchToMerge"  ]; then
	echo "No local application branch found. Process stopped!"
	exit 1
fi

# Create temporary working branch for application.
git checkout -b $TEMP_BRANCH $appBranchToMerge
checkoutResult=$?
if [ "$checkoutResult" -ne 0 ]; then
	echo "Can't create a local working application branch named $TEMP_BRANCH based on $appBranchToMerge. Process stopped!"
	exit 1
fi

# Pull application changes.
git pull $PR_REMOTE

# Check if the HEAD is a submodule hash commit.
submoduleCommitHash=$(git show | grep "+Subproject commit" | cut -f3 -d" ")
if [ -n "$submoduleCommitHash" ]; then
	# Make sure we don't commit this hash.
	git reset --hard HEAD^
fi

# Add the library submodule hash commit.
git add $PKP_LIB_PATH
if [ -n "$BUG_ID" ]; then
	git commit -m "*$BUG_ID* Submodule changes track"
else
	git commit -m "Submodule changes track"
fi

# Present commits that will be sent to official repositories.
echo -e "\n"
echo -e "\n"
echo "*****************************************************************************"
echo "Presenting the merged pull request commits that will be pushed to official..."
echo "*****************************************************************************"
echo "Commits in application, to be pushed to branch $appBranchToMerge:"
git log $OFFICIAL_REPO_NAME/$appBranchToMerge..$TEMP_BRANCH
echo "____________________________________________________"
if [ "$APPLICATION_ONLY" -eq 0 ]; then
	echo -e "\n"
	cd $PKP_LIB_PATH
	echo "Commits in library, to be pushed to branch $libBranchToMerge:"
	git log $OFFICIAL_REPO_NAME/$libBranchToMerge..$TEMP_BRANCH
	cd ../..
	echo "____________________________________________________"
fi
echo -e "\n"
echo -e "\n"
# Ask for user confirmation before pushing to official
confirm "Confirm merging into $OFFICIAL_REPO_NAME the commits presented above? [y/N]?"
ret=$?
if [ "$ret" -eq 1 ]; then
	echo -e "\n"
        echo "Merging process stopped."
	echo "The merge is done locally, but IT'S NOT pushed to official. You are now in $TEMP_BRANCH, for both application and library. This is just a temporary branch created by the script. You should delete it if you want to run the script again for the same pull request."
        exit 0
fi

# User said YES, let's push.
# First library commits, if any.
if [ "$APPLICATION_ONLY" -eq 0 ]; then
	cd $PKP_LIB_PATH  
	git push $OFFICIAL_REPO_NAME $TEMP_BRANCH:$libBranchToMerge
	libPushResult=$?
	cd ../..
	if [ "$libPushResult" -eq 1 ]; then
		echo -e "\n"
		echo "Could not push the library commits. Try to update your local repositories and push again manually from this $TEMP_BRANCH branch."
		exit 1
	fi
fi
# Now application.
git push $OFFICIAL_REPO_NAME $TEMP_BRANCH:$appBranchToMerge
pushResult=$?
if [ "$pushResult" -eq 1 ]; then
	echo -e "\n"
	echo "Could not push the application commits. Try to update your local repositories and push again manually from this $TEMP_BRANCH branch."
	if [ "$libPushResult" -eq 0 ]; then
		echo "The library commits were successfully pushed to official though."
	fi
	exit 1
fi

# Return to the original branches and clear temporary ones.
git checkout $appBranchToMerge
git branch -D $TEMP_BRANCH

if [ "$APPLICATION_ONLY" -eq 0 ]; then
	cd $PKP_LIB_PATH
	git checkout $libBranchToMerge
	git branch -D $TEMP_BRANCH
	cd ../..
fi

# Update.
git pull $OFFICIAL_REPO_NAME $appBranchToMerge
if [ "$APPLICATION_ONLY" -eq 0 ]; then
	cd $PKP_LIB_PATH
	git pull $OFFICIAL_REPO_NAME $libBranchToMerge
	cd ../..
fi

# End script.
echo "Merge process completed."
echo 0
