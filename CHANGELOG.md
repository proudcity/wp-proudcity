## 2026-04-16

- Hardened HTTP response headers: set `expose_php = Off` in `etc/php.ini` to suppress the `X-Powered-By: PHP/x.x.x` header, added `Header always unset X-Powered-By` to `etc/apache-vhost.conf` as a belt-and-suspenders removal at the Apache level, and added five standard security headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `X-XSS-Protection`, `Permissions-Policy`) to the same vhost config. Enabled `mod_headers` unconditionally in `Dockerfile` to support all of the above.

References: https://github.com/proudcity/wp-proudcity/issues/2795
References: https://github.com/proudcity/wp-proudcity/issues/2796
