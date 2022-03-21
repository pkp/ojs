#!/bin/bash

parseRepositoryName () {
    local url=$1
    if [ -z "$url" ]; then
        echo "You should pass a URL" >&2
        exit 2;
    fi;

    echo $(basename "${url}" .git)
}

getSubmodulesList () {
    local option=$1
    if [ -z "$option" ]; then
        echo "You should select an option" >&2
        exit 2;
    fi;

    echo $(git config --file .gitmodules --get-regexp ${option} | awk '{ print $2 }');
}

existsFork () {
    local username=$1
    local repository=$2
    local branch=$3
    if [ -z "$username" ]
    then
        echo "Username should be pass as first parameter." >&2
        exit 2;
    fi;

    if [ -z "$repository" ]
    then
        echo "Repository should be pass as second parameter." >&2
        exit 2;
    fi;

    if [ -z "$branch" ]
    then
        echo "Branch should be pass as third parameter." >&2
        exit 2;
    fi;

    result=$(git ls-remote --quiet --exit-code -h https://github.com/${username}/${repository} ${branch});

    if [ ! -z "$result" ]
     then
        echo 1;
     else
        echo 0;
    fi;
}

git config --global user.email "pkp@mailinator.com"
git config --global user.name "PKP"
echo "1 - Retrieving git user:"
gitUser=$(cat .git/config | grep -A2 "remote \"origin\"" | grep "url" | cut -f2 -d":" | cut -f4 -d"/")
echo "    Gituser: $gitUser"
echo "2 - Verifying if we have a merge commit in HEAD:"
isMergeCommit=$(git log -m --pretty=oneline -n1 | grep -P "Merge [0-9a-z]{40} into [0-9a-z]{40}")
echo "    Merge commit message: $isMergeCommit"
if [ \( -n "$isMergeCommit" \) -a \( "$gitUser" = "pkp" \) ]; then
	commitHash=$(git log -m --pretty=oneline -n2 | sed -n 2p | grep -Po "^[0-9a-z]{40}")
	echo "    HEAD points to a merge commit."
	echo "    HEAD^ points to a common commit with hash $commitHash"
else
	commitHash=$(git log -m --pretty=oneline -n1 | grep -Po "^[0-9a-z]{40}")
	echo "    HEAD points to a common commit with hash $commitHash"
fi
echo "3 - Verifying if last non merge commit is a subproject commit:"
libModuleHashes=$(git show "$commitHash" | grep "+Subproject commit" | cut -f3 -d" ")
strLength=${#libModuleHashes}
echo "    Subproject commit hash: $libModuleHashes"
if [ \( -n "$libModuleHashes" \) -a \( "$strLength" -ge 40 \) ]; then
	echo "    Last non merge commit is subproject commit."
	echo "4 - Trying to get user and branch from commit message:"
	userAndBranch=$(git log --pretty=oneline -1 "$commitHash" | grep -o "##.*##" | sed "s:^##\(.*\)##$:\1:")
	gitUser=$(echo "$userAndBranch" | cut -f1 -d"/")
	branch=$(echo "$userAndBranch" | cut -f2- -d"/")
	echo "    User and branch: $userAndBranch - User: $gitUser - Branch: $branch"
	if [ \( -n "$gitUser" \) -a \( -n "$branch" \) ]; then
		echo "    Found user and branch in commit message."
		echo "5 - Reseting the subproject commit in application."
		git reset --hard "$commitHash"^
		echo "6 - Initializing and updating submodule from official."
		git submodule update --init --recursive
        submoduleList=$(getSubmodulesList path)

        declare -A submodulesArr
        for submodule in $submoduleList
        do
            rawUrl=$(getSubmodulesList "submodule.${submodule}.url")
            submodulesArr[$submodule]=$(parseRepositoryName $rawUrl)
        done;

        rootDir=$(pwd)
        currentSubmoduleIteration=1

        for submoduleName in ${!submodulesArr[@]};
        do
            submoduleUrlBasename=${submodulesArr[${submoduleName}]};
            submoduleLocalPath=$submoduleName
            printf "\nThis is the submodule: ${submoduleName}\n"
            printf "\tLocal path for repository should be %s\n" "${submoduleLocalPath}"
            printf "\tURL path for repository should be %s\n" "${submoduleUrlBasename}"
            printf "\tExists fork for it?\n"
            existsForkForSubmodule=$(existsFork ${gitUser} ${submoduleUrlBasename} ${branch})
            if [[ $existsForkForSubmodule == 1 ]]
                then
                    echo "7 - Updating $submoduleLocalPath with code from $gitUser repository, $branch branch."
                    cd ${submoduleLocalPath}
                    git remote add "$gitUser" https://github.com/"$gitUser"/"$submoduleUrlBasename"
                    git reset --hard HEAD
                    git pull --rebase "$gitUser" "$branch"
                    cd ${rootDir}
                    ((currentSubmoduleIteration=currentSubmoduleIteration+1))
            fi;
        done
		exit 0
	fi
fi
git submodule update --init --recursive
