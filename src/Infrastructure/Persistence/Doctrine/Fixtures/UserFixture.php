<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Application\PasswordHasherInterface;
use App\Domain\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class UserFixture extends Fixture
{
    public const DIALOG_ADMIN_USER = 'dialog_admin_user';

    public function __construct(
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User(
            '424f4b29-2ca8-4236-9913-0de3e6a1bc90',
            'DiaLog',
            'admin@dialog.beta.gouv.fr',
            $this->passwordHasher->hash('4dm!nD!4l0g'),
        );


        $manager->persist($user);
        $manager->flush();

        $this->addReference(self::DIALOG_ADMIN_USER, $user);
    }
}
