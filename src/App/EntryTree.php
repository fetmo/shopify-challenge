<?php declare(strict_types=1);

namespace App;

/**
 * @package App
 */
class EntryTree
{
    /**
     * @var Entry[]
     */
    private $items = [];

    /**
     * @var Entry[]
     */
    private $notConnected = [];

    /**
     * @var EntryRepository
     */
    private $entryRepo;

    /**
     * EntryTree constructor.
     *
     * @param EntryRepository $entryRepository
     */
    public function __construct(EntryRepository $entryRepository)
    {
        $this->entryRepo = $entryRepository;
    }

    /**
     * @return Entry[]
     */
    public function getChildren(): array
    {
        return $this->items;
    }

    /**
     * @param Entry $entry
     * @param int|null $parentid
     */
    public function addEntry(Entry $entry, int $parentid = null)
    {
        if ($parentid !== null) {
            $parent = $this->findEntry($parentid);
            $entry->setParentID($parentid);

            if ($parent->get('id') !== 0) {
                $parent->addChild($entry);
                $this->arrangeNotConnected($entry);
            } else {
                $this->notConnected[] = $entry;
            }
        } else {
            $this->items[] = $entry;
        }
    }

    /**
     * @param int $id
     *
     * @return Entry
     */
    public function findEntry(int $id): Entry
    {
        $entry = $this->entryRepo->createEntry([
            'id' => 0,
            'data' => '',
            'child_ids' => [],
        ]);

        foreach ($this->items as $item) {
            if ($item->get('id') === $id) {
                $entry = $item;
            } else {
                $entry = $item->getChildren()->findEntry($id);
            }

            if ($entry->get('id') !== 0) {
                break;
            }
        }

        return $entry;
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        $deepth = 0;

        if (\count($this->items) !== 0) {
            $deepest = 0;

            foreach ($this->items as $item) {
                $itemDeep = $item->getDepth();

                $deepest = $deepest < $itemDeep ? $itemDeep : $deepest;
            }

            $deepth += $deepest;
        }

        return $deepth;
    }

    /**
     * @param Entry $entry
     */
    private function arrangeNotConnected(Entry $entry)
    {
        foreach ($this->notConnected as $index => $item) {
            $parentId = $item->get('parentid');
            $parentFound = $parentEntry = false;

            if ($parentId === $entry->get('id') || \in_array($item->get('id'), $entry->get('childids'), true)) {
                $parentEntry = $entry;
                $parentFound = true;
            }

            foreach ($this->items as $entryItem) {
                if (!$parentFound) {
                    $parentEntry = $entryItem->getChildren()->findEntry($parentId);
                    $parentFound = $parentEntry->get('id') !== 0;
                }
            }

            if ($parentFound) {
                $parentEntry->addChild($item);
                unset($this->notConnected[$index]);
                break;
            }
        }
    }

}