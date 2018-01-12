<?php declare(strict_types=1);

namespace App;

/**
 * @package App
 */
class MenuValidator
{
    /**
     * @var EntryCollector
     */
    private $entryCollector;

    /**
     * MenuValidator constructor.
     */
    public function __construct()
    {
        $this->entryCollector = new EntryCollector();
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function validateMenu(string $url): string
    {
        $entryTree = $this->entryCollector->collect($url);

        $valid = $invalid = [];

        foreach ($entryTree->getChildren() as $item) {
            if ($item->isValid()) {
                $valid[] = $item->toArray();
            } else {
                $invalid[] = $item->toArray();
            }
        }

        return json_encode([
            'valid_menus' => $valid,
            'invalid_menus' => $invalid
        ]);
    }
}
