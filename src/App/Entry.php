<?php #declare(strict_types=1);

namespace App;

/**
 * @package App
 */
class Entry
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var EntryTree
     */
    private $children;

    /**
     * @var string
     */
    private $data;

    /**
     * @var Entry
     */
    private $parent;

    /**
     * @var int
     */
    private $parentid;

    /**
     * @var array
     */
    private $childids;

    /**
     * Entry constructor.
     *
     * @param int             $id
     * @param string          $data
     * @param array           $childids
     * @param EntryRepository $entryRepo,
     */
    public function __construct(int $id, string $data, array $childids, EntryRepository $entryRepo)
    {
        $this->id = $id;
        $this->data = $data;

        $this->childids = $childids;
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
        $childIsInParentIds = \is_array($parentIds) ? (bool)\count(array_intersect($parentIds, $this->childids)) : false;

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
