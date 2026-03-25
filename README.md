# Asset Trash for Craft CMS 5

English | **[日本語](README-ja.md)** | **[Deutsch](README-de.md)**

A Craft CMS 5 plugin that provides a trash/recycle bin for deleted assets. When an asset is deleted, the file is copied to a `.trash/` directory within the volume, and a database record is kept so it can be restored or permanently deleted later from the control panel.

## Requirements

- Craft CMS 5.0.0 or later
- PHP 8.2 or later

## Installation

### Via Composer (recommended)

```bash
composer require bit-part/craft-asset-trash
```

Then install the plugin from the Craft control panel under **Settings > Plugins**, or via the CLI:

```bash
php craft plugin/install asset-trash
```

### Manual Installation

1. Download the release from [GitHub](https://github.com/bit-part/craft-asset-trash)
2. Place the contents in a directory and add a [path repository](https://getcomposer.org/doc/05-repositories.md#path) to your project's `composer.json`
3. Run `composer require bit-part/craft-asset-trash`
4. Install via the control panel or CLI

## How It Works

When you delete an asset in Craft:

1. The plugin intercepts the deletion event
2. The file is **copied** to a `.trash/` directory inside the same volume (e.g., `uploads/.trash/`)
3. A database record is created with metadata (filename, size, volume, path, who deleted it, and element references at the time of deletion)
4. Craft's normal deletion proceeds (the original asset element is removed)

The trashed file remains in `.trash/` until you restore or permanently delete it.

## Features

### Trash Listing

A dedicated **Asset Trash** section appears in the control panel's primary navigation. The listing shows:

- Filename (links to detail view)
- File size
- Who deleted it
- When it was deleted
- Number of element references at the time of deletion

### Volume Filtering

If you have multiple volumes, a dropdown filter lets you view trash items from a specific volume or all volumes at once.

### Detail View

Click a filename to see full metadata:

- Filename, kind, and file size
- Volume name and original path (volume base path + filename)
- Title and alt text (if set)
- Deleted-by user and deletion date
- Original asset element ID
- Internal trash path
- A table of element references at the time of deletion (source element ID, type, and field ID)

### Restore

Restore a trashed item to its original volume and folder. The plugin creates a new asset element and moves the file back from `.trash/` to the original location. If a file with the same name already exists, a unique suffix is added automatically.

### Permanent Delete

Remove a trashed item permanently. This deletes the file from `.trash/` and removes the database record. This action cannot be undone.

### Bulk Actions

Select multiple items using the checkboxes, then use **Restore Selected** or **Delete Selected** to act on them all at once.

### Empty Trash

The **Empty Trash** button permanently deletes all items in the trash (or all items in the currently filtered volume). A confirmation dialog is shown before proceeding.

### Auto-Purge

Expired items can be automatically purged during Craft's garbage collection. Configure the retention period and enable/disable auto-purge in the plugin settings.

## Settings

Navigate to **Settings > Plugins > Asset Trash** to configure:

| Setting | Default | Description |
|---------|---------|-------------|
| **Retention Days** | `30` | Number of days to keep trashed items before auto-purge. Set to `0` to keep items indefinitely. |
| **Auto Purge** | `On` | When enabled, expired items are automatically deleted during Craft's garbage collection. |
| **Trash Directory Name** | `.trash` | The directory name within each volume where trashed files are stored. Must contain only letters, numbers, dots, hyphens, and underscores. |

## Permissions

The plugin registers four permissions under **Asset Trash**:

| Permission | Description |
|------------|-------------|
| **View trash** | Access the Asset Trash section in the control panel |
| **Restore assets** | Restore trashed items back to their original location |
| **Permanently delete assets** | Permanently delete individual trashed items |
| **Empty trash** | Use the "Empty Trash" button to delete all items at once |

Permissions are nested: the three action permissions require the "View trash" permission to be granted first.

## Translations

The plugin includes translations for:

- English (`en`)
- Japanese (`ja`)

## Support

- [GitHub Issues](https://github.com/bit-part/craft-asset-trash/issues)
- [Documentation](https://github.com/bit-part/craft-asset-trash)

## License

This plugin is licensed under the [MIT License](LICENSE.md).

---

Built by [bit part LLC](https://bit-part.net)
