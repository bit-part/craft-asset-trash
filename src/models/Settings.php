<?php

namespace bitpart\assettrash\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    /**
     * @var int Number of days to retain trashed items before auto-purge.
     *          0 = keep indefinitely.
     */
    public int $retentionDays = 30;

    /**
     * @var bool Whether to automatically purge expired items during GC.
     */
    public bool $autoPurge = true;

    /**
     * @var string Directory name within each volume for trashed files.
     */
    public string $trashDirName = '.trash';

    public function defineRules(): array
    {
        return [
            [['retentionDays'], 'integer', 'min' => 0],
            [['autoPurge'], 'boolean'],
            [['trashDirName'], 'string'],
            [['trashDirName'], 'required'],
            [['trashDirName'], 'match', 'pattern' => '/^[a-zA-Z0-9._-]+$/',
                'message' => Craft::t('asset-trash', 'Trash directory name can only contain letters, numbers, dots, hyphens, and underscores.')],
        ];
    }
}
