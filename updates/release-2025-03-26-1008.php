<?php

require_once('wp-load.php');

function empty_widget_area($sidebar_id)
{
    $sidebars_widgets = get_option('sidebars_widgets');

    if (isset($sidebars_widgets[$sidebar_id])) {
        $sidebars_widgets[$sidebar_id] = []; // Empty the widget area
        update_option('sidebars_widgets', $sidebars_widgets);
    }
}

echo 'Clearing widget area';

// Call the function with the widget area ID
empty_widget_area('sidebar-job');
