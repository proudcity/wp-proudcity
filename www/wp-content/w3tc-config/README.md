w3tc config for ProudCity
=========================


To build this:
1. Export config as json from /wp-admin/admin.php?page=w3tc_general
2. Convert to php array http://php.fnlist.com/php/json_decode
3. Search for `'betaredis`, replace with `$key.'redis`
4. Save as master.php

```
<?php
$key = getenv('WORDPRESS_DB_NAME');

return ...
```