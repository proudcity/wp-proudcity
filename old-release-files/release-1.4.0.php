<?php

$opt = get_option('sidebars_widgets');
$opt['sidebar-footer'] = array (
  0 => 'proud_contact_block-2',
  1 => 'gform_widget-2',
  2 => 'proud_social_links-2',
);
update_option('sidebars_widgets', $opt);
