# Routes

## `/` — Current placeholder home

- Route file: `routes/web.php`
- View: `resources/views/welcome.blade.php`
- Layout: none; the view is a self-contained HTML document
- Current content: stock Laravel welcome page
- Requested target: replace conceptually with a new Persian RTL Kioosk discovery/review homepage

Full router configuration:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
```
