#
# This will split up each Rain library to its own github repo
#

./git-subsplit.sh init git@github.com:octobercms/library.git
./git-subsplit.sh publish --no-tags src/Translation:git@github.com:octoberrain/translation.git
./git-subsplit.sh publish --no-tags src/Support:git@github.com:octoberrain/support.git
./git-subsplit.sh publish --no-tags src/Router:git@github.com:octoberrain/router.git
./git-subsplit.sh publish --no-tags src/Network:git@github.com:octoberrain/network.git
./git-subsplit.sh publish --no-tags src/Mail:git@github.com:octoberrain/mail.git
./git-subsplit.sh publish --no-tags src/Html:git@github.com:octoberrain/html.git
./git-subsplit.sh publish --no-tags src/Filesystem:git@github.com:octoberrain/filesystem.git
./git-subsplit.sh publish --no-tags src/Extension:git@github.com:octoberrain/extension.git
./git-subsplit.sh publish --no-tags src/Database:git@github.com:octoberrain/database.git
./git-subsplit.sh publish --no-tags src/Config:git@github.com:octoberrain/config.git
./git-subsplit.sh publish --no-tags src/Auth:git@github.com:octoberrain/auth.git
rm -rf .subsplit/

#
# This will split up the Core modules to its own github repo
#

./git-subsplit.sh init git@github.com:octobercms/october.git
./git-subsplit.sh publish --no-tags modules/backend:git@github.com:octoberrain/backend.git
./git-subsplit.sh publish --no-tags modules/cms:git@github.com:octoberrain/cms.git
./git-subsplit.sh publish --no-tags modules/system:git@github.com:octoberrain/system.git

#
# This will split up the Demonstration theme and plugin its own github repo
#
./git-subsplit.sh publish --no-tags themes/demo:git@github.com:octoberrain/demo-theme.git
./git-subsplit.sh publish --no-tags plugins/october/demo:git@github.com:octoberrain/demo-plugin.git

rm -rf .subsplit/