# Updates for the 1.2.0 release
# Update footer action widgets, update homepage panels

wp plugin install --activate wp-ses --allow-root

# Update footer action widgets
wp eval --allow-root "
update_option('sidebars_widgets', array (
  'wp_inactive_widgets' => 
  array (
    0 => 'proud_social_app-3',
    1 => 'nav_menu-2',
    2 => 'recent-posts-2',
    3 => 'archives-2',
    4 => 'categories-2',
    5 => 'meta-2',
    6 => 'search-2',
  ),
  'footer-actions' => 
  array (
    0 => 'proudscore_widget-2',
    1 => 'proud_share_links-2',
    2 => 'proud_font_size-2',
    3 => 'proud_google_translate-2',
  ),
  'sidebar-footer' => 
  array (
    0 => 'proud_logo-3',
    1 => 'proud_social_links-2',
    2 => 'gform_widget-2',
    3 => 'proud_contact_block-2',
  ),
  'sidebar-primary' => 
  array (
    0 => 'submenu-2',
  ),
  'sidebar-news' => 
  array (
    0 => 'proud_teaser_list-5',
  ),
  'sidebar-agency' => 
  array (
    0 => 'agency_menu-2',
    1 => 'agency_contact-2',
    2 => 'agency_hours-2',
  ),
  'sidebar-event' => 
  array (
    0 => 'proud_teaser_filters-2',
    1 => 'proud_teaser_list-3',
    2 => 'proud_icon_link-2',
  ),
  'sidebar-job' => 
  array (
    0 => 'proud_teaser_filters-3',
    1 => 'proud_teaser_list-4',
  ),
  'array_version' => 3,
));"

# Update homepage panels
wp eval --allow-root "array (
  'widgets' => 
  array (
    0 => 
    array (
      'text' => '<div class="lead">[slogan]</div>
<h1>[sitename]</h1>
[widget widget_name="SearchBox"]',
      'headertype' => 'full',
      'background' => 'image',
      'pattern' => '',
      'repeat' => 'full',
      'image' => '[featured-image]',
      'make_inverse' => 'no',
      'panels_info' => 
      array (
        'class' => 'JumbotronHeader',
        'raw' => false,
        'grid' => 0,
        'cell' => 0,
        'id' => 0,
        'style' => 
        array (
          'background_display' => 'tile',
        ),
      ),
    ),
    1 => 
    array (
      'active_tabs' => 
      array (
        'faq' => 'faq',
        'payments' => 'payments',
        'report' => 'report',
        'status' => 'status',
      ),
      'category_section' => 
      array (
        'education-2' => 'education-2',
        'jobs-contracts' => 'jobs-contracts',
        'payments' => 'payments',
        'business' => 'business',
        'transportation' => 'transportation',
        'property-housing' => 'property-housing',
        'permits-licensing' => 'permits-licensing',
        'services' => 'services',
        'culture-recreation' => 'culture-recreation',
      ),
      'panels_info' => 
      array (
        'class' => 'ActionsBox',
        'raw' => false,
        'grid' => 1,
        'cell' => 0,
        'id' => 1,
        'style' => 
        array (
          'background_display' => 'tile',
        ),
      ),
    ),
    2 => 
    array (
      'iconset' => 
      array (
        0 => 
        array (
          'link_title' => 'City Council',
          'link_url' => '/departments/city-council/',
          'fa_icon' => 'fa-users',
        ),
        1 => 
        array (
          'link_title' => 'Police',
          'link_url' => '/agencies/police-department',
          'fa_icon' => 'fa-shield',
        ),
        2 => 
        array (
          'link_title' => 'Jobs',
          'link_url' => '/jobs',
          'fa_icon' => 'fa-briefcase',
        ),
        3 => 
        array (
          'link_title' => 'Finance & Tax',
          'link_url' => '/departments/finance/',
          'fa_icon' => 'fa-money',
        ),
        4 => 
        array (
          'link_title' => 'Fire',
          'link_url' => '/agencies/fire-department',
          'fa_icon' => 'fa-fire',
        ),
        5 => 
        array (
          'link_title' => 'Service',
          'link_url' => '/departments/service-department/',
          'fa_icon' => 'fa-tint',
        ),
        6 => 
        array (
          'link_title' => 'Parks & Rec',
          'link_url' => '/departments/parks-recreation/',
          'fa_icon' => 'fa-pagelines',
        ),
        7 => 
        array (
          'link_title' => 'Planning & Building',
          'link_url' => '/departments/planning-building/',
          'fa_icon' => 'fa-university',
        ),
      ),
      'panels_info' => 
      array (
        'class' => 'IconSet',
        'raw' => false,
        'grid' => 1,
        'cell' => 0,
        'id' => 2,
        'style' => 
        array (
          'background_display' => 'tile',
        ),
      ),
    ),
    3 => 
    array (
      'title' => 'Recent news',
      'proud_teaser_content' => 'post',
      'proud_teaser_display' => 'mini',
      'show_filters' => 'no',
      'post_count' => '3',
      'link_title' => 'More news',
      'link_url' => '/news',
      'panels_info' => 
      array (
        'class' => 'TeaserListWidget',
        'raw' => false,
        'grid' => 2,
        'cell' => 0,
        'id' => 3,
        'style' => 
        array (
          'background_display' => 'tile',
        ),
      ),
    ),
    4 => 
    array (
      'title' => 'Upcoming events',
      'proud_teaser_content' => 'event',
      'proud_teaser_display' => 'mini',
      'show_filters' => 'no',
      'post_count' => '3',
      'link_title' => 'More events',
      'link_url' => '/events',
      'panels_info' => 
      array (
        'class' => 'TeaserListWidget',
        'raw' => false,
        'grid' => 2,
        'cell' => 1,
        'id' => 4,
        'style' => 
        array (
          'background_display' => 'tile',
        ),
      ),
    ),
    5 => 
    array (
      'title' => '',
      'accounts' => 'custom',
      'custom' => 
      array (
        'twitter:getproudcity' => 'twitter:getproudcity',
        'facebook:getproudcity' => 'facebook:getproudcity',
      ),
      'services' => 
      array (
        'facebook' => 'facebook',
        'twitter' => 'twitter',
        'youtube' => 'youtube',
        'instagram' => 'instagram',
      ),
      'widget_type' => 'wall',
      'post_count' => '10',
      'panels_info' => 
      array (
        'class' => 'SocialFeed',
        'grid' => 3,
        'cell' => 0,
        'id' => 5,
        'style' => 
        array (
          'background_image_attachment' => false,
          'background_display' => 'tile',
        ),
      ),
    ),
    6 => 
    array (
      'title' => '',
      'active_layers' => 
      array (
        'all' => 'all',
      ),
      'panels_info' => 
      array (
        'class' => 'LocalMap',
        'raw' => false,
        'grid' => 4,
        'cell' => 0,
        'id' => 6,
        'style' => 
        array (
          'background_display' => 'tile',
        ),
      ),
    ),
  ),
  'grids' => 
  array (
    0 => 
    array (
      'cells' => 1,
      'style' => 
      array (
        'row_stretch' => 'full',
        'background_display' => 'tile',
      ),
    ),
    1 => 
    array (
      'cells' => 1,
      'style' => 
      array (
      ),
    ),
    2 => 
    array (
      'cells' => 2,
      'style' => 
      array (
      ),
    ),
    3 => 
    array (
      'cells' => 1,
      'style' => 
      array (
        'background_display' => 'tile',
      ),
    ),
    4 => 
    array (
      'cells' => 1,
      'style' => 
      array (
        'row_stretch' => 'full',
        'background_display' => 'tile',
      ),
    ),
  ),
  'grid_cells' => 
  array (
    0 => 
    array (
      'grid' => 0,
      'weight' => 1,
    ),
    1 => 
    array (
      'grid' => 1,
      'weight' => 1,
    ),
    2 => 
    array (
      'grid' => 2,
      'weight' => 0.5,
    ),
    3 => 
    array (
      'grid' => 2,
      'weight' => 0.5,
    ),
    4 => 
    array (
      'grid' => 3,
      'weight' => 1,
    ),
    5 => 
    array (
      'grid' => 4,
      'weight' => 1,
    ),
  ),
);"