<?php

namespace App\Enums;

enum PermitApplicationType: string
{
    case MinePermitOnly = 'mine_permit_only';
    case MinePermitSimper = 'mine_permit_simper';

    public function requiresExam(): bool
    {
        return $this === self::MinePermitSimper;
    }
}
