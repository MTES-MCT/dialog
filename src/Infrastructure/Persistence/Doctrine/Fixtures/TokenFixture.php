<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\User\Enum\TokenTypeEnum;
use App\Domain\User\Token;
use App\Domain\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class TokenFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $confirmAccountToken = new Token(
            '7b9ed5b3-cb2e-4f77-88ef-5371a98de677',
            'confirmAccountToken',
            TokenTypeEnum::CONFIRM_ACCOUNT->value,
            $this->getReference('otherOrgUser', User::class),
            new \DateTimeImmutable('2025-08-26 09:00:00'),
        );

        $expiredConfirmAccountToken = new Token(
            '217993a8-af4c-40ad-8afc-75d0e9415865',
            'expiredConfirmAccountToken',
            TokenTypeEnum::CONFIRM_ACCOUNT->value,
            $this->getReference('otherOrgUser', User::class),
            new \DateTimeImmutable('2023-01-01 19:00:00'),
        );

        $forgotPasswordToken = new Token(
            '8edb17c6-9346-4e64-8a0d-bf1e4762802c',
            'forgotPasswordToken',
            TokenTypeEnum::FORGOT_PASSWORD->value,
            $this->getReference('department93User', User::class),
            new \DateTimeImmutable('2025-08-26 09:00:00'),
        );

        $expiredForgotPasswordToken = new Token(
            '57b8d6cc-1b7b-4204-8a38-da30359a936e',
            'expiredForgotPasswordToken',
            TokenTypeEnum::FORGOT_PASSWORD->value,
            $this->getReference('department93User', User::class),
            new \DateTimeImmutable('2023-01-01 19:00:00'),
        );

        $manager->persist($forgotPasswordToken);
        $manager->persist($expiredForgotPasswordToken);
        $manager->persist($confirmAccountToken);
        $manager->persist($expiredConfirmAccountToken);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}
