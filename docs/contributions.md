# Kioosk contributions

## Routes and behavior

- `/contribute`: Persian, RTL business search, new business details, review, photos, and inline SMS login. Any Iranian city can be entered. GPS is optional.
- `/account/contributions`: server drafts, pending business decisions, reviews, photo removal, saved places, and notifications.
- `/admin/submissions`: requires recent staff OTP. Preview submissions, edit business details, approve, request corrections, reject, or merge duplicates.
- `/admin/reports`: inspect reported text/photos, hide, restore, or dismiss with an audit reason.
- `/` and `/businesses/{slug}` read approved records and published reviews from the database.

Verified users can submit businesses without becoming owners. Their new businesses and attached reviews/photos remain private until approval. Reviews on existing approved businesses publish immediately. Recently authenticated staff can publish new businesses immediately. Approved owners cannot rate their own businesses and can post one reply per review.

Draft UUIDs provide submission and upload idempotency; versions prevent stale edits. Exact canonical name/city/address matches reuse one business. Similar names in the same city require confirmation. Branches at different addresses remain separate. Moderation merges preserve the existing review when the author already has one, moving the conflicting contribution and photos into a private correction draft.

## Dates and storage

PHP requires `intl`, `gd`, and `exif`. Dates use the Persian calendar, Persian digits, and `Asia/Tehran` for display and civil visit dates. Database timestamps remain UTC. Visit dates are stored as Gregorian date-only values after strict Jalali validation, including leap years and future-date rejection. Opening hours are free text in Tehran time.

Guests keep drafts and compressed photos in IndexedDB on their device for seven days after last save. Authenticated drafts sync to the server and can be resumed from another device. Browser storage failures are displayed explicitly. Drafts remain intact through OTP and failed submissions. On a shared device, signing out does not remove IndexedDB data; account ownership filters prevent automatic recovery under another account. Do not store sensitive personal information in review drafts.

Images: up to six per contribution/review; JPEG, PNG, WebP; server limit 10 MiB and 20 megapixels. Camera/gallery input compresses images before upload. HEIC requires conversion to JPEG. The server validates and re-encodes images, corrects EXIF orientation, strips metadata, and creates gallery/thumbnail JPEGs on the private `local` disk. `/media/{id}` applies visibility checks; never expose `storage/app/private` through the web server.

## Deployment

Run from the application root:

```sh
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci
npm run build
php artisan optimize
```

Configure HTTPS, secure session cookies, a shared database-backed cache/session store, writable private storage, and `APP_DEBUG=false`. Set PHP `upload_max_filesize` to at least `10M`, `post_max_size` to at least `12M`, and the proxy request-body limit accordingly. Media processing is synchronous and bounded; provision PHP workers/memory for it. Writes currently use a shared cache lock to serialize contribution changes; reassess its contention before high-volume rollout.

Run a supervised database queue worker (for in-app notifications) and Laravel's scheduler:

```sh
php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=60
```

```cron
* * * * * cd /var/www/html/kioosk.space && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler cleans expired drafts/abandoned media at 03:00 Tehran time and monitors queue depth every five minutes. `contributions:cleanup` logs pending submissions, open reports, and failed jobs; warning threshold is in `config/contributions.php`. Monitor Laravel exceptions and `contribution.upload_failed`, `contributions.health`, and `contributions.attention_required`. Configure infrastructure alert routing separately. No system service or cron configuration is installed automatically by application migrations.

OTP delivery intentionally remains log-only in the private daily `sms` log channel. It is not connected to a telecom SMS provider. Do not expose those logs publicly.

## Data and validation

Existing business records are retained as incomplete until administrators fill mandatory fields. There is no automatic import of browser demo reviews. The optional `DemoBusinessSeeder` requires `APP_ENV=local` or `testing` and `KIOOSK_DEMO=true`; keep it disabled for real data.

Cities are managed in the `cities` table and appear as searchable selects across search and business forms. Add a city with:

```sh
php artisan kioosk:city "یزد"
```

```sh
php artisan test --compact
npm run build
```

Feature tests cover submission/approval, privacy, OTP adoption, duplicate handling and merges, revisions, permissions, malicious images, date boundaries, and draft expiration. Browser verification uses an isolated temporary database and storage directory, never the live database.
