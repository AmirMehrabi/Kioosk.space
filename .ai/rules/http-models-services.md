---
paths:
  - 'app/{Http,Models,Services}/**'
---

# Http Models Services

## Homepage featured business selection
Homepage hero selection is separate from business_featured_media (the business-page gallery). Use businesses.is_featured and hero_media_id, managed only through freshly authenticated admin routes. A hero image must be public and belong to the featured approved business; selecting a new homepage business replaces the previous selection. Owners may edit their gallery but cannot change homepage selection.
