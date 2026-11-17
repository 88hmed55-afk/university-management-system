<?php

namespace App;

enum StudentStatus: string
{
    case Active = 'active';
    case Deferred = 'deferred';
    case Returned = 'returned';
    case Withdrawn = 'withdrawn';
    case Graduated = 'graduated';

    public function label(): string
    {
        return trans('values.student_status.'.$this->value);
    }
}
