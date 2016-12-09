# bash kube-cmd-all.sh $NAMESPACE  wp "option get blogname"


dir=`pwd`
namespace=$1
cmd=$2
set -- $(kubectl --namespace=$namespace get services --output=jsonpath={.items..metadata.name})
for w; do 
  if [[ $w != "kube"* ]]
  then
    echo  "RUNNING COMMANDS FOR SERVICE $w"
    bash $dir/kube-cmd.sh $namespace $w "$cmd"
    echo ""
  fi
done