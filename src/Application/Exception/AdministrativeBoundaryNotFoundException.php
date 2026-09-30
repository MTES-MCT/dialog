<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class AdministrativeBoundaryNotFoundException extends \Exception
{
    public function __construct(
        private string $codeType,
        private string $boundaryCode,
    ) {
        parent::__construct(\sprintf('No administrative boundary found for code "%s" of type "%s"', $boundaryCode, $codeType));
    }

    public function getCodeType(): string
    {
        return $this->codeType;
    }

    public function getBoundaryCode(): string
    {
        return $this->boundaryCode;
    }
}
