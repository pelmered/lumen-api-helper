<?php

namespace pelmered\APIHelper\Traits;

trait APIModel
{
    public function scopeProcessSorting($query, $sorting)
    {
        if (empty($sorting))
        {
            $query->orderBy('id', 'desc');
        }
        else
        {
            foreach($sorting AS $sort)
            {
                if(isset($sort['orderBy']))
                {
                    if(!isset($sort['order']) || !in_array($sort['order'], ['asc', 'desc']))
                    {
                        $sort['order'] = 'asc';
                    }

                    $query->orderBy($sort['orderBy'], $sort['order']);
                }
            }
        }
        return $query;
    }

    public function scopeProcessFilters($query, $filters)
    {

        if (empty($filters))
        {
            return $query;
        }
        foreach($filters as $filter)
        {
            if (strpos($filter['field'], '.'))
            {
                $fields = explode('.', $filter['field']);

                $query->whereHas($fields[0], function ($query) use($fields, $filter) {
                    $query->where($fields[0].'.'.$fields[1], $filter['operator'], $filter['value']);
                });
            }
            else
            {
                if( is_array( $filter['value'] ) )
                {
                    $query->whereHas($filter['field'], function ($query) use ($filter) {
                        foreach($filter['value'] as $key => $value)
                        {
                            $query->where($key, 'like', '%'.$value.'%');
                        }
                    })->get();
                }
                else
                {
                    $query->where($filter['field'], $filter['operator'], $filter['value']);
                }
            }
        }

        return $query;
    }
}
