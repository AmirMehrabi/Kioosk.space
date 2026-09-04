# Kioosk — Homepage Design System

## Product context

Kioosk is a Persian-language local discovery and review product, conceptually similar to Yelp but designed for Iranian users and right-to-left reading. The homepage helps a visitor answer one question quickly: «کجا بریم؟» It should make finding a restaurant, café, shop, doctor, or local service feel effortless.

Primary jobs:

- Search for a place or service by name/category and city or neighborhood.
- Browse a small, understandable set of popular categories.
- Discover well-reviewed nearby places through real-looking dummy content.
- Read short community review signals and confidently open a place.
- Encourage business owners to add their business without distracting from discovery.

Target: responsive web, desktop-first draft with a deliberate mobile collapse. Entire visible interface is Persian and `dir="rtl"`.

## Visual direction

Take interaction cues from Yelp: a search-led header, category shortcuts, strong rating signals, and scannable local-business cards. Do not reproduce Yelp's exact visual identity, logo, wording, layout, or trademarked assets. Kioosk should feel calmer, more editorial, and more contemporary.

Personality: warm, trustworthy, local, composed, useful. Elegant through typography, whitespace, and detail—not decoration. Avoid gradients, glassmorphism, oversized marketing typography, loud shadows, excessive pills, and dashboard-like density.

## Brand

- Wordmark: `کیوسک` in Persian text, with small Latin `KIOOSK` only where useful. Because no brand asset exists, use a carefully typeset wordmark rather than inventing an icon or SVG mark.
- Primary accent / pomegranate: `#C8323E`
- Accent hover: `#A92330`
- Ink: `#171717`
- Secondary ink: `#57534E`
- Muted: `#78716C`
- Canvas: `#FAF8F5`
- Surface: `#FFFFFF`
- Soft surface: `#F3EFE9`
- Border: `#E5E0D8`
- Positive/open: `#247A56`
- Rating gold: `#E7A83E`

Use red sparingly for the primary search action, active accents, and tiny brand moments. Rating stars remain warm gold so rating and action color are not confused.

## Typography

- Persian UI and headings: `Vazirmatn`, `Tahoma`, `Arial`, sans-serif.
- Latin fallback: `Instrument Sans`, sans-serif.
- Body: 15–16px, line-height 1.8 for Persian readability.
- Small metadata: 12–14px, line-height 1.6.
- Section headings: 24–30px, weight 700.
- Hero heading: 42–52px desktop, 32–38px mobile, weight 800, tight but natural Persian line-height around 1.35.
- Avoid all-caps except the tiny optional Latin brand lockup.

## Layout and spacing

- Maximum content width: 1180–1240px, centered.
- Page padding: 24px desktop, 16px mobile.
- Spacing scale: 4, 8, 12, 16, 24, 32, 48, 64, 88px.
- Header height: about 72px; white/translucent only if legibility stays crisp, with a thin bottom border.
- Hero: compact and useful, not a full-screen billboard. Search should be above the fold.
- Cards use a simple grid: 3 across desktop, 2 tablet, 1 mobile.

## Components

- Corners: 12px inputs/buttons, 14–16px cards, 18px category tiles. Avoid making every container a pill.
- Borders: 1px `#E5E0D8`; use borders more than shadows.
- Shadows: subtle only: `0 8px 30px rgba(36, 27, 20, .07)` for elevated search or hover; otherwise none.
- Primary button: pomegranate background, white label, 44–48px height, 12px radius, bold text.
- Secondary button: transparent/white with ink text and neutral border.
- Search: one clear horizontal control on desktop with query field, location field, and square-ish red submit button; stack cleanly on mobile.
- Category tiles: simple line icon inside a pale warm square, concise Persian label, roomy click target.
- Place cards: one strong 4:3 or 3:2 photo, name, numeric score plus five compact stars, review count, category, neighborhood, price cue, open/closed status, and one short review excerpt. Keep metadata scannable.
- Avatar: small circle only within review attribution.

## Homepage content architecture

1. Header: Kioosk wordmark, compact links «کشف مکان‌ها»، «نوشتن نظر»، «برای کسب‌وکارها», sign-in, outlined sign-up.
2. Hero: friendly Persian promise, supporting line, primary two-part search, and a short row of popular query links. Use a restrained photographic/local-food visual treatment only if it does not reduce search clarity.
3. Popular categories: six obvious categories—رستوران، کافه، خرید، پزشک، زیبایی، خدمات منزل.
4. Recommended places: section titled «این دوروبر چه خبره؟» with three polished dummy venue cards from Tehran.
5. Community pulse: two compact recent review snippets or a horizontal strip, emphasizing people and trust.
6. Business-owner invitation: a low-key warm panel with concise copy and secondary CTA.
7. Footer: minimal link groups, city selector, copyright.

Suggested dummy venues: «کافه ری‌را — ولیعصر»، «رستوران گیلانه — جردن»، «نانوایی سحر — یوسف‌آباد». Use plausible Persian names, review counts, and short natural review excerpts; clearly treat all as sample data.

## Interaction and motion

- Hover: border darkens slightly, card raises by 2px, 160–200ms ease-out.
- Focus: visible 2px pomegranate outline with offset; never remove keyboard focus.
- Search suggestions may appear as a clean white popover, but the static draft should not look busy.
- Respect reduced motion. No auto-rotating carousels or animated hero backgrounds.

## Accessibility and localization

- True RTL layout and right-aligned Persian copy; numbers may remain Persian digits where practical.
- Minimum 4.5:1 contrast for normal text.
- Touch targets at least 44px.
- Icons must have text labels or accessible names.
- Do not use flags as language icons.
- Maintain a clear heading hierarchy and visible labels/placeholders.

## Responsive behavior

- Under 768px, collapse navigation links into one menu button, keep wordmark visible, stack search fields, use a two-column category grid, and one-column venue list.
- Preserve rating, open status, and neighborhood at small widths; shorten excerpts before hiding trust signals.

## Hard constraints

Use only the fonts, colors, spacing, and component styles defined here. Do not introduce purple, blue gradients, decorative serif type, neon color, glass effects, or alternate visual systems. Keep the page very simple and easy to use while still feeling finished and premium.
