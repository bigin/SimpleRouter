![SimpleRouter image](https://scriptor-cms.info/extensions/data/uploads/extensions/module/9/simple-router-cover.png?v=1 "SimpleRouter Cover Image")

SimpleRouter module can be used to provide URL routing in your Scriptor application.

### More infos: 
https://scriptor-cms.info/extensions/extensions-modules/simplerouter/

### Here's an example of usage

```php
<?php

use Scriptor\Route;

// Add a new route to your Scriptor application
Route::add('/info', function() {
    echo 'This is a simple URL router module for Scriptor CMS.';
}, 'GET');

/* Add a new route GET and POST for the same 
  pattern and map it to the controller. */
Route::add('/contact', 'Controllers\Controller::contact', 
    ['GET', 'POST']
);

// Execute router
Route::run();
```