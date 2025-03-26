<?php

require_once('wp-load.php');

echo 'Clearing widget area';

$sidebars_widgets = get_option('sidebars_widgets');

$sidebars_widgets['sidebar-job'] = [];

update_option('sidebars_widgets', $sidebars_widgets);
