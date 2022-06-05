<?php

use App\Models\Account;
use App\Models\Environment;

if (! function_exists('custom_paginate')) {
    /**
     * Customize paginated response
     *
     * @param $query
     * @param int $perPage
     * @return mixed
     */
    function custom_paginate($query, int $perPage = 25): mixed
    {
        if (request()->input('pagination') === 0) {
            return $query->get();
        }
        $perPage = (int) request()->input('per_page', ($perPage ?? 25));

        return $query->paginate($perPage)->appends(request()->except(['page']));
    }
}
