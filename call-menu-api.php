<?php

require_once 'vendor/autoload.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

$valid = $invalid = [];

$menuTree = collectTree();
$menuTree = arrangeChildren($menuTree);

foreach ($menuTree as $menuItem) {
    if (in_array($menuItem['id'], $menuItem['child_ids'])) {
        $invalid[] = $menuItem;
    } else {
        $valid[] = $menuItem;
    }
}

dump($valid);
dump($invalid);

function collectTree()
{
    $menuTree = [];
    $page = 1;

    while (($content = fetchContent($page)) && \count($content['menus']) > 0) {
        foreach ((array)$content['menus'] as $menu) {
            $menuTree[$menu['id']] = $menu;

            if (parentExists($menu['parent_id'], $menuTree)) {
                $children = $menuTree[$menu['parent_id']]['child_ids'];
                $children = cleanMerge($children, [$menu['id']]);

                $menuTree[$menu['parent_id']]['child_ids'] = $children;
            }
        }
        $page++;
    }

    return $menuTree;
}

function fetchContent($page)
{
    $baseUrl = "https://backend-challenge-summer-2018.herokuapp.com/challenges.json?id=2";
    $ch = \curl_init();

    \curl_setopt($ch, CURLOPT_URL, $baseUrl . '&page=' . $page);
    \curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $return = \curl_exec($ch);
    \curl_close($ch);

    return json_decode($return, true);
}

function arrangeChildren(array $menuTree): array
{
    foreach ($menuTree as $id => $menuItem) {
        $newChildIds = [];

        foreach ((array)$menuItem['child_ids'] as $child_id) {
            $newChildIds = cleanMerge($newChildIds, $menuTree[$child_id]['child_ids']);
        }

        $menuTree[$id]['child_ids'] = cleanMerge($menuItem['child_ids'], $newChildIds);

        if (parentExists($menuItem['parent_id'], $menuTree)) {
            pushUp($menuTree, $menuItem['parent_id'], $menuItem['child_ids']);
        }
    }

    return $menuTree;
}

function pushUp(&$tree, $parent, $children)
{
    $menuItem = $tree[$parent];
    $tree[$parent]['child_ids'] = cleanMerge($children, $menuItem['child_ids']);

    if (parentExists($menuItem['parent_id'], $tree)) {
        pushUp($tree, $menuItem['parent_id'], $children);
    }
}

function cleanMerge($arr1, $arr2)
{
    return \array_unique(\array_merge($arr1, $arr2));
}

function parentExists($parent, $tree)
{
    return $parent !== null && array_key_exists($parent, $tree);
}
