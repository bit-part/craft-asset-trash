<?php

namespace bitpart\assettrash\records;

use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property string $uid
 * @property int|null $assetId
 * @property int $volumeId
 * @property int|null $folderId
 * @property string $folderPath
 * @property string $filename
 * @property string $kind
 * @property int|null $size
 * @property string $trashPath
 * @property string|null $title
 * @property string|null $alt
 * @property string|null $referencesSnapshot
 * @property int|null $deletedByUserId
 * @property string $dateDeleted
 * @property string $dateCreated
 * @property string $dateUpdated
 */
class TrashItemRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%assettrash_items}}';
    }
}
