# Changelog

## 1.8.2 (2026-10-27)

- **Fixed the "Select media" button on new slides**: the button now skips slide form validation (`#limit_validation_errors`), exactly like the open button of the core media library widget. Previously the empty media selection of a new slide failed validation before the button's Ajax callback could run, so the dialog never opened.
- **Any remaining dialog error is shown instead of an empty HTTP 500**: if building the media dialog fails, the error message appears in an alert and is written to the site log.

## 1.8.1 (2026-10-27)

- **Fixed a 500 error on the "Select media" button** in the slide form: the modal dialog must receive a rendered form array, not a form object - the object cannot be JSON-encoded into the Ajax response. The dialog is now built exactly like the core media library widget builds its add form.
- **The dialog now receives the widget ID and the current selection through the form state** (FormBuilder never forwards `getForm()` arguments to form constructors), and the widget ID stays stable across Ajax rebuilds - the chosen media item reliably appears in the slide form preview after closing the dialog.
- The media grid and the slide preview no longer break on media items without a thumbnail field value.

## 1.8.0 (2026-10-26)

- **Per-slide alt and title for the image**: new "Image alt text" and "Image title text" fields on the slide form. Empty = the client name is used (or the alt/title stored on the media item); the title is rendered as the `title` attribute of the slider and modal images.
- **Media selection now opens in a modal dialog**, exactly like the core media library: "Select media" opens a dialog with a filterable media grid (up to 200 items), an upload field ("Upload and use") and "Insert selected". The chosen media is applied instantly via Ajax, with a preview and a "Remove" button on the slide form.
- **The "Slider maximum width" setting is removed.** The block relies on the theme's `page-content` classes for the width. `our_clients_post_update_remove_slider_max_width()` clears the old config key on update.
- **Fixed: the slide order on the overview page** (`/admin/structure/our-clients`) now saves reliably when the weights are changed by hand (explicit `#tree` on the weight table plus a defensive save fallback and a redirect after saving).

## 1.7.2 (2026-10-26)

- **Fixed a PHP notice "Only variables should be passed by reference"** shown when saving a slide with a newly uploaded image (`reset()` on a function return in `SlideForm::mediaFromUpload()`).

## 1.7.1 (2026-10-26)

- **New "Button border color" setting** (Settings tab » '"More details" button'): controls the button border, the slide focus outline, the arrows on hover and the default hover background tint. Leave empty to follow the button text color. This makes the remaining accent-colored outlines directly adjustable instead of being derived from the text color.

## 1.7.0 (2026-10-26)

- **The vague "Accent color" setting is gone.** It is replaced by understandable per-element colors: the **button text color** (also used for the button border, the slide focus outline and the arrows on hover), a separate **button text color on hover** and the **review color** (stars, left border and background tint of the reviews in the modal window, plus the close button on hover). `our_clients_post_update_remove_accent_color()` clears the old config key on update.
- **Client name margins**: new "margin top" and "margin bottom" settings control the space around the client name (0 keeps the defaults).
- The modal window now picks up the block's custom colors via inline CSS variables synced from the slider.

## 1.6.0 (2026-10-26)

- **New "Heading" and "More details" button style settings**: heading font size (desktop/mobile), heading top/bottom margins, button text color, background, hover background, border radius, font size (desktop/mobile), height (desktop/mobile) and horizontal padding. Empty values keep the defaults (the theme's h2 style for the heading, the stormoff-style accent pill for the button).

## 1.5.0 (2026-10-26)

- **New slider design in the style of the "Our clients" block on stormoff.ru**: frameless slides with a full-bleed image, the client name and an accent pill button "More details", bold side arrows. The dots navigation is gone - the arrows cover paging.
- **The block heading is rendered as an `h2` and inherits the theme's h2 style entirely** (color, font, size) - the separate heading font size settings are removed and their config keys are cleared on update.
- The block wrapper carries the theme layout classes `page-content page-margin-top-middle page-margin-bottom-middle page-padding-top-big page-padding-bottom-middle` (edit the template to change them).
- **The "Media image" field of the slide form is collapsed by default** - the media list no longer takes over the form; it opens automatically when the field fails validation.
- New appearance defaults: accent color `#ff5073`, image height 390px (200px mobile), client name 24px. `our_clients_post_update_refresh_default_appearance()` applies them to sites that never changed the old defaults.
- The bundled Russian translation is re-imported automatically on module update (versioned import state).
- **Confirmed compatible with Drupal 9.3+ / 9.5 / 10 / 11 and PHP 7.4.** The module has no contrib dependencies, so no `composer require drupal/media_library_form_element` is needed (that package requires Drupal 10.3+ and fails on Drupal 9) - just update the module itself and run `drush updb -y && drush cr`.
- Stale translations from the media_library_form_element era removed from the bundled Russian translation.

## 1.4.0 (2026-10-24)

- **Reviews are now a separate entity with a multi-value field on the slide.** Each review holds: author name, review date, city, a 1-5 star rating and the text. Add as many reviews as needed right in the slide form ("Add review" button), edit and delete them individually.
- The modal window renders the reviews in the site style: star rating, quote, author, city and date.
- Legacy single review texts are converted into review entities on update (`our_clients_post_update_reviews_to_entity()`, rating 5).
- Reviews are deleted together with their slide.

## 1.3.0 (2026-10-24)

- **Self-contained media selection field** («Медиа-изображение»): pick an existing media item from a thumbnail grid or upload a new image in the same form. The upload is stored as a media item automatically.
- The contrib dependency `media_library_form_element` (and the core Media Library module requirement) is **gone** - the field is rendered by the module itself and cannot silently disappear because of disabled dependencies. Requires only the core `media` module.
- The bundled Russian translation is imported automatically on the module admin pages when the admin UI language is Russian (works even if the module was installed before the locale module).
- `our_clients_post_update_detach_media_library_form_element()` cleanly detaches the no-longer-used contrib module during `drush updb`.

## 1.2.1 (2026-10-24)

- **Self-healing dependencies**: when the module code is updated over an already-enabled module, Composer installs the new dependency packages but never enables them, and Drupal silently hides unknown form element types - the media field disappeared without any error. The slide form now enables the missing media modules itself (Media, Media library, Media library form element) the first time it is opened.
- **Guaranteed image field**: if the media modules cannot be enabled automatically, the form falls back to a plain image upload and the uploaded file is stored as a media item automatically, so an image field is always present.
- The machine name field on the slide form now has a visible title.
- `slideMediaBundles()` falls back to every existing media type when the site has no image/video/oEmbed media types.

## 1.2.0 (2026-10-24)

- **Video slides**: the media field now also accepts media types based on `video` (uploaded video files) and `oembed` sources (YouTube, Vimeo, VK, Rutube and other providers configured on the site), in addition to images.
- In the slider a video slide shows its automatic thumbnail preview; clicking it opens the modal window where the video is played with the site's default media formatter (HTML5 player for uploaded videos, provider iframe for remote videos).
- The media field is now titled "Media" instead of "Image" (reflects the mixed content).

## 1.1.0 (2026-10-24)

- The slide image is now a **Media Library field**: upload new images or pick existing media items from the site media library. The `media`, `media_library` and `media_library_form_element` modules are enabled automatically as dependencies.
- Per-slide alt text removed: the alt text now belongs to the media item itself and is edited there. On update, existing slide images are automatically converted into media entities (`our_clients_post_update_image_to_media()`), the old alt text is preserved.
- The media items of a slide are kept in the library when the slide is deleted.
- Block cache metadata now also depends on the referenced media items, files and image styles.

## 1.0.1 (2026-10-23)

- Module-shipped configuration (settings, image styles) now declares an enforced dependency on `our_clients`, so it is removed on uninstall and a clean re-install is possible.

## 1.0.0 (2026-10-23)

- Initial release.
- Config entity "Slide" with image, alt text, client name, description, review, weight and published status.
- Admin section at `/admin/structure/our-clients` with drag & drop ordering, add/edit/delete and a settings tab (heading, visible slides per breakpoint, autoplay, accent color, sizes: slider max width, heading font size desktop/mobile, slide image height desktop/mobile, client name font size, modal width).
- `Our clients slider` block with a dependency-free vanilla JS slider: arrows, dots, swipe support, autoplay, keyboard and ARIA support.
- Modal window (opened by clicking a slide) with the larger image, description and the client review.
- Mobile-first responsive behavior: configurable number of visible slides and element sizes for desktop / tablet / mobile breakpoints.
- Two image styles (`our_clients_slider`, `our_clients_modal`) created automatically on install.
- File usage tracking: images are protected from garbage collection while used by a slide.
- Russian UI translation included.
