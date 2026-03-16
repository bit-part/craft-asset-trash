<?php

namespace bitpart\assettrash\models;

use craft\base\Model;
use DateTime;

class TrashItem extends Model
{
    public ?int $id = null;
    public ?string $uid = null;
    public ?int $assetId = null;
    public int $volumeId = 0;
    public ?int $folderId = null;
    public string $folderPath = '';
    public string $filename = '';
    public string $kind = 'unknown';
    public ?int $size = null;
    public string $trashPath = '';
    public ?string $title = null;
    public ?string $alt = null;
    public ?string $referencesSnapshot = null;
    public ?int $deletedByUserId = null;
    public ?DateTime $dateDeleted = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        return [
            [['volumeId', 'filename', 'trashPath'], 'required'],
            [['volumeId', 'assetId', 'folderId', 'size', 'deletedByUserId'], 'integer'],
            [['filename', 'trashPath', 'folderPath', 'kind'], 'string'],
        ];
    }

    /**
     * Returns decoded references snapshot.
     */
    public function getReferences(): array
    {
        if ($this->referencesSnapshot === null) {
            return [];
        }

        return json_decode($this->referencesSnapshot, true) ?: [];
    }

    /**
     * Returns the number of references.
     */
    public function getReferenceCount(): int
    {
        return count($this->getReferences());
    }
}
