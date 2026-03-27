# Changelog

## 1.0.1 - 2026-03-27

### Fixed
- Restored assets now have the restoring user set as "Uploaded By" (was empty)
- Restored assets now have the restoration time set as "File Modified Date" (was empty)
- Asset Trash nav item now appears directly after Assets in the CP navigation
- Fixed spacing between volume filter / bulk action buttons and the trash table

### Changed
- Removed preview column from trash listing (not useful without actual thumbnails)
- Removed Original Location column from trash listing (folder path alone lacked context)
- Detail view now shows full path including volume base path (e.g. `uploads/image.jpg`)
- Removed badge count from nav item (cached count was often stale)

## 1.0.0 - 2026-03-26

### Added
- Initial release
- Asset deletion interception with file backup to `.trash/` directory
- CP section with trash item listing and volume filtering
- Restore trashed assets to original or fallback location
- Filename conflict resolution on restore
- Permanent deletion of individual or bulk trash items
- Empty trash functionality
- References snapshot at time of deletion
- Automatic purge of expired items via Craft GC
- Configurable retention period, auto-purge, and trash directory name
- User permissions: view, restore, permanently delete, empty trash
- English and Japanese translations
