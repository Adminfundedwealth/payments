# Railway deployment

The application is a Laravel/PHP service, not a static Vite site. The
`Dockerfile` builds the Vite assets and runs Laravel with FrankenPHP, serving
only `public/`. Railway's injected `PORT` is used by the Caddy configuration.

## Web service

Use the repository root and `main` branch. Let Railway detect the root
`Dockerfile`; leave the custom build and start commands empty. Set the health
check path to `/up` and the pre-deploy command to:

```sh
php artisan migrate --force
```

Attach persistent storage at `/app/storage`. This preserves the install marker,
runtime files, and verification-node installation identity across deployments.
The application also creates its writable framework directories at boot.

## Database and production variables

Create a dedicated MySQL service in the Payments Railway project. Do not point
the gateway at the PostgreSQL service used by the FundedWealth application.
Use Railway variable references for the MySQL host, port, database, username,
and password; never copy credentials into this repository.

Set the following on the web service:

```text
APP_NAME=UPI Gateway
APP_ENV=production
APP_DEBUG=false
APP_URL=https://payments.fundedwealth.com
ASSET_URL=https://payments.fundedwealth.com
APP_KEY=<generate once in Railway and keep identical on every app service>
LOG_CHANNEL=stderr
DB_CONNECTION=mysql
DB_HOST=<Railway MySQL private host reference>
DB_PORT=<Railway MySQL private port reference>
DB_DATABASE=<Railway MySQL database reference>
DB_USERNAME=<Railway MySQL username reference>
DB_PASSWORD=<Railway MySQL password reference>
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database
GATEWAY_VERIFICATION_NODE_ID=payments-web-1
INSTALLER_ALLOW_REMOTE_DB=true
GATEWAY_WEBHOOK_ALLOW_INSECURE=false
GATEWAY_ALLOW_INSECURE_REDIRECTS=false
GATEWAY_CSP_ENFORCE=true
```

Use a unique `GATEWAY_VERIFICATION_NODE_ID` for each process service. Preserve
the project's other `GATEWAY_*` defaults unless the deployment has a documented
requirement to override them. Do not populate unused AWS or mail variables with
placeholder values. Set `TRUSTED_PROXIES` only to an explicitly verified proxy
range; do not use an unrestricted wildcard.

Generate `APP_KEY` once, keep it out of Git and logs, and reuse it for the web,
worker, and scheduler services. Keep `APP_DEBUG=false` and insecure webhook or
redirect options disabled.

## Queue and scheduler

Create worker and scheduler services from the same repository, branch, and
Dockerfile. Give each service the same database, application key, URL, session,
cache, and queue variables as the web service, using Railway references where
possible. Set distinct verification node IDs:

```sh
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

```sh
php artisan schedule:work
```

Run migrations only as the web service's pre-deploy command. Do not run
`migrate:fresh` or seed production data. The scheduler uses the application's
database cache locks and the schedule defined in `routes/console.php`.

## First-run and smoke tests

After the MySQL service is healthy, deploy the web service and confirm the
migration command completes before enabling public traffic. Complete the
application's first-run installation and create the initial administrator
through its protected setup flow. Keep `/app/storage` persistent so the
installation marker survives redeploys. The installer restricts remote
database hosts by default; enable `INSTALLER_ALLOW_REMOTE_DB=true` only while
using the protected setup flow, then remove it and redeploy after installation.

Verify `/up`, `/login`, and the dashboard over HTTPS. Configure a sandbox
merchant/account and test only the sandbox payment simulator. Do not add live
provider credentials or initiate a real payment as part of deployment.
