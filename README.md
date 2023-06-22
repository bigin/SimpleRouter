# SimpleRouter
The SimpleRouter module provides URL routing functionality for your Scriptor application.

### Usage
To create custom routes for your application, follow these steps:

1. Open the `_routes.php` file.
2. Use the `Route::add()` method to add new routes.
3. Provide the route (path), the closure or controller method to execute when the route is matched, and the associated HTTP verb(s) as parameters to the `Route::add()` method.

Example:

```php
<?php
use Scriptor\Modules\SimpleRouter\Route;

// Add a route for the root URL
Route::add('/', function() {
    echo 'Welcome to SimpleRouter for Scriptor CMS!';
}, 'GET');

// Execute the router
Route::run();
```

For more examples and information, you can visit the [SimpleRouter documentation](https://scriptor-cms.info/extensions/extensions-modules/simplerouter/).