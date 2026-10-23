# Changelog

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
