<?php

namespace App;

enum DataScopeCode: string
{
    case Institution = 'institution';
    case Campus = 'campus';
    case College = 'college';
    case Department = 'department';
    case Program = 'program';
    case Section = 'section';
    case Self = 'self';

    public function label(): string
    {
        return trans('scopes.'.$this->value);
    }
}
