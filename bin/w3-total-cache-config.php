#!/usr/bin/php
<?php
/**
w3tc config for ProudCity
=========================

To enable and configure w3-total-cache
```
wp --allow-root plugin activate w3-total-cache
php /app/bin/w3-total-cache-config.php
```

To build this:
1. Export config as json from /wp-admin/admin.php?page=w3tc_general
2. Convert to php array http://php.fnlist.com/php/json_decode
3. Search for `'betaredis`, replace with `$key.'redis`
4. Paste as `$config` below

**/





$key = getenv('WORDPRESS_DB_NAME');

$config = array (
  'version' => '0.9.5.2',
  'cluster.messagebus.debug' => false,
  'cluster.messagebus.enabled' => false,
  'cluster.messagebus.sns.region' => '',
  'cluster.messagebus.sns.api_key' => '',
  'cluster.messagebus.sns.api_secret' => '',
  'cluster.messagebus.sns.topic_arn' => '',
  'dbcache.configuration_overloaded' => false,
  'dbcache.debug' => '0',
  'dbcache.enabled' => '0',
  'dbcache.engine' => 'redis',
  'dbcache.file.gc' => '3600',
  'dbcache.file.locking' => false,
  'dbcache.lifetime' => '3600',
  'dbcache.memcached.persistent' => true,
  'dbcache.memcached.aws_autodiscovery' => false,
  'dbcache.memcached.servers' => 
  array (
    0 => '127.0.0.1:11211',
  ),
  'dbcache.memcached.username' => '',
  'dbcache.memcached.password' => '',
  'dbcache.redis.persistent' => '1',
  'dbcache.redis.servers' => 
  array (
    0 => $key.'redis:6379',
  ),
  'dbcache.redis.password' => '',
  'dbcache.redis.dbid' => '2',
  'dbcache.reject.constants' => 
  array (
    0 => 'APP_REQUEST',
    1 => 'DOING_CRON',
    2 => 'DONOTCACHEDB',
    3 => 'SHORTINIT',
    4 => 'XMLRPC_REQUEST',
  ),
  'dbcache.reject.cookie' => 
  array (
  ),
  'dbcache.reject.logged' => '1',
  'dbcache.reject.sql' => 
  array (
    0 => 'gdsr_',
    1 => 'wp_rg_',
    2 => '_wp_session_',
  ),
  'dbcache.reject.uri' => 
  array (
    0 => '',
  ),
  'dbcache.reject.words' => 
  array (
    0 => '^\s*insert\b',
    1 => '^\s*delete\b',
    2 => '^\s*update\b',
    3 => '^\s*replace\b',
    4 => '^\s*create\b',
    5 => '^\s*alter\b',
    6 => '^\s*show\b',
    7 => '^\s*set\b',
    8 => "\bautoload\s+=\s+'yes'",
    9 => '\bsql_calc_found_rows\b',
    10 => '\bfound_rows\(\)',
  ),
  'objectcache.configuration_overloaded' => false,
  'objectcache.enabled' => '1',
  'objectcache.debug' => '0',
  'objectcache.enabled_for_wp_admin' => '1',
  'objectcache.fallback_transients' => '1',
  'objectcache.engine' => 'redis',
  'objectcache.file.gc' => '3600',
  'objectcache.file.locking' => false,
  'objectcache.memcached.servers' => 
  array (
    0 => '127.0.0.1:11211',
  ),
  'objectcache.memcached.persistent' => true,
  'objectcache.memcached.aws_autodiscovery' => false,
  'objectcache.memcached.username' => '',
  'objectcache.memcached.password' => '',
  'objectcache.redis.persistent' => '1',
  'objectcache.redis.servers' => 
  array (
    0 => $key.'redis:6379',
  ),
  'objectcache.redis.password' => '',
  'objectcache.redis.dbid' => '3',
  'objectcache.groups.global' => 
  array (
    0 => 'users',
    1 => 'userlogins',
    2 => 'usermeta',
    3 => 'user_meta',
    4 => 'site-transient',
    5 => 'site-options',
    6 => 'site-lookup',
    7 => 'blog-lookup',
    8 => 'blog-details',
    9 => 'rss',
    10 => 'global-posts',
  ),
  'objectcache.groups.nonpersistent' => 
  array (
    0 => 'comment',
    1 => 'counts',
    2 => 'plugins',
  ),
  'objectcache.lifetime' => '3600',
  'objectcache.purge.all' => false,
  'pgcache.configuration_overloaded' => false,
  'pgcache.enabled' => '1',
  'pgcache.comment_cookie_ttl' => '1800',
  'pgcache.debug' => false,
  'pgcache.engine' => 'redis',
  'pgcache.file.gc' => 3600,
  'pgcache.file.nfs' => false,
  'pgcache.file.locking' => false,
  'pgcache.lifetime' => '3600',
  'pgcache.memcached.servers' => 
  array (
    0 => '127.0.0.1:11211',
  ),
  'pgcache.memcached.persistent' => true,
  'pgcache.memcached.aws_autodiscovery' => false,
  'pgcache.memcached.username' => '',
  'pgcache.memcached.password' => '',
  'pgcache.redis.persistent' => '1',
  'pgcache.redis.servers' => 
  array (
    0 => $key.'redis:6379',
  ),
  'pgcache.redis.password' => '',
  'pgcache.redis.dbid' => '0',
  'pgcache.cache.query' => '1',
  'pgcache.cache.home' => '1',
  'pgcache.cache.feed' => '0',
  'pgcache.cache.nginx_handle_xml' => false,
  'pgcache.cache.ssl' => '1',
  'pgcache.cache.404' => '0',
  'pgcache.cache.headers' => 
  array (
    0 => 'Last-Modified',
    1 => 'Content-Type',
    2 => 'X-Pingback',
    3 => 'P3P',
    4 => 'Link',
  ),
  'pgcache.compatibility' => false,
  'pgcache.remove_charset' => false,
  'pgcache.accept.uri' => 
  array (
    0 => 'sitemap(_index)?\.xml(\.gz)?',
    1 => '([a-z0-9_\-]+)?sitemap\.xsl',
    2 => '[a-z0-9_\-]+-sitemap([0-9]+)?\.xml(\.gz)?',
  ),
  'pgcache.accept.files' => 
  array (
    0 => 'wp-comments-popup.php',
    1 => 'wp-links-opml.php',
    2 => 'wp-locations.php',
  ),
  'pgcache.accept.qs' => 
  array (
    0 => '',
  ),
  'pgcache.late_init' => '0',
  'pgcache.late_caching' => '0',
  'pgcache.mirrors.enabled' => '0',
  'pgcache.mirrors.home_urls' => 
  array (
    0 => '',
  ),
  'pgcache.reject.front_page' => '0',
  'pgcache.reject.logged' => '1',
  'pgcache.reject.logged_roles' => '1',
  'pgcache.reject.roles' => 
  array (
    0 => 'administrator',
    1 => 'author',
    2 => 'contributor',
    3 => 'editor',
  ),
  'pgcache.reject.uri' => 
  array (
    0 => 'wp-.*\.php',
    1 => 'index\.php',
  ),
  'pgcache.reject.ua' => 
  array (
    0 => '',
  ),
  'pgcache.reject.cookie' => 
  array (
    0 => 'wptouch_switch_toggle',
  ),
  'pgcache.reject.request_head' => false,
  'pgcache.purge.front_page' => '1',
  'pgcache.purge.home' => '1',
  'pgcache.purge.post' => '1',
  'pgcache.purge.comments' => '0',
  'pgcache.purge.author' => '0',
  'pgcache.purge.terms' => '0',
  'pgcache.purge.archive.daily' => '0',
  'pgcache.purge.archive.monthly' => '0',
  'pgcache.purge.archive.yearly' => '0',
  'pgcache.purge.feed.blog' => '1',
  'pgcache.purge.feed.comments' => '0',
  'pgcache.purge.feed.author' => '0',
  'pgcache.purge.feed.terms' => '0',
  'pgcache.purge.feed.types' => 
  array (
    0 => 'rss2',
  ),
  'pgcache.purge.postpages_limit' => '10',
  'pgcache.purge.pages' => 
  array (
    0 => 'residents',
    1 => 'businesses',
    2 => 'events',
    3 => 'news',
    4 => 'documents',
    5 => 'departments',
    6 => '',
  ),
  'pgcache.purge.sitemap_regex' => '([a-z0-9_\-]*?)sitemap([a-z0-9_\-]*)?\.xml',
  'pgcache.prime.enabled' => '0',
  'pgcache.prime.interval' => '900',
  'pgcache.prime.limit' => '10',
  'pgcache.prime.sitemap' => '',
  'pgcache.prime.post.enabled' => '0',
  'stats.enabled' => '0',
  'minify.configuration_overloaded' => false,
  'minify.enabled' => '1',
  'minify.auto' => '1',
  'minify.debug' => '0',
  'minify.engine' => 'redis',
  'minify.error.notification' => 'admin',
  'minify.file.gc' => '86400',
  'minify.file.nfs' => false,
  'minify.file.locking' => false,
  'minify.memcached.servers' => 
  array (
    0 => '127.0.0.1:11211',
  ),
  'minify.memcached.persistent' => true,
  'minify.memcached.aws_autodiscovery' => false,
  'minify.memcached.username' => '',
  'minify.memcached.password' => '',
  'minify.redis.persistent' => '1',
  'minify.redis.servers' => 
  array (
    0 => $key.'redis:6379',
  ),
  'minify.redis.password' => '',
  'minify.redis.dbid' => '1',
  'minify.rewrite' => '0',
  'minify.options' => 
  array (
  ),
  'minify.symlinks' => 
  array (
  ),
  'minify.lifetime' => '86400',
  'minify.upload' => true,
  'minify.html.enable' => '1',
  'minify.html.engine' => 'html',
  'minify.html.reject.feed' => '0',
  'minify.html.inline.css' => '0',
  'minify.html.inline.js' => '0',
  'minify.html.strip.crlf' => '0',
  'minify.html.comments.ignore' => 
  array (
    0 => 'google_ad_',
    1 => 'RSPEAK_',
  ),
  'minify.css.combine' => '0',
  'minify.css.enable' => '1',
  'minify.css.engine' => 'css',
  'minify.css.http2push' => '1',
  'minify.css.strip.comments' => '0',
  'minify.css.strip.crlf' => '0',
  'minify.css.embed' => false,
  'minify.css.imports' => 'process',
  'minify.css.groups' => 
  array (
  ),
  'minify.js.http2push' => '1',
  'minify.js.enable' => '1',
  'minify.js.engine' => 'js',
  'minify.js.combine.header' => '0',
  'minify.js.header.embed_type' => 'blocking',
  'minify.js.combine.body' => false,
  'minify.js.body.embed_type' => 'blocking',
  'minify.js.combine.footer' => false,
  'minify.js.footer.embed_type' => 'blocking',
  'minify.js.strip.comments' => '0',
  'minify.js.strip.crlf' => '0',
  'minify.js.groups' => 
  array (
  ),
  'minify.yuijs.path.java' => 'java',
  'minify.yuijs.path.jar' => 'yuicompressor.jar',
  'minify.yuijs.options.line-break' => 5000,
  'minify.yuijs.options.nomunge' => false,
  'minify.yuijs.options.preserve-semi' => false,
  'minify.yuijs.options.disable-optimizations' => false,
  'minify.yuicss.path.java' => 'java',
  'minify.yuicss.path.jar' => 'yuicompressor.jar',
  'minify.yuicss.options.line-break' => 5000,
  'minify.ccjs.path.java' => 'java',
  'minify.ccjs.path.jar' => 'compiler.jar',
  'minify.ccjs.options.compilation_level' => 'SIMPLE_OPTIMIZATIONS',
  'minify.ccjs.options.formatting' => '',
  'minify.csstidy.options.remove_bslash' => true,
  'minify.csstidy.options.compress_colors' => true,
  'minify.csstidy.options.compress_font-weight' => true,
  'minify.csstidy.options.lowercase_s' => false,
  'minify.csstidy.options.optimise_shorthands' => 1,
  'minify.csstidy.options.remove_last_;' => false,
  'minify.csstidy.options.case_properties' => 1,
  'minify.csstidy.options.sort_properties' => false,
  'minify.csstidy.options.sort_selectors' => false,
  'minify.csstidy.options.merge_selectors' => 2,
  'minify.csstidy.options.discard_invalid_properties' => false,
  'minify.csstidy.options.css_level' => 'CSS2.1',
  'minify.csstidy.options.preserve_css' => false,
  'minify.csstidy.options.timestamp' => false,
  'minify.csstidy.options.template' => 'default',
  'minify.htmltidy.options.clean' => false,
  'minify.htmltidy.options.hide-comments' => true,
  'minify.htmltidy.options.wrap' => 0,
  'minify.reject.logged' => '0',
  'minify.reject.ua' => 
  array (
    0 => '',
  ),
  'minify.reject.uri' => 
  array (
    0 => '',
  ),
  'minify.reject.files.js' => 
  array (
    "wp-includes/js/query/ui/core.min.js",
    "wp-includes/js/query/ui/menu.min.js",
    "wp-includes/js/query/ui/selectmenu.min.js",
    "wp-includes/js/query/ui/tooltip.min.js",
    "wp-content/plugins/wp-fullcalendar/includes/js/main.js"
  ),
  'minify.reject.files.css' => 
  array (
    0 => '',
  ),
  'minify.cache.files' => 
  array (
    0 => '',
  ),
  'minify.cache.files_regexp' => '0',
  'cdn.configuration_overloaded' => false,
  'cdn.enabled' => '0',
  'cdn.debug' => false,
  'cdn.engine' => 'maxcdn',
  'cdn.uploads.enable' => true,
  'cdn.includes.enable' => true,
  'cdn.includes.files' => '*.css;*.js;*.gif;*.png;*.jpg;*.xml',
  'cdn.theme.enable' => true,
  'cdn.theme.files' => '*.css;*.js;*.gif;*.png;*.jpg;*.ico;*.ttf;*.otf,*.woff,*.less',
  'cdn.minify.enable' => true,
  'cdn.custom.enable' => true,
  'cdn.custom.files' => 
  array (
    0 => 'favicon.ico',
    1 => '{wp_content_dir}/gallery/*',
    2 => '{wp_content_dir}/uploads/avatars/*',
    3 => '{plugins_dir}/wordpress-seo/css/xml-sitemap.xsl',
    4 => '{plugins_dir}/wp-minify/min*',
    5 => '{plugins_dir}/*.js',
    6 => '{plugins_dir}/*.css',
    7 => '{plugins_dir}/*.gif',
    8 => '{plugins_dir}/*.jpg',
    9 => '{plugins_dir}/*.png',
  ),
  'cdn.import.files' => false,
  'cdn.queue.interval' => 900,
  'cdn.queue.limit' => 25,
  'cdn.force.rewrite' => false,
  'cdn.autoupload.enabled' => false,
  'cdn.autoupload.interval' => 3600,
  'cdn.canonical_header' => false,
  'cdn.ftp.host' => '',
  'cdn.ftp.type' => '',
  'cdn.ftp.user' => '',
  'cdn.ftp.pass' => '',
  'cdn.ftp.path' => '',
  'cdn.ftp.pasv' => false,
  'cdn.ftp.domain' => 
  array (
  ),
  'cdn.ftp.ssl' => 'auto',
  'cdn.google_drive.client_id' => '',
  'cdn.google_drive.refresh_token' => '',
  'cdn.google_drive.folder.id' => '',
  'cdn.google_drive.folder.title' => '',
  'cdn.google_drive.folder.url' => '',
  'cdn.highwinds.account_hash' => '',
  'cdn.highwinds.api_token' => '',
  'cdn.highwinds.host.hash_code' => '',
  'cdn.highwinds.host.domains' => 
  array (
  ),
  'cdn.highwinds.ssl' => 'auto',
  'cdn.s3.key' => '',
  'cdn.s3.secret' => '',
  'cdn.s3.bucket' => '',
  'cdn.s3.cname' => 
  array (
  ),
  'cdn.s3.ssl' => 'auto',
  'cdn.s3_compatible.api_host' => 'auto',
  'cdn.cf.key' => '',
  'cdn.cf.secret' => '',
  'cdn.cf.bucket' => '',
  'cdn.cf.id' => '',
  'cdn.cf.cname' => 
  array (
  ),
  'cdn.cf.ssl' => 'auto',
  'cdn.cf2.key' => '',
  'cdn.cf2.secret' => '',
  'cdn.cf2.id' => '',
  'cdn.cf2.cname' => 
  array (
  ),
  'cdn.cf2.ssl' => '',
  'cdn.rscf.user' => '',
  'cdn.rscf.key' => '',
  'cdn.rscf.location' => 'us',
  'cdn.rscf.container' => '',
  'cdn.rscf.cname' => 
  array (
  ),
  'cdn.rscf.ssl' => 'auto',
  'cdn.rackspace_cdn.user_name' => '',
  'cdn.rackspace_cdn.api_key' => '',
  'cdn.rackspace_cdn.region' => '',
  'cdn.rackspace_cdn.service.access_url' => '',
  'cdn.rackspace_cdn.service.id' => '',
  'cdn.rackspace_cdn.service.name' => '',
  'cdn.rackspace_cdn.service.protocol' => 'http',
  'cdn.rackspace_cdn.domains' => 
  array (
  ),
  'cdn.azure.user' => '',
  'cdn.azure.key' => '',
  'cdn.azure.container' => '',
  'cdn.azure.cname' => 
  array (
  ),
  'cdn.azure.ssl' => 'auto',
  'cdn.mirror.domain' => 
  array (
  ),
  'cdn.mirror.ssl' => 'auto',
  'cdn.netdna.alias' => '',
  'cdn.netdna.consumerkey' => '',
  'cdn.netdna.consumersecret' => '',
  'cdn.netdna.authorization_key' => '',
  'cdn.netdna.domain' => 
  array (
  ),
  'cdn.netdna.ssl' => 'auto',
  'cdn.netdna.zone_id' => 0,
  'cdn.maxcdn.authorization_key' => '',
  'cdn.maxcdn.domain' => 
  array (
  ),
  'cdn.maxcdn.ssl' => 'auto',
  'cdn.maxcdn.zone_id' => 0,
  'cdn.cotendo.username' => '',
  'cdn.cotendo.password' => '',
  'cdn.cotendo.zones' => 
  array (
  ),
  'cdn.cotendo.domain' => 
  array (
  ),
  'cdn.cotendo.ssl' => 'auto',
  'cdn.akamai.username' => '',
  'cdn.akamai.password' => '',
  'cdn.akamai.email_notification' => 
  array (
  ),
  'cdn.akamai.action' => 'invalidate',
  'cdn.akamai.zone' => 'production',
  'cdn.akamai.domain' => 
  array (
  ),
  'cdn.akamai.ssl' => 'auto',
  'cdn.edgecast.account' => '',
  'cdn.edgecast.token' => '',
  'cdn.edgecast.domain' => 
  array (
  ),
  'cdn.edgecast.ssl' => 'auto',
  'cdn.att.account' => '',
  'cdn.att.token' => '',
  'cdn.att.domain' => 
  array (
  ),
  'cdn.att.ssl' => 'auto',
  'cdn.reject.admins' => false,
  'cdn.reject.logged_roles' => false,
  'cdn.reject.roles' => 
  array (
  ),
  'cdn.reject.ua' => 
  array (
  ),
  'cdn.reject.uri' => 
  array (
  ),
  'cdn.reject.files' => 
  array (
    0 => '{uploads_dir}/wpcf7_captcha/*',
    1 => '{uploads_dir}/imagerotator.swf',
    2 => '{plugins_dir}/wp-fb-autoconnect/facebook-platform/channel.html',
  ),
  'cdn.reject.ssl' => false,
  'varnish.configuration_overloaded' => false,
  'varnish.enabled' => '0',
  'varnish.debug' => false,
  'varnish.servers' => 
  array (
    0 => '',
  ),
  'browsercache.configuration_overloaded' => false,
  'browsercache.enabled' => '1',
  'browsercache.rewrite' => '0',
  'browsercache.hsts' => '0',
  'browsercache.no404wp' => '0',
  'browsercache.no404wp.exceptions' => 
  array (
    0 => 'robots\.txt',
    1 => '[a-z0-9_\-]*sitemap[a-z0-9_\-]*\.(xml|xsl|html)(\.gz)?',
  ),
  'browsercache.cssjs.last_modified' => '1',
  'browsercache.cssjs.compression' => '1',
  'browsercache.cssjs.expires' => '1',
  'browsercache.cssjs.lifetime' => '31536000',
  'browsercache.cssjs.nocookies' => '1',
  'browsercache.cssjs.cache.control' => '1',
  'browsercache.cssjs.cache.policy' => 'cache_public_maxage',
  'browsercache.cssjs.etag' => '0',
  'browsercache.cssjs.w3tc' => '0',
  'browsercache.cssjs.replace' => '0',
  'browsercache.html.compression' => '1',
  'browsercache.html.last_modified' => '1',
  'browsercache.html.expires' => '1',
  'browsercache.html.lifetime' => '3600',
  'browsercache.html.cache.control' => '1',
  'browsercache.html.cache.policy' => 'cache_public_maxage',
  'browsercache.html.etag' => '1',
  'browsercache.html.w3tc' => '0',
  'browsercache.html.replace' => false,
  'browsercache.other.last_modified' => '1',
  'browsercache.other.compression' => '1',
  'browsercache.other.expires' => '1',
  'browsercache.other.lifetime' => '31536000',
  'browsercache.other.nocookies' => '1',
  'browsercache.other.cache.control' => '1',
  'browsercache.other.cache.policy' => 'cache_public_maxage',
  'browsercache.other.etag' => '1',
  'browsercache.other.w3tc' => '0',
  'browsercache.other.replace' => '0',
  'browsercache.replace.exceptions' => 
  array (
    0 => '',
  ),
  'mobile.configuration_overloaded' => false,
  'mobile.enabled' => false,
  'mobile.rgroups' => 
  array (
    'high' => 
    array (
      'theme' => '',
      'enabled' => false,
      'redirect' => '',
      'agents' => 
      array (
        0 => 'android',
        1 => 'mobi',
        2 => 'bada',
        3 => 'incognito',
        4 => 'kindle',
        5 => 'maemo',
        6 => 'opera\ mini',
        7 => 's8000',
        8 => 'series60',
        9 => 'ucbrowser',
        10 => 'ucweb',
        11 => 'webmate',
        12 => 'webos',
      ),
    ),
    'low' => 
    array (
      'theme' => '',
      'enabled' => false,
      'redirect' => '',
      'agents' => 
      array (
        0 => '2\.0\ mmp',
        1 => '240x320',
        2 => 'alcatel',
        3 => 'amoi',
        4 => 'asus',
        5 => 'au\-mic',
        6 => 'audiovox',
        7 => 'avantgo',
        8 => 'benq',
        9 => 'bird',
        10 => 'blackberry',
        11 => 'blazer',
        12 => 'cdm',
        13 => 'cellphone',
        14 => 'danger',
        15 => 'ddipocket',
        16 => 'docomo',
        17 => 'dopod',
        18 => 'elaine/3\.0',
        19 => 'ericsson',
        20 => 'eudoraweb',
        21 => 'fly',
        22 => 'haier',
        23 => 'hiptop',
        24 => 'hp\.ipaq',
        25 => 'htc',
        26 => 'huawei',
        27 => 'i\-mobile',
        28 => 'iemobile',
        29 => 'iemobile/7',
        30 => 'iemobile/9',
        31 => 'j\-phone',
        32 => 'kddi',
        33 => 'konka',
        34 => 'kwc',
        35 => 'kyocera/wx310k',
        36 => 'lenovo',
        37 => 'lg',
        38 => 'lg/u990',
        39 => 'lge\ vx',
        40 => 'midp',
        41 => 'midp\-2\.0',
        42 => 'mmef20',
        43 => 'mmp',
        44 => 'mobilephone',
        45 => 'mot\-v',
        46 => 'motorola',
        47 => 'msie\ 10\.0',
        48 => 'netfront',
        49 => 'newgen',
        50 => 'newt',
        51 => 'nintendo\ ds',
        52 => 'nintendo\ wii',
        53 => 'nitro',
        54 => 'nokia',
        55 => 'novarra',
        56 => 'o2',
        57 => 'openweb',
        58 => 'opera\ mobi',
        59 => 'opera\.mobi',
        60 => 'p160u',
        61 => 'palm',
        62 => 'panasonic',
        63 => 'pantech',
        64 => 'pdxgw',
        65 => 'pg',
        66 => 'philips',
        67 => 'phone',
        68 => 'playbook',
        69 => 'playstation\ portable',
        70 => 'portalmmm',
        71 => '\bppc\b',
        72 => 'proxinet',
        73 => 'psp',
        74 => 'qtek',
        75 => 'sagem',
        76 => 'samsung',
        77 => 'sanyo',
        78 => 'sch',
        79 => 'sch\-i800',
        80 => 'sec',
        81 => 'sendo',
        82 => 'sgh',
        83 => 'sharp',
        84 => 'sharp\-tq\-gx10',
        85 => 'small',
        86 => 'smartphone',
        87 => 'softbank',
        88 => 'sonyericsson',
        89 => 'sph',
        90 => 'symbian',
        91 => 'symbian\ os',
        92 => 'symbianos',
        93 => 'toshiba',
        94 => 'treo',
        95 => 'ts21i\-10',
        96 => 'up\.browser',
        97 => 'up\.link',
        98 => 'uts',
        99 => 'vertu',
        100 => 'vodafone',
        101 => 'wap',
        102 => 'willcome',
        103 => 'windows\ ce',
        104 => 'windows\.ce',
        105 => 'winwap',
        106 => 'xda',
        107 => 'xoom',
        108 => 'zte',
      ),
    ),
  ),
  'referrer.configuration_overloaded' => false,
  'referrer.enabled' => false,
  'referrer.rgroups' => 
  array (
    'search_engines' => 
    array (
      'theme' => '',
      'enabled' => false,
      'redirect' => '',
      'referrers' => 
      array (
        0 => 'google\.com',
        1 => 'yahoo\.com',
        2 => 'bing\.com',
        3 => 'ask\.com',
        4 => 'msn\.com',
      ),
    ),
  ),
  'common.edge' => true,
  'common.support' => '',
  'common.track_usage' => '1',
  'common.tweeted' => false,
  'config.check' => '1',
  'config.path' => '',
  'widget.latest.items' => 3,
  'widget.latest_news.items' => 5,
  'widget.pagespeed.enabled' => '1',
  'widget.pagespeed.key' => '',
  'widget.pagespeed.show_in_admin_bar' => '0',
  'timelimit.email_send' => 180,
  'timelimit.varnish_purge' => 300,
  'timelimit.cache_flush' => 600,
  'timelimit.cache_gc' => 600,
  'timelimit.cdn_upload' => 600,
  'timelimit.cdn_delete' => 300,
  'timelimit.cdn_purge' => 300,
  'timelimit.cdn_import' => 600,
  'timelimit.cdn_test' => 300,
  'timelimit.cdn_container_create' => 300,
  'timelimit.domain_rename' => 120,
  'timelimit.minify_recommendations' => 600,
  'common.instance_id' => 1331943623,
  'common.force_master' => true,
  'extensions.active' => 
  array (
    'newrelic' => 'w3-total-cache/Extension_NewRelic_Plugin.php',
    'fragmentcache' => 'w3-total-cache/Extension_FragmentCache_Plugin.php',
  ),
  'extensions.active_frontend' => 
  array (
  ),
  'plugin.license_key' => '',
  'plugin.type' => '',
  'fragmentcache' => 
  array (
    'engine' => '',
  )
);

