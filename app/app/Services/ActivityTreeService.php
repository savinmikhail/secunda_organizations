<?php

namespace App\Services;

use App\Models\Activity;

class ActivityTreeService
{
    /**
     * Collect the given activity id and all its descendant ids.
     *
     * @return int[]
     */
    public function descendantIds(int $activityId): array
    {
        $all = [$activityId];
        $frontier = [$activityId];

        while ($frontier !== []) {
            $children = Activity::whereIn('parent_id', $frontier)->pluck('id')->all();
            $children = array_values(array: array_diff($children, $all));
            if ($children === []) {
                break;
            }
            $all = array_merge($all, $children);
            $frontier = $children;
        }

        return $all;
    }
}
