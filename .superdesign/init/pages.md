# Page Dependency Trees

## `/` — Laravel starter placeholder

Entry: `resources/views/welcome.blade.php`

Dependencies:

- `resources/views/welcome.blade.php`
  - `resources/css/app.css` (through `@vite`)
  - `resources/js/app.js` (through `@vite`)
  - `routes/web.php` (route declaration and conditional route checks)
  - `vite.config.js` (asset pipeline and font configuration)

The requested Kioosk homepage does not exist yet. There is no representative product page or shared shell to reuse, so its design context should use the application theme plus the new Kioosk design system.
