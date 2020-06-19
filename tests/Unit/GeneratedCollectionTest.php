<?php declare(strict_types=1);

namespace ModelariumTests;

use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;

final class GeneratedCollectionTest extends TestCase
{
    public function testFilterByType()
    {
        $collection = new GeneratedCollection();
        $collection->push(
            new GeneratedItem(GeneratedItem::TYPE_EVENT, 'event', 'event.php')
        );
        $collection->push(
            new GeneratedItem(GeneratedItem::TYPE_MIGRATION, 'migration', 'migration.php')
        );
        $filtered = $collection->filterByType(GeneratedItem::TYPE_MIGRATION);
        $this->assertEquals(1, $filtered->count());
        $this->assertEquals(GeneratedItem::TYPE_MIGRATION, $filtered->first()->type);
    }
}