// If Cloudflare is active, add that plugin
if (!empty(getenv('CLOUDFLARE_KEY'))) {
    $config['extensions.active_frontend'] = array ('cloudflare' => '*');
    $config['cloudflare'] = [
        'email' => getenv('CLOUDFLARE_EMAIL'),
        'key' => getenv('CLOUDFLARE_KEY'),
        'zone_id' => getenv('CLOUDFLARE_ZONE_ID'),
        'zone_name' => getenv('HOST'),
        'widget_interval' => '-30',
        'widget_cache_mins' => '5',
        'pagecache' => '1',
    ];
    $config['extensions.active']['cloudflare'] = 'w3-total-cache/Extension_CloudFlare_Plugin.php';
}

// wp-fullcalendar doesn't like JS minification, so we switch it to just "combine"
$activePlugins = shell_exec('wp --allow-root plugin list --status=active');
if (strpos($activePlugins, 'wp-fullcalendar') !== false) {
    $config['minify.js.method'] = 'combine';
}

// Both combine + minify
$config['minify.js.method'] = 'both';

$tmpFile = '/tmp/w3tc.json';
echo "Writing w3-total-cache configuration file to $tmpFile" . PHP_EOL;
file_put_contents($tmpFile, json_encode($config));
chdir('/app/wordpress');

//echo "Enabling w3-total-cache" . PHP_EOL;
//shell_exec("wp --allow-root plugin activate w3-total-cache");

echo "Importing w3-total-cache configuration" . PHP_EOL;
shell_exec("wp --allow-root total-cache import $tmpFile");