---
paths:
  - 'app/{Http,Models,Services,Support}/**|resources/views/**|routes/web.php'
---

# Views

## Canonical public entity URLs
Use /place/{slug}, /category/{slug}, /city/{slug}, and /user/{slug} as canonical public entity URLs. Business pages must remain server-rendered and emit LocalBusiness/Review plus BreadcrumbList JSON-LD; sitemap children list only public entities.
