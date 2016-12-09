# bash kube-cmd.sh $NAMESPACE beta "wp option get blogname"
# bash kube-cmd.sh $NAMESPACE example "bash ls wordpress"

namespace=$1
app=${2}
cmd=${3}

fullcmd="${cmd/wp\ /wp\ \-\-path\=wordpress \-\-allow\-root\ }"

pod=$(kubectl --namespace=$namespace get pods --selector=app=$app --output=jsonpath={.items[0].metadata.name})

echo "kubectl --namespace=$namespace exec $pod -- $fullcmd"
kubectl --namespace=$namespace exec $pod -- $fullcmd $args
