# Shared Layouts

No shared application shell, header, navigation, footer, or Blade layout exists yet.

The existing `/` route renders `resources/views/welcome.blade.php` directly as a complete HTML document. That file is Laravel's framework welcome placeholder and is not an application layout to preserve for the new Kioosk homepage.

## Frontend entry configuration

`vite.config.js`:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
```
