<?php

declare(strict_types=1);

namespace App\Application\Organization\Logo\Command;

use App\Application\CommandInterface;
use App\Domain\User\Organization;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SaveOrganizationLogoCommand implements CommandInterface
{
    public ?UploadedFile $file;

    public function __construct(
        public readonly Organization $organization,
    ) {
    }
}
