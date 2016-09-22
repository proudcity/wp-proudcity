# using
#  bash pull_all.sh

cmd=${1:-"git pull"}
dir=`pwd`
echo $cmd

cd $dir;git pull
for d in $dir/src/wp-content/plugins/* ; do (if [ -d $d/.git ]; then echo $d; cd $d; $cmd; echo;echo "--";echo; fi;); done
for d in $dir/src/wp-content/themes/* ; do (if [ -d $d/.git ]; then echo $d; cd $d; $cmd; echo;echo "--";echo; fi;); done