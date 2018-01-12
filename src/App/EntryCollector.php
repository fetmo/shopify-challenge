<?php declare(strict_types=1);

namespace App;

/**
 * @package App
 */
class EntryCollector
{
    /**
     * @var EntryRepository
     */
    private $entryRepository;

    /**
     * EntryCollector constructor.
     */
    public function __construct()
    {
        $this->entryRepository = new EntryRepository();
    }

    /**
     * @param string $baseUrl
     *
     * @return EntryTree
     */
    public function collect(string $baseUrl): EntryTree
    {
        $page = 1;
        $entryTree = new EntryTree($this->entryRepository);

        while (($content = $this->fetchContent($baseUrl, $page)) && \count($content['menus']) > 0) {
            foreach ((array)$content['menus'] as $menu) {

                $entry = $this->entryRepository->createEntry($menu);
                if ($menu['parent_id']) {
                    $entryTree->addEntry($entry, $menu['parent_id']);
                } else {
                    $entryTree->addEntry($entry);
                }
            }

            $page++;
        }

        return $entryTree;
    }

    /**
     * @param string $url
     * @param int $page
     *
     * @return array
     */
    private function fetchContent(string $url, int $page): array
    {
        $ch = \curl_init();

        \curl_setopt($ch, CURLOPT_URL, $url . '&page=' . $page);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $return = \curl_exec($ch);
        \curl_close($ch);

        return (array)json_decode($return, true);
    }
}
