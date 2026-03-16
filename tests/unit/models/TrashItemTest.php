<?php

namespace bitpart\assettrash\tests\unit\models;

use bitpart\assettrash\models\TrashItem;
use PHPUnit\Framework\TestCase;

class TrashItemTest extends TestCase
{
    public function testGetReferencesReturnsEmptyArrayWhenNull(): void
    {
        $item = new TrashItem();
        $item->referencesSnapshot = null;

        $this->assertSame([], $item->getReferences());
    }

    public function testGetReferencesDecodesValidJson(): void
    {
        $refs = [
            ['fieldId' => 1, 'sourceId' => 10, 'sourceType' => 'craft\\elements\\Entry'],
            ['fieldId' => 2, 'sourceId' => 20, 'sourceType' => 'craft\\elements\\Entry'],
        ];
        $item = new TrashItem();
        $item->referencesSnapshot = json_encode($refs);

        $this->assertSame($refs, $item->getReferences());
    }

    public function testGetReferencesReturnsEmptyArrayForInvalidJson(): void
    {
        $item = new TrashItem();
        $item->referencesSnapshot = 'not valid json';

        $this->assertSame([], $item->getReferences());
    }

    public function testGetReferenceCountReturnsZeroWhenNull(): void
    {
        $item = new TrashItem();
        $item->referencesSnapshot = null;

        $this->assertSame(0, $item->getReferenceCount());
    }

    public function testGetReferenceCountReturnsCorrectCount(): void
    {
        $refs = [
            ['fieldId' => 1, 'sourceId' => 10, 'sourceType' => 'craft\\elements\\Entry'],
            ['fieldId' => 2, 'sourceId' => 20, 'sourceType' => 'craft\\elements\\Entry'],
            ['fieldId' => 3, 'sourceId' => 30, 'sourceType' => 'craft\\elements\\Entry'],
        ];
        $item = new TrashItem();
        $item->referencesSnapshot = json_encode($refs);

        $this->assertSame(3, $item->getReferenceCount());
    }

    public function testGetReferencesReturnsEmptyArrayForEmptyJsonArray(): void
    {
        $item = new TrashItem();
        $item->referencesSnapshot = '[]';

        $this->assertSame([], $item->getReferences());
        $this->assertSame(0, $item->getReferenceCount());
    }
}
