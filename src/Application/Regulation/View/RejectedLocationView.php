<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final readonly class RejectedLocationView
{
    /**
     * @param array $location données de la localisation telles qu'envoyées
     * @param array $measure  données de la mesure parente telles qu'envoyées (sans ses localisations)
     * @param array $errors   liste de ['propertyPath' => string, 'title' => string]
     */
    public function __construct(
        public int $measureIndex,
        public int $locationIndex,
        public array $location,
        public array $measure,
        public array $errors,
        public ?string $regulationOrderIdentifier = null,
        public ?\DateTimeInterface $createdAt = null,
    ) {
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->regulationOrderIdentifier !== null) {
            $data['identifier'] = $this->regulationOrderIdentifier;
        }

        $data += [
            'measureIndex' => $this->measureIndex,
            'locationIndex' => $this->locationIndex,
            'errors' => $this->errors,
            'location' => $this->location,
            'measure' => $this->measure,
        ];

        if ($this->createdAt !== null) {
            $data['createdAt'] = $this->createdAt->format(\DateTimeInterface::ATOM);
        }

        return $data;
    }
}
