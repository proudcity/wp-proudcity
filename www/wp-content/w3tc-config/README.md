w3tc config for ProudCity
=========================


To build this:
1. Export config as json from
2. Convert to php array
3. Replace redis host, pass to envvars and add code above
4. Save as master.php

```
<?php
$redisHost = getenv('WP_REDIS_BACKEND_HOST');
$redisAuth = getenv('WP_REDIS_BACKEND_AUTH');

return ...
```