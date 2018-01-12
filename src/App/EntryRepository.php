<?php declare(strict_types=1);

namespace App;

/**
 * @package App
 */
class EntryRepository
{
    /**
     * @var Entry[]
     */
    private $entries = [];

    /**
     * @param array $data
     *
     * @return Entry
     */
    public function createEntry(array $data): Entry
    {
        $id = $data['id'];

        $entry = $this->findEntry($id);

        if ($entry === null) {
            $entry = new Entry((int)$data['id'], (string)$data['data'], (array)$data['child_ids'], $this);
            $this->entries[] = $entry;
        }

        return $entry;
    }

    /**
     * @param int $id
     *
     * @return null|Entry
     */
    public function findEntry(int $id)
    {
        $entry = array_values(
            array_filter($this->entries, function (Entry $cacheEntry) use ($id) {
            return $cacheEntry->get('id') === $id;
        }));

        return array_shift($entry);
    }


}