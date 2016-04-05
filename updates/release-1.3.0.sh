# Updates for the 1.3.0 release
# Replace Black Studio TinyMCE with Site Origin, update homepage panels

# Replace Black Studio TinyMCE with Site Origin
wp search-replace 'WP_Widget_Black_Studio_TinyMCE' 'SiteOrigin_Widget_Editor_Widget'  --allow-root
wp plugin deactivate black-studio-tinymce-widget --allow-root


# Update homepage panels
wp eval "
update_post_meta( get_option('page_on_front'), 'panels_data',
array (
  'widgets' => 
  array (
    0 => 
    array (
      'text' => '<div class=\"lead\">[slogan]</div>
<h1>[sitename]</h1>
[widget widget_name=\"SearchBox\"]',
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
      'title' => 'Services',
      'iconset' => 
      array (
        0 => 
        array (
          'link_title' => 'City Council',
          'link_url' => '/departments/city-council/',
          'fa_icon' => 'fa-users',
          'weight' => '0',
        ),
        1 => 
        array (
          'link_title' => 'Police',
          'link_url' => '/agencies/police-department',
          'fa_icon' => 'fa-shield',
          'weight' => '1',
        ),
        2 => 
        array (
          'link_title' => 'Jobs',
          'link_url' => '/jobs',
          'fa_icon' => 'fa-briefcase',
          'weight' => '2',
        ),
        3 => 
        array (
          'link_title' => 'Finance & Tax',
          'link_url' => '/departments/finance/',
          'fa_icon' => 'fa-money',
          'weight' => '3',
        ),
        4 => 
        array (
          'link_title' => 'Fire',
          'link_url' => '/agencies/fire-department',
          'fa_icon' => 'fa-fire',
          'weight' => '4',
        ),
        5 => 
        array (
          'link_title' => 'Service',
          'link_url' => '/departments/service-department/',
          'fa_icon' => 'fa-tint',
          'weight' => '5',
        ),
        6 => 
        array (
          'link_title' => 'Parks & Rec',
          'link_url' => '/departments/parks-recreation/',
          'fa_icon' => 'fa-pagelines',
          'weight' => '6',
        ),
        7 => 
        array (
          'link_title' => 'Planning & Building',
          'link_url' => '/departments/planning-building/',
          'fa_icon' => 'fa-university',
          'weight' => '7',
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
      'title' => 'News',
      'proud_teaser_content' => 'post',
      'proud_teaser_display' => 'mini',
      'post_count' => '3',
      'link_title' => 'More news',
      'link_url' => '/news',
      'show_filters' => '1',
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
      'title' => 'Events',
      'proud_teaser_content' => 'event',
      'proud_teaser_display' => 'mini',
      'post_count' => '3',
      'link_title' => 'More events',
      'link_url' => '/events',
      'show_filters' => '1',
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
      'title' => 'Connect',
      'accounts' => 'all',
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
)

);" --allow-root
