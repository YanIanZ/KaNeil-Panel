<?php

namespace App\Enums;

enum CustomRenderHooks: string
{
    case FooterStart = 'kaneil::footer.start';
    case FooterEnd = 'kaneil::footer.end';
}
