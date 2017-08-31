#
# This will split up each Rain library to its own github repo
#

mkdir -p library
pushd library
./../git-subsplit.sh init git@github.com:octobercms/library.git
./../git-subsplit.sh update
./../git-subsplit.sh publish --heads="master develop" src/Translation:git@github.com:octoberrain/translation.git
./../git-subsplit.sh publish --heads="master develop" src/Support:git@github.com:octoberrain/support.git
./../git-subsplit.sh publish --heads="master develop" src/Router:git@github.com:octoberrain/router.git
./../git-subsplit.sh publish --heads="master develop" src/Network:git@github.com:octoberrain/network.git
./../git-subsplit.sh publish --heads="master develop" src/Mail:git@github.com:octoberrain/mail.git
./../git-subsplit.sh publish --heads="master develop" src/Html:git@github.com:octoberrain/html.git
./../git-subsplit.sh publish --heads="master develop" src/Filesystem:git@github.com:octoberrain/filesystem.git
./../git-subsplit.sh publish --heads="master develop" src/Extension:git@github.com:octoberrain/extension.git
./../git-subsplit.sh publish --heads="master develop" src/Database:git@github.com:octoberrain/database.git
./../git-subsplit.sh publish --heads="master develop" src/Config:git@github.com:octoberrain/config.git
./../git-subsplit.sh publish --heads="master develop" src/Auth:git@github.com:octoberrain/auth.git
./../git-subsplit.sh publish --heads="master develop" src/Parse:git@github.com:octoberrain/parse.git
./../git-subsplit.sh publish --heads="master develop" src/Halcyon:git@github.com:octoberrain/halcyon.git
popd


#
# This will split up the Core modules to its own github repo
#

mkdir -p october
pushd october
./../git-subsplit.sh init git@github.com:octobercms/october.git
./../git-subsplit.sh update
./../git-subsplit.sh publish --heads="master develop" modules/backend:git@github.com:octoberrain/backend.git
./../git-subsplit.sh publish --heads="master develop" modules/cms:git@github.com:octoberrain/cms.git
./../git-subsplit.sh publish --heads="master develop" modules/system:git@github.com:octoberrain/system.git
./../git-subsplit.sh publish --heads="master develop" themes/demo:git@github.com:octoberrain/demo-theme.git
./../git-subsplit.sh publish --heads="master develop" plugins/october/demo:git@github.com:octoberrain/demo-plugin.git
popd
