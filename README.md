# symfony-media-ds

Version: 2.0.3

## The library page

`/media/` (`media_library_index`) is symfony-media's library, browsed by whoever holds `MEDIA_VIEW`: symfony-design-system's file explorer, its folders on the side, its media in a grid of thumbnails or a list. Whoever holds `MEDIA_MANAGE` also adds files to the folder shown — sent in pieces by the loader's upload queue —, makes folders, renames and deletes.

The component is drawn anywhere with the options the server computes:

```twig
{{ vue(render_pass, '@WexampleSymfonyMediaDsBundle/components/media-library', media_library_options()) }}
```

`media_library_options({ multiple, picker, field, state_id })` gives its addresses, the actions the current user may take, a signed upload address for the library's intake directory, and its wording, resolved on the server.

Like any page, the library opens in a modal or a panel — `button_target(icon, label, path('media_library_index'), 'modal')` —, the way the tunnels of symfony-tunnels-demo do.

An upload lands in `<storage_dir>/intake`; `EventSubscriber\MediaUploadSubscriber` takes it from there (symfony-design-system's `UploadCompletedEvent`) into the folder its address names, and answers with the media it became.

## Picking media in a form

```php
$builder->add('cover', MediaPickerType::class);                                                  // one media: a ManyToOne
$builder->add('attachments', MediaPickerType::class, ['multiple' => true, 'target' => 'panel']); // several: a ManyToMany
```

The field shows what it holds — a thumbnail and a name each — and opens the library page as a picker (`?picker=1&multiple=&field=<id>`), loaded by the loader in a modal or, `target: panel`, a panel. A media opened, or the selection chosen, goes back to the field (`media-library:choose`, on the document, naming the field), and the page closes the way a form closes once sent (`embed:close`). What it posts is the ids, comma separated; `Form\DataTransformer\MediaPickerTransformer` turns them back into the media, a collection for several. The field joins the application's form theme by itself.

symfony-media-demo shows both: the library, and an article picking its cover and its attachments.

## Table of Contents

- [The library page](#the-library-page)
- [Picking media in a form](#picking-media-in-a-form)
- [Integration in the Suite](#integration-in-the-suite)
- [Dependencies](#dependencies)
- [Versioning & Compatibility Policy](#versioning--compatibility-policy)
- [License](#license)
- [About us](#about-us)
- [Migration Notes](#migration-notes)

## Integration in the Suite

This package is part of the Wexample Suite — a collection of high-quality, modular tools designed to work seamlessly together across multiple languages and environments.

### Related Packages

The suite includes packages for configuration management, file handling, prompts, and more. Each package can be used independently or as part of the integrated suite.

Visit the [Wexample Suite documentation](https://docs.wexample.com) for the complete package ecosystem.

## Dependencies

- php: >=8.5
- symfony/form: ^7.4 || ^8.0
- wexample/symfony-design-system: >=35.0.0
- wexample/symfony-helpers: >=16.0.0
- wexample/symfony-loader: >=23.0.0
- wexample/symfony-media: >=2.0.0

## Versioning & Compatibility Policy

Wexample packages follow **Semantic Versioning** (SemVer):

- **MAJOR**: Breaking changes
- **MINOR**: New features, backward compatible
- **PATCH**: Bug fixes, backward compatible

We maintain backward compatibility within major versions and provide clear migration guides for breaking changes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Free to use in both personal and commercial projects.

## About us

[Wexample](https://wexample.com) stands as a cornerstone of the digital ecosystem — a collective of seasoned engineers, researchers, and creators driven by a relentless pursuit of technological excellence. More than a media platform, it has grown into a vibrant community where innovation meets craftsmanship, and where every line of code reflects a commitment to clarity, durability, and shared intelligence.

This packages suite embodies this spirit. Trusted by professionals and enthusiasts alike, it delivers a consistent, high-quality foundation for modern development — open, elegant, and battle-tested. Its reputation is built on years of collaboration, refinement, and rigorous attention to detail, making it a natural choice for those who demand both robustness and beauty in their tools.

Wexample cultivates a culture of mastery. Each package, each contribution carries the mark of a community that values precision, ethics, and innovation — a community proud to shape the future of digital craftsmanship.

## Migration Notes

When upgrading between major versions, refer to the migration guides in the documentation.

Breaking changes are clearly documented with upgrade paths and examples.
