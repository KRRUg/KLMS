<?php

namespace App\Service;

enum SettingType: string
{
    case String = 'string';
    case HTML = 'html';
    case URL = 'url';
    case File = 'file';
    case Bool = 'bool';
    case Integer = 'int';
    case Money = 'money';
}
