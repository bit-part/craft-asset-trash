<?php

namespace bitpart\assettrash\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $this->createTable('{{%assettrash_items}}', [
            'id' => $this->primaryKey(),
            'uid' => $this->uid(),
            'assetId' => $this->integer()->null(),
            'volumeId' => $this->integer()->notNull(),
            'folderId' => $this->integer()->null(),
            'folderPath' => $this->string()->notNull()->defaultValue(''),
            'filename' => $this->string()->notNull(),
            'kind' => $this->string(50)->notNull()->defaultValue('unknown'),
            'size' => $this->bigInteger()->unsigned()->null(),
            'trashPath' => $this->string()->notNull(),
            'title' => $this->string()->null(),
            'alt' => $this->text()->null(),
            'referencesSnapshot' => $this->text()->null(),
            'deletedByUserId' => $this->integer()->null(),
            'dateDeleted' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);

        $this->createIndex(null, '{{%assettrash_items}}', ['volumeId']);
        $this->createIndex(null, '{{%assettrash_items}}', ['dateDeleted']);

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%assettrash_items}}');

        return true;
    }
}
