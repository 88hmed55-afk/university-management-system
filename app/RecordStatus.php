<?php

namespace App;

enum RecordStatus: string
{
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return trans('values.status.'.$this->value);
    }
}
