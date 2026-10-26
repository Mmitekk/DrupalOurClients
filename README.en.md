# Our clients

A module for Drupal 9.3+ / 10 / 11 that adds a configurable **"Our clients"** block — a slider with client images and names. Clicking a slide opens a **modal window** with the enlarged image, the client description and the client review. A slide can hold not only an image but also a **video** — an uploaded file or a YouTube/VK/other-provider clip: the slider shows an automatic thumbnail preview, the modal window plays the video.

The slider appearance and all of its content are managed in the module's admin section: block heading, number of visible slides, autoplay, accent color, plus adding / editing / sorting slides (image, alt text, name, description, review).

[Читать документацию на русском](./README.md)

![Our clients slider](docs/screenshot-slider.png)

## Features

- **`Our clients slider` block** — place it into any theme region via the standard Block layout page
- **Dependency-free slider** — pure vanilla JS (~12 KB): arrows, dot navigation, mouse and touch swipe, autoplay with pause on hover
- **Modal window** — clicking a slide opens the client card: large image (or a video player), description and **client reviews** — star rating, quote, author, city and date. Close via the cross, clicking outside the window or the Escape key
- **Full-featured admin** (Structure » Our clients):
  - Slides list with thumbnails and **drag & drop ordering**
  - Slide form: the **"Media image" field** — pick an existing media item from a thumbnail grid or upload a new file (stored as a media item automatically), client name, description (text format), **reviews** (multi-value field: author name, date, city, 1-5 rating, text — add as many as needed with the "Add review" button), published flag
  - Settings tab: block heading, visible slides count (desktop / tablet / mobile), autoplay and its interval, **accent color** (pick your theme color so the block blends into the design), plus sizes: slider max width, heading font size (desktop and mobile), slide image height (desktop and mobile), client name font size, modal window width
- **Mobile-first responsive behavior** — separate values for the number of visible slides, image height and heading size on mobile, touch swipe navigation, compact arrows
- **Accessible** — carousel ARIA attributes, keyboard arrow navigation, focus trap in the modal, `prefers-reduced-motion` support
- **Media library and video** — the slide image or video is stored as a media item: no file duplication, one media item can be reused; media types with image, video and oEmbed sources are supported (YouTube, VK and other providers configured on the site); the alt text is managed by the media item
- **Automatic image styles** — `our_clients_slider` (480x360, no upscale) and `our_clients_modal` (up to 1200px) are created on install
- **File usage tracking** — slide images are protected from garbage collection and released automatically when a slide is replaced or deleted
- **Caching** — the block updates whenever any slide or setting changes; with an empty list the block is hidden entirely for anonymous visitors, while administrators see a hint with a link to the admin section
- **Russian translation included** — the UI is translated automatically on Russian-language sites
- **Compatibility** — Drupal 9.3.x, 10.x, 11.x; jQuery is not required

## Requirements

- Drupal 9.3+ / 10 / 11
- PHP version required by your Drupal version

The module only requires the core **Media** module — no Media Library, no contrib dependencies: the media selection is built into the slide form itself. The slide image is stored as a media item and reused without file duplication.

## Installation

### Option 1: via Composer (recommended)

1. Add the module repository to your project's Composer configuration (the command below does all `composer.json` editing automatically):

   ```bash
   composer config repositories.our-clients vcs https://github.com/Mmitekk/DrupalOurClients.git
   ```

2. Install the module:

   ```bash
   composer require mmitekk/our-clients:^1.0
   ```

   Composer downloads the stable release and puts the module into `modules/custom/our-clients/` (or `web/modules/custom/our-clients/` — the path depends on your project structure).

3. Enable the module:

   ```bash
   drush en our_clients -y
   ```

   or via the UI: **Manage » Extend** (`/admin/modules`), find "Our clients" and check it.

4. Rebuild caches:

   ```bash
   drush cr
   ```

**Updating the module:**

```bash
composer update mmitekk/our-clients
drush updb -y
drush cr
```

> **Note:** if your project has no custom installer-paths, Composer places the module according to your project's `installer-paths`. Make sure the resulting folder lives inside the Drupal modules directory and contains the `our_clients.info.yml` file.

### Option 2: manual

1. Download the latest release archive from the [Releases](https://github.com/Mmitekk/DrupalOurClients/releases) page (the `our_clients-X.Y.Z.zip` file).
2. Extract the archive — inside you will find the `our_clients` folder.
3. Copy it into your site's `modules/custom/` directory, so you get `modules/custom/our_clients/our_clients.info.yml`.
4. Enable the module on the **Manage » Extend** page and rebuild caches.

## Quick start

1. Open **Manage » Structure » Our clients** (`/admin/structure/our-clients`).
2. Click **"Add slide"**: in the "Media image" field pick an existing media item from the thumbnail grid or upload a new file, enter the client name, description and review, save. Repeat for the other clients.
3. Optionally open the **"Settings"** tab and set the block heading ("Our clients" by default), the number of visible slides and your theme's accent color.
4. Place the block: **Manage » Structure » Block layout** (`/admin/structure/block`) » choose a region » **"Place block"** » find **"Our clients slider"**.
5. In the placement form, hide the technical block title (the "Display block title" checkbox) if the heading is already set in the module settings — otherwise the title will appear twice.
6. Save and open any page of that region.

## Module admin

| Section | Path | What is configured |
|---|---|---|
| Slides | `/admin/structure/our-clients` | Slides list, drag & drop ordering, "Add slide" button, Edit/Delete operations |
| Slide form | `/admin/structure/our-clients/add` | Media image (library selection or upload), client name, description, reviews (multi-value: author, date, city, rating, text), published flag |
| Settings | `/admin/structure/our-clients/settings` | Block heading, visible slides (3 breakpoints), autoplay, interval, sizes (slider width, fonts, image height, modal width), accent color |

The section is also linked from the admin menu: **Manage » Structure » Our clients**.

Permissions: managing the section requires the **"Administer Our clients"** permission (granted to administrators by default).

## Theming

The block is styled with CSS variables — override them in your theme without touching module files (some of them are also exposed in the module settings):

```css
.our-clients {
  --oc-accent: #006ab1;              /* accent color (also in module settings) */
  --oc-max-width: 1200px;            /* slider max width (also in settings) */
  --oc-heading-size: 28px;           /* heading size (also in settings) */
  --oc-heading-size-mobile: 22px;    /* heading size on mobile (also in settings) */
  --oc-image-height: 130px;          /* slide image height (also in settings) */
  --oc-image-height-mobile: 100px;   /* image height on mobile (also in settings) */
  --oc-name-size: 14px;              /* client name size (also in settings) */
  --oc-text: #1a171b;                /* main text */
  --oc-text-muted: #5b6470;          /* captions under images */
  --oc-bg: #ffffff;                  /* card background */
  --oc-surface: #f5f7fa;             /* modal image background */
  --oc-border: #e3e8ef;              /* borders */
  --oc-radius: 16px;                 /* card corner radius */
}
```

Default template: `templates/our-clients-slider.html.twig` — copy it into your theme's `templates` folder to override it (rebuild the theme cache afterwards).

The modal window (`oc-modal__*`) is appended directly to `<body>`, so its CSS variables have fallbacks and inherit the accent color set in the module settings.

## FAQ

**The slider does not appear on the page.**
Check: (1) the block has at least one **published** slide — an empty block is hidden completely; (2) the block is placed into a region that is actually rendered on that page; (3) the cache has been rebuilt (`drush cr`).

**Images are missing / broken previews.**
Rebuild the cache — image styles are generated on first request. Make sure the slide references a media item with an image (the "Image" field is required) and that the media item has its alt text filled.

**How to disable autoplay?**
The "Settings" tab » uncheck "Autoplay" » save.

**How to rename the "Our clients" heading?**
The "Settings" tab » the "Block heading" field (clear it to render the slider without a heading). The technical block title is changed in its placement form on the "Block layout" page.

**How to set the image alt text?**
A newly uploaded file gets the client name as its alt text automatically. Change it on the media item: /admin/content/media » edit » the "Alt" field. If the alt is empty, the client name is used instead.

**The module admin strings are in English.**
When the admin UI language is Russian, the module imports its bundled Russian translation automatically on the first open of its admin pages — reload the page once. Manual way: /admin/config/regional/translate/import » the `translations/our_clients.ru.po` file from the module folder.

**The admin UI is in English.**
Make sure the **Interface Translation** (`locale`) module is enabled and the interface language is Russian: the translation from the `translations/` folder is imported automatically on module install. If needed: **Manage » Configuration » Regional and language » User interface translation » Update** (`/admin/config/regional/translate/import`).

## License

[GPL-2.0-or-later](LICENSE.txt)
