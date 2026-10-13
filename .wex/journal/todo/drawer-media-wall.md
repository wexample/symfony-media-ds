# Media wall (masonry grid with lazy images) and optional carousel

Opened: 2026-10-09
Updated: 2026-10-09
Author: agent:archeology-drawers

## Read this first — status of this todo

> **This is a proposal for discussion, not an order to code.** It comes from the 2026-10 "bottom of the drawers" archaeology pass. Read it, then discuss it with the owner: every design choice below is to be challenged and validated **before** any code is written. Do not start implementing on your own.
>
> - **Before anything else**: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/PACKAGES/PHP/.wex/local/package-agent-intro.md` — where you stand in the suite, what a package must look like, how to work, and how to judge a drawer todo.
> - Context: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/local/network/.wex/knowledge/readme/archeology/index.md.j2`, `sources.md.j2`, and the drawers page linked below.
> - Safety: `NETWORK/local/network` runs on production data — read its code only, never run anything against it. Anonymize anything taken from it. Never copy secrets found in its history.

Drawers page: `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/local/network/.wex/knowledge/readme/archeology/drawers-bundles-components.md.j2`, sections "Masonry media wall (`MasonryBundle`)" and "Owl carousel (`CarouselBundle`)".

## What it is

In 2019 the public home page of network showed a **wall of project cards**, the `MasonryBundle` by Lionel Péramo. It looked and behaved like this:
- **Layout.** A CSS grid packs cards of different heights with no holes: auto-fill columns of 100–355 px, rows of at least 335 px, a 20 px gap, `grid-auto-flow: dense`, centred with `space-around`.
- **Cards.** 15 px rounded corners, a soft grey shadow and a pointer cursor. Each card is a link.
- **Images.** `object-fit: cover`, 330–500 px high. Before loading, each image shows a radial black-to-grey gradient; that gradient also stands in for a missing picture.
- **Caption.** A title band at the bottom: 35 % black, light text with a dark shadow.
- **Lazy loading.** An `IntersectionObserver` (100 px margin) swaps `data-src` into `src` when more than 10 % of a card is visible.

The public project page also had a **carousel** of screenshots: a vendored Owl Carousel 2 (jQuery). It had lazy images, centred items and dots, showed one item on mobile and three from 600 px, and looped only when there was more than one image.

`symfony-media-ds` has `media-library` and `media-picker`, which are admin tools. Neither it nor `symfony-design-system` has a display gallery.

## Sources

```bash
cd /home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/repo.git
git show 73db4d09d:project/src/Wex/MasonryBundle/Resources/css/_style.scss
git show 73db4d09d:project/src/Wex/MasonryBundle/Resources/js/index.js
git show bf787c7ce:project/templates/pages/home/index.html.twig          # usage, cover → first screenshot → gradient fallback
git show 12e8e6ef1^:project/templates/pages/home/worker.html.twig        # later variant on typed Media (commented out)
git log --format='%h %ci %s' 7538e4972^..73db4d09d -- project/src/Wex/MasonryBundle   # 8 commits, Firefox workaround
# Carousel (only for its behaviour; do not port the library):
git show 84795b51a:project/src/Wex/BaseBundle/Resources/js/app.js        # initCarousel() options (and its `this.length` bug)
git show 12e8e6ef1^:project/templates/pages/project/show.html.twig
git show 1756b18e6:project/src/Wex/BaseBundle/Resources/templates/macros/components/owl_carousel.html.twig
```

## What to recover

- The visual recipe above: dense grid, rounded shadowed cards, gradient placeholder, caption band.
- The fallback order for a card's image: cover, then first screenshot, then gradient.
- Lazy loading on visibility.

## Proposed modern shape (suggestion)

- A `media-wall` component (Twig and Vue) in `symfony-media-ds`. It takes items `{media, title, href}`, gets image URLs and sizes from `symfony-media` (`Media` stores `width`/`height`, so cards can keep their real aspect ratio, for example with `grid-row: span n` computed from the ratio), uses the loader's lazy loader (`ComponentLazyLoaderService`) instead of its own observer, and draws the caption overlay as a slot.
- Optionally a `media-strip` carousel in plain CSS: `scroll-snap`, previous and next buttons, dots, no library.
- Possible consumers: a public projects portfolio (see `/home/weeger/Desktop/WIP/WEB/WEXAMPLE/NETWORK/archeo/drawers/todo/public-showcase.md`) and the media library's own grid view.

## Open questions

1. Does a display gallery belong in `symfony-media-ds`, or in `symfony-design-system` (generic, URLs only)?
2. Is the carousel wanted at all?
3. Should true CSS masonry (`grid-template-rows: masonry`, still behind flags) be used as a progressive enhancement?
