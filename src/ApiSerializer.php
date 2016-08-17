<?php

namespace pelmered\APIHelper;

use League\Fractal\Serializer\ArraySerializer;

class ApiSerializer extends ArraySerializer
{
    public function collection($resourceKey, array $data)
    {
        if ($resourceKey === false) {
            return $data;
        }

        if ($resourceKey === null) {
            return ['data' => $data];
        }

        return [$resourceKey => $data];
    }

    public function item($resourceKey, array $data)
    {
        if ($resourceKey === false) {
            return $data;
        }
        if ($resourceKey === null) {
            return ['data' => $data];
        }

        return [$resourceKey => $data];
    }
}
