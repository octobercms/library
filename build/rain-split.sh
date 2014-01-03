#
# This script will split up each Rain library to its own github repo
#

./git-subsplit.sh init git@github.com:octobercms/library.git
./git-subsplit.sh publish --no-tags src/October/Rain/Translation:git@github.com:octoberrain/translation.git
./git-subsplit.sh publish --no-tags src/October/Rain/Support:git@github.com:octoberrain/support.git
./git-subsplit.sh publish --no-tags src/October/Rain/Router:git@github.com:octoberrain/router.git
./git-subsplit.sh publish --no-tags src/October/Rain/Network:git@github.com:octoberrain/network.git
./git-subsplit.sh publish --no-tags src/October/Rain/Html:git@github.com:octoberrain/html.git
./git-subsplit.sh publish --no-tags src/October/Rain/Filesystem:git@github.com:octoberrain/filesystem.git
./git-subsplit.sh publish --no-tags src/October/Rain/Extension:git@github.com:octoberrain/extension.git
./git-subsplit.sh publish --no-tags src/October/Rain/Database:git@github.com:octoberrain/database.git
./git-subsplit.sh publish --no-tags src/October/Rain/Config:git@github.com:octoberrain/config.git
./git-subsplit.sh publish --no-tags src/October/Rain/Auth:git@github.com:octoberrain/auth.git
rm -rf .subsplit/