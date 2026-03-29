<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Standard;

enum CheckType: string
{
    case Script = 'script';
    case Prompt = 'prompt';
    case Phpstan = 'phpstan';
    case Manual = 'manual';
}
