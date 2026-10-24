# Changelog

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
