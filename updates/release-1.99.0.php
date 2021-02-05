<?php

# Updates for the 1.93.0 release
# Delete old orphaned events

$posts = [
	2551 =>'citizen-police-academy',
];

foreach ($posts as $id => $name) {
	$post = wp_get_single_post($id);
	if ($post->post_name === $name) {
		wp_delete_post($id);
		echo "Deleted $id $name" . PHP_EOL;
	}
	else {
		echo "ERR: post_name ($post->post_name) != expected ($name)" . PHP_EOL;
	}
}



