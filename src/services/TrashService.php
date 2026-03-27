<?php

namespace bitpart\assettrash\services;

use bitpart\assettrash\AssetTrash;
use bitpart\assettrash\models\TrashItem;
use bitpart\assettrash\records\TrashItemRecord;
use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\Volume;
use DateTime;
use Throwable;
use yii\base\Component;

class TrashService extends Component
{
    private const MAX_FILENAME_ATTEMPTS = 1000;

    /**
     * Move an asset's file to the trash directory and record metadata.
     */
    public function trashAsset(Asset $asset): bool
    {
        $volume = $asset->getVolume();
        $settings = AssetTrash::getInstance()->getSettings();
        $uid = StringHelper::UUID();
        $trashPath = $this->generateTrashPath($settings->trashDirName, $uid, $asset->getFilename());
        $sourcePath = $asset->getPath();

        try {
            $volume->copyFile($sourcePath, $trashPath);
        } catch (Throwable $e) {
            Craft::error(
                "Asset Trash: Failed to copy file '{$sourcePath}' to '{$trashPath}': " . $e->getMessage(),
                'asset-trash'
            );
            return false;
        }

        $referencesSnapshot = $this->captureReferences($asset->id);
        $currentUser = Craft::$app->getUser()->getIdentity();

        $record = new TrashItemRecord();
        $record->uid = $uid;
        $record->assetId = $asset->id;
        $record->volumeId = $volume->id;
        $record->folderId = $asset->folderId;
        $record->folderPath = $asset->folderPath ?? '';
        $record->filename = $asset->getFilename();
        $record->kind = $asset->kind;
        $record->size = $asset->size;
        $record->trashPath = $trashPath;
        $record->title = $asset->title;
        $record->alt = $asset->alt ?? null;
        $json = !empty($referencesSnapshot) ? json_encode($referencesSnapshot) : null;
        $record->referencesSnapshot = ($json !== false) ? $json : null;
        $record->deletedByUserId = $currentUser?->id;
        $record->dateDeleted = Db::prepareDateForDb(new DateTime());

        if (!$record->save()) {
            Craft::error(
                'Asset Trash: Failed to save trash record: ' . json_encode($record->getErrors()),
                'asset-trash'
            );
            // Clean up the copied file
            try {
                $volume->deleteFile($trashPath);
            } catch (Throwable $e) {
                Craft::warning(
                    "Asset Trash: Failed to clean up orphaned trash file '{$trashPath}': " . $e->getMessage(),
                    'asset-trash'
                );
            }
            return false;
        }

        Craft::info(
            "Asset Trash: Trashed asset '{$asset->getFilename()}' (ID: {$asset->id}) to '{$trashPath}'",
            'asset-trash'
        );

        return true;
    }

    /**
     * Capture references (relations) pointing to this asset.
     */
    public function captureReferences(int $assetId): array
    {
        return (new Query())
            ->select(['r.fieldId', 'r.sourceId', 'e.type AS sourceType'])
            ->from(['r' => Table::RELATIONS])
            ->innerJoin(['e' => Table::ELEMENTS], '[[e.id]] = [[r.sourceId]]')
            ->where(['r.targetId' => $assetId])
            ->andWhere(['e.dateDeleted' => null])
            ->all();
    }

    /**
     * Generate a trash path within the trash directory.
     */
    public function generateTrashPath(string $trashDirName, string $uid, string $filename): string
    {
        $safeFilename = basename($filename);
        return $trashDirName . '/' . $uid . '_' . $safeFilename;
    }

    /**
     * Get all trash items with optional volume filter and pagination.
     *
     * @return TrashItem[]
     */
    public function getTrashItems(?int $volumeId = null, int $offset = 0, int $limit = 50): array
    {
        $query = TrashItemRecord::find()
            ->orderBy(['dateDeleted' => SORT_DESC]);

        if ($volumeId !== null) {
            $query->andWhere(['volumeId' => $volumeId]);
        }

        $query->offset($offset)->limit($limit);

        $records = $query->all();
        return array_map(fn($record) => $this->recordToModel($record), $records);
    }

    /**
     * Get total count of trash items.
     */
    public function getTotalTrashItems(?int $volumeId = null): int
    {
        $query = TrashItemRecord::find();

        if ($volumeId !== null) {
            $query->andWhere(['volumeId' => $volumeId]);
        }

        return (int)$query->count();
    }

    /**
     * Get a single trash item by ID.
     */
    public function getTrashItemById(int $id): ?TrashItem
    {
        $record = TrashItemRecord::findOne($id);
        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * Restore a trashed item back to its original volume/folder.
     */
    public function restoreItem(int $trashItemId): bool
    {
        $item = $this->getTrashItemById($trashItemId);
        if ($item === null) {
            return false;
        }

        $volume = Craft::$app->getVolumes()->getVolumeById($item->volumeId);
        if ($volume === null) {
            Craft::error(
                "Asset Trash: Cannot restore - volume ID {$item->volumeId} no longer exists.",
                'asset-trash'
            );
            return false;
        }

        // Determine target folder
        $targetFolder = $this->resolveTargetFolder($volume, $item->folderId, $item->folderPath);
        if ($targetFolder === null) {
            Craft::error(
                'Asset Trash: Cannot resolve target folder for restoration.',
                'asset-trash'
            );
            return false;
        }

        // Resolve filename conflicts
        $folderPath = $targetFolder->path ?? '';
        $filename = $this->resolveFilenameConflict($volume, $folderPath, $item->filename);

        // Copy file from trash back to the original location
        $restorePath = $folderPath . $filename;
        try {
            $volume->copyFile($item->trashPath, $restorePath);
        } catch (Throwable $e) {
            Craft::error(
                "Asset Trash: Failed to copy file from trash: " . $e->getMessage(),
                'asset-trash'
            );
            return false;
        }

        // Create new Asset element
        $asset = new Asset();
        $asset->setVolumeId($volume->id);
        $asset->folderId = $targetFolder->id;
        $asset->folderPath = $targetFolder->path ?? '';
        $asset->setFilename($filename);
        $asset->kind = $item->kind;
        $asset->size = $item->size;
        $asset->title = $item->title ?? AssetsHelper::filename2Title($filename);
        $asset->alt = $item->alt;
        $asset->dateModified = new \DateTime();
        $asset->uploaderId = Craft::$app->getUser()->getId();
        $asset->setScenario(Asset::SCENARIO_INDEX);

        if (!Craft::$app->getElements()->saveElement($asset)) {
            Craft::error(
                'Asset Trash: Failed to save restored asset: ' . json_encode($asset->getErrors()),
                'asset-trash'
            );
            // Clean up the copied file
            try {
                $volume->deleteFile($restorePath);
            } catch (Throwable $e) {
                Craft::warning(
                    "Asset Trash: Failed to clean up restored file '{$restorePath}': " . $e->getMessage(),
                    'asset-trash'
                );
            }
            return false;
        }

        // Clean up trash file and record
        try {
            $volume->deleteFile($item->trashPath);
        } catch (Throwable $e) {
            Craft::warning(
                "Asset Trash: Failed to delete trash file '{$item->trashPath}' after restore: " . $e->getMessage(),
                'asset-trash'
            );
        }

        TrashItemRecord::deleteAll(['id' => $item->id]);

        Craft::info(
            "Asset Trash: Restored '{$filename}' to volume '{$volume->name}' (new Asset ID: {$asset->id})",
            'asset-trash'
        );

        return true;
    }

    /**
     * Permanently delete a trash item.
     */
    public function permanentlyDelete(int $trashItemId): bool
    {
        $record = TrashItemRecord::findOne($trashItemId);
        if ($record === null) {
            return false;
        }

        $filename = $record->filename;
        $result = $this->deleteTrashRecord($record);

        if ($result) {
            Craft::info(
                "Asset Trash: Permanently deleted '{$filename}' from trash.",
                'asset-trash'
            );
        }

        return $result;
    }

    /**
     * Empty all items from the trash.
     */
    public function emptyTrash(?int $volumeId = null): int
    {
        $query = TrashItemRecord::find();
        if ($volumeId !== null) {
            $query->andWhere(['volumeId' => $volumeId]);
        }

        $count = 0;

        foreach ($query->batch(100) as $records) {
            foreach ($records as $record) {
                if ($this->deleteTrashRecord($record)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Purge trash items older than the retention period.
     */
    public function purgeExpired(): int
    {
        $settings = AssetTrash::getInstance()->getSettings();

        if ($settings->retentionDays <= 0) {
            return 0;
        }

        $cutoff = (new DateTime())->modify("-{$settings->retentionDays} days");

        $query = TrashItemRecord::find()
            ->andWhere(['<', 'dateDeleted', Db::prepareDateForDb($cutoff)]);

        $count = 0;
        foreach ($query->batch(100) as $records) {
            foreach ($records as $record) {
                if ($this->deleteTrashRecord($record)) {
                    $count++;
                }
            }
        }

        if ($count > 0) {
            Craft::info("Asset Trash: Purged {$count} expired item(s).", 'asset-trash');
        }

        return $count;
    }

    /**
     * Delete a trash record and its associated file directly from a record.
     * Avoids the extra DB query that permanentlyDelete() would incur.
     */
    private function deleteTrashRecord(TrashItemRecord $record): bool
    {
        $volume = Craft::$app->getVolumes()->getVolumeById($record->volumeId);
        if ($volume !== null) {
            try {
                $volume->deleteFile($record->trashPath);
            } catch (Throwable $e) {
                Craft::warning(
                    "Asset Trash: Could not delete trash file '{$record->trashPath}': " . $e->getMessage(),
                    'asset-trash'
                );
            }
        }

        return $record->delete() !== false;
    }

    /**
     * Resolve the target folder for restoration.
     */
    private function resolveTargetFolder(Volume $volume, ?int $folderId, string $folderPath): ?\craft\models\VolumeFolder
    {
        $assets = Craft::$app->getAssets();

        // Try original folder first
        if ($folderId !== null) {
            $folder = $assets->getFolderById($folderId);
            if ($folder !== null) {
                return $folder;
            }
        }

        // Try to find by path
        if ($folderPath !== '') {
            $folder = $assets->findFolder([
                'volumeId' => $volume->id,
                'path' => $folderPath,
            ]);
            if ($folder !== null) {
                return $folder;
            }
        }

        // Fall back to volume root folder
        return $assets->getRootFolderByVolumeId($volume->id);
    }

    /**
     * Resolve filename conflicts by appending a suffix.
     */
    private function resolveFilenameConflict(Volume $volume, string $folderPath, string $filename): string
    {
        if (!$volume->fileExists($folderPath . $filename)) {
            return $filename;
        }

        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $counter = 1;
        $maxAttempts = self::MAX_FILENAME_ATTEMPTS;

        do {
            $newFilename = $baseName . '_' . $counter . ($extension ? '.' . $extension : '');
            $counter++;

            if ($counter > $maxAttempts) {
                // Fall back to UUID-based filename to guarantee uniqueness
                $newFilename = $baseName . '_' . StringHelper::UUID() . ($extension ? '.' . $extension : '');
                break;
            }
        } while ($volume->fileExists($folderPath . $newFilename));

        return $newFilename;
    }

    /**
     * Convert a record to a model.
     */
    private function recordToModel(TrashItemRecord $record): TrashItem
    {
        $model = new TrashItem();
        $model->id = $record->id;
        $model->uid = $record->uid;
        $model->assetId = $record->assetId;
        $model->volumeId = $record->volumeId;
        $model->folderId = $record->folderId;
        $model->folderPath = $record->folderPath;
        $model->filename = $record->filename;
        $model->kind = $record->kind;
        $model->size = $record->size;
        $model->trashPath = $record->trashPath;
        $model->title = $record->title;
        $model->alt = $record->alt;
        $model->referencesSnapshot = $record->referencesSnapshot;
        $model->deletedByUserId = $record->deletedByUserId;
        $model->dateDeleted = $record->dateDeleted ? DateTimeHelper::toDateTime($record->dateDeleted) : null;
        $model->dateCreated = $record->dateCreated ? DateTimeHelper::toDateTime($record->dateCreated) : null;
        $model->dateUpdated = $record->dateUpdated ? DateTimeHelper::toDateTime($record->dateUpdated) : null;

        return $model;
    }
}
