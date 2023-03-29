<?php

namespace App\Application\Organization\Command;

use App\Application\CommandInterface;
use App\Domain\User\Organization;

class SaveOrganizationCommand implements CommandInterface
{
    public ?string $name;
    public ?Organization $organization=null;
}