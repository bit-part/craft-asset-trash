# Asset Trash Plugin - Browser Test Report

**Date**: 2026-03-10
**Environment**: Craft CMS 5.9.15 / PHP 8.4 / DDEV v1.25.0 / MySQL 8.0
**URL**: https://craft-plugin-dev.ddev.site
**Tester**: Claude Code (automated via Chrome DevTools)

---

## Test Results Summary

| # | Test | Result | Screenshot |
|---|------|--------|------------|
| 1 | CP Navigation & Empty Trash View | PASS | `01_empty_trash.png` |
| 2 | Asset Deletion -> Trash Intercept | PASS | `02_trash_with_item.png` |
| 3 | Trash Item Detail Page | PASS | `03_detail_page.png` |
| 4 | Single Item Restore | PASS | `04_after_restore.png`, `05_assets_restored.png` |
| 5 | Single Item Permanent Delete | PASS | `06_after_permanent_delete.png` |
| 6 | Bulk Asset Deletion (2 items) | PASS | `07_trash_multiple_items.png` |
| 7 | Empty Trash (全削除) | PASS | `08_after_empty_trash.png` |
| 8 | Settings Page | PASS | `09_settings_page.png` |
| 9 | GC Auto-Purge (CLI) | PASS | - |

---

## Test Details

### Test 1: CP Navigation & Empty Trash View
- **Steps**: Login -> Click "Asset Trash" in primary navigation
- **Expected**: Page title "Asset Trash", "The trash is empty." message
- **Result**: PASS
- **Verified**:
  - Navigation item displayed correctly
  - Empty state message shown
  - No errors or broken layout

### Test 2: Asset Deletion -> Trash Intercept
- **Steps**: Assets page -> Select "Test document" -> Actions -> Delete -> Confirm
- **Expected**: Asset removed from Assets list, file copied to `.trash/`, DB record created
- **Result**: PASS
- **Verified**:
  - Asset count decreased from 3 to 2
  - DB record created in `assettrash_items` table with correct metadata:
    - `filename`: test-document.txt
    - `trashPath`: .trash/{uuid}_test-document.txt
    - `volumeId`: 1
    - `deletedByUserId`: 1 (tinybeans)
    - `dateDeleted`: 2026-03-10 07:34:19
  - Physical file exists at `/web/uploads/.trash/{uuid}_test-document.txt` (47 bytes)

### Test 3: Trash Item Detail Page
- **Steps**: Asset Trash page -> Click filename link
- **Expected**: Detail page with all metadata fields
- **Result**: PASS
- **Verified fields**:
  - Filename: test-document.txt
  - Kind: text
  - Size: 47 B
  - Volume: Uploads
  - Original Path: /test-document.txt
  - Title: Test document
  - Deleted By: tinybeans
  - Date Deleted: Mar 10, 2026, 12:34:19 AM
  - Original Asset ID: 5
  - Trash Path: .trash/{uuid}_test-document.txt
  - Restore / Permanently Delete buttons present
  - "Back to Trash" link present

### Test 4: Single Item Restore
- **Steps**: Asset Trash page -> Click "Restore" button for test-document.txt
- **Expected**: File restored to original location, new Asset element created, trash record removed
- **Result**: PASS
- **Verified**:
  - Trash page shows "The trash is empty."
  - Assets page shows 3 assets again (test-document.txt restored)
  - Physical file restored to `/web/uploads/test-document.txt` (47 bytes)
  - `.trash/` directory is empty
  - DB record removed from `assettrash_items`
  - Badge count cleared from navigation

### Test 5: Single Item Permanent Delete
- **Steps**: Delete asset again -> Asset Trash page -> Click "Delete" button -> Confirm
- **Expected**: File and DB record permanently removed
- **Result**: PASS
- **Verified**:
  - Trash page shows "The trash is empty."
  - DB: `SELECT COUNT(*) FROM assettrash_items` = 0
  - `.trash/` directory is empty
  - Original file `/web/uploads/test-document.txt` no longer exists

### Test 6: Bulk Asset Deletion (2 items)
- **Steps**: Assets page -> Select All (2 remaining) -> Actions -> Delete -> Confirm
- **Expected**: Both assets moved to trash
- **Result**: PASS
- **Verified**:
  - Both `test.txt` and `test-image.png` appear in trash
  - DB has 2 records in `assettrash_items`
  - Badge shows "2 notifications" in navigation
  - Trash table displays correct metadata for both items

### Test 7: Empty Trash (全削除)
- **Steps**: Asset Trash page -> Click "Empty Trash" -> Confirm
- **Expected**: All trash items permanently deleted
- **Result**: PASS
- **Verified**:
  - Trash page shows "The trash is empty."
  - DB: `SELECT COUNT(*) FROM assettrash_items` = 0
  - `.trash/` directory is empty (no files)
  - "Empty Trash" button no longer visible

### Test 8: Settings Page
- **Steps**: Settings -> Plugins -> Asset Trash
- **Expected**: Settings form with all configurable options
- **Result**: PASS
- **Verified fields**:
  - Retention Days: 30 (spinbutton, with description)
  - Auto Purge: ON (switch toggle, with description)
  - Trash Directory Name: .trash (textbox, with description)
  - Save button present

### Test 9: GC Auto-Purge (CLI)
- **Steps**: `ddev craft gc` (garbage collection command)
- **Expected**: No errors, purge runs without issues
- **Result**: PASS
- **Verified**:
  - GC completed successfully with no errors
  - No trash items purged (all items within retention period)
  - Plugin's GC event handler executed without exceptions

---

## Badge Count Behavior
- Badge count correctly shows item count (1, 2, etc.) when trash has items
- Badge disappears when trash is empty
- Note: 60-second cache on badge count means there can be a brief delay after changes

## Accessibility Observations
- All table headers have `scope="col"` attribute
- Checkboxes have `role="checkbox"` and `aria-checked` attributes
- Individual checkboxes have `aria-label` set to the filename
- "Select all" checkbox toggles all items
- Keyboard navigation works (tabindex on checkboxes)
- Hidden labels use `visually-hidden` class for screen readers

## Screenshots
All screenshots are saved in `tests/screenshots/` directory:
- `01_empty_trash.png` - Initial empty trash view
- `02_trash_with_item.png` - Trash with one deleted item
- `03_detail_page.png` - Item detail page
- `04_after_restore.png` - Trash after restore (empty)
- `05_assets_restored.png` - Assets page showing restored file
- `06_after_permanent_delete.png` - Trash after permanent delete (empty)
- `07_trash_multiple_items.png` - Trash with 2 items
- `08_after_empty_trash.png` - Trash after Empty Trash action (empty)
- `09_settings_page.png` - Plugin settings page
