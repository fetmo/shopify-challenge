<?php

require_once 'vendor/autoload.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

$menuValidator = new MenuValidator();
$result = $menuValidator->validateMenu('https://backend-challenge-summer-2018.herokuapp.com/challenges.json?id=2');

dump($result);

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
     * @param $url
     *
     * @return string
     */
    public function validateMenu($url): string
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
            'valid_menus'   => $valid,
            'invalid_menus' => $invalid
        ]);
    }

}

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
     * @param $url
     * @param $page
     *
     * @return array
     */
    private function fetchContent($url, $page): array
    {
        $ch = \curl_init();

        \curl_setopt($ch, CURLOPT_URL, $url . '&page=' . $page);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $return = \curl_exec($ch);
        \curl_close($ch);

        return (array)json_decode($return, true);
    }
}

class EntryRepository
{
    /**
     * @var Entry[]
     */
    private $entries = [];

    /**
     * @param $data
     *
     * @return Entry
     */
    public function createEntry($data)
    {
        $id = $data['id'];

        $entry = $this->findEntry($id);

        if ($entry === null) {
            $entry = new Entry($data['id'], $data['data'], $data['child_ids'], $this);
            $this->entries[] = $entry;
        }

        return $entry;
    }

    /**
     * @param $id
     *
     * @return null|Entry
     */
    public function findEntry($id)
    {
        $entry = array_filter($this->entries, function (Entry $cacheEntry) use ($id) {
            return $cacheEntry->get('id') === $id;
        });

        return array_values($entry)[0];
    }
}

class Entry
{
    private $id;
    private $children;
    private $data;
    private $parent;

    private $parentid;
    private $childids;

    public function __construct($id, $data, $childids, EntryRepository $entryRepo)
    {
        $this->id = $id;
        $this->data = $data;

        $this->childids = (array)$childids;
        $this->children = new EntryTree($entryRepo);
    }

    /**
     * @param array|null $parentIds
     *
     * @return bool
     */
    public function isValid(array $parentIds = null): bool
    {
        $tooDeep = $this->getDepth() > 4;
        $childIsInParentIds = \is_array($parentIds) ? (bool)count(array_intersect($parentIds, $this->childids)) : false;

        $parentIds[] = $this->id;

        $childrenValid = true;
        foreach ($this->children->getChildren() as $child) {
            $childrenValid &= $child->isValid($parentIds);
        }

        return !$tooDeep && !$childIsInParentIds && $childrenValid;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'root_id'  => $this->id,
            'children' => $this->getChildIds()
        ];
    }

    /**
     * @return array
     */
    public function getChildIds(): array
    {
        $childids = $this->childids;

        foreach ($this->children->getChildren() as $child) {
            $childids = array_merge($childids, $child->getChildIds());
        }

        return $childids;
    }

    /**
     * @param Entry $parent
     */
    public function setParent(Entry $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @param $parentID
     */
    public function setParentID($parentID)
    {
        $this->parentid = $parentID;
    }

    /**
     * @param Entry $child
     */
    public function addChild(Entry $child)
    {
        $this->children->addEntry($child);
        $child->setParent($this);
    }

    /**
     * @return EntryTree
     */
    public function getChildren(): EntryTree
    {
        return $this->children;
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->children->getDepth() + 1;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function get($name)
    {
        return $this->$name;
    }

    /**
     * @param $name
     *
     * @return null
     */
    public function unset($name)
    {
        return $this->$name = null;
    }

}

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
     * @param null  $parentid
     */
    public function addEntry(Entry $entry, $parentid = null)
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
     * @param $id
     *
     * @return Entry
     */
    public function findEntry($id): Entry
    {
        $entry = $this->entryRepo->createEntry([
            'id'        => 0,
            'data'      => '',
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

        if (count($this->items) !== 0) {
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
            $parentFound = $parentFound = false;

            if ($parentId === $entry->get('id') || in_array($item->get('id'), $entry->get('childids'), true)) {
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