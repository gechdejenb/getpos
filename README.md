# GetPos Inventory System Installation Guide
## Move /public Files to Project Folder When Hosting on cPanel

When hosting the project on cPanel, you need to move all files and folders from the /public directory to the root directory of your project. This ensures that the project's entry point is correctly configured.
Update PHP File

After moving the files, you need to update the PHP file (usually index.php) to reflect the new directory structure. Here's how you can modify the PHP file:

```bash
<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);    ```

```

## Migrate Database

To migrate the database, run the following commands:


php artisan migrate:fresh
php artisan db:seed
