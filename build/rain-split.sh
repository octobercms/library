#
# This will split up each Rain library to its own github repo
#

./git-subsplit.sh init git@github.com:octobercms/library.git
./git-subsplit.sh publish src/Translation:git@github.com:octoberrain/translation.git
./git-subsplit.sh publish src/Support:git@github.com:octoberrain/support.git
./git-subsplit.sh publish src/Router:git@github.com:octoberrain/router.git
./git-subsplit.sh publish src/Network:git@github.com:octoberrain/network.git
./git-subsplit.sh publish src/Mail:git@github.com:octoberrain/mail.git
./git-subsplit.sh publish src/Html:git@github.com:octoberrain/html.git
./git-subsplit.sh publish src/Filesystem:git@github.com:octoberrain/filesystem.git
./git-subsplit.sh publish src/Extension:git@github.com:octoberrain/extension.git
./git-subsplit.sh publish src/Database:git@github.com:octoberrain/database.git
./git-subsplit.sh publish src/Config:git@github.com:octoberrain/config.git
./git-subsplit.sh publish src/Auth:git@github.com:octoberrain/auth.git
./git-subsplit.sh publish src/Parse:git@github.com:octoberrain/parse.git
./git-subsplit.sh publish src/Halcyon:git@github.com:octoberrain/halcyon.git
rm -rf .subsplit/

#
# This will split up the Core modules to its own github repo
#

./git-subsplit.sh init git@github.com:octobercms/october.git
./git-subsplit.sh publish modules/backend:git@github.com:octoberrain/backend.git
./git-subsplit.sh publish modules/cms:git@github.com:octoberrain/cms.git
./git-subsplit.sh publish modules/system:git@github.com:octoberrain/system.git

#
# This will split up the Demonstration theme and plugin its own github repo
#
./git-subsplit.sh publish themes/demo:git@github.com:octoberrain/demo-theme.git
./git-subsplit.sh publish plugins/october/demo:git@github.com:octoberrain/demo-plugin.git

rm -rf .subsplit/
