<?php

declare(strict_types=1);

namespace Brille24\SyliusLdapPlugin\User;

use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\User\Model\UserInterface as SyliusUserInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class UserSynchronizer implements UserSynchronizerInterface
{
    public function __construct(private PropertyAccessorInterface $propertyAccessor)
    {
    }

    public function synchroniseUsers(SyliusUserInterface $sourceUser, SyliusUserInterface $targetUser): void
    {
        $attributesToSync = [
            'email',
            'expiresAt',
            'lastLogin',
            'enabled',
            'verifiedAt',
            'emailCanonical',
            'username',
            'usernameCanonical',
            'credentialsExpireAt',
        ];

        if ($targetUser instanceof AdminUserInterface && $sourceUser instanceof AdminUserInterface) {
            $attributesToSync[] = 'lastName';
            $attributesToSync[] = 'firstName';
        }

        foreach ($attributesToSync as $attributeToSync) {
            /** @psalm-suppress MixedAssignment */
            $value = $this->propertyAccessor->getValue($sourceUser, $attributeToSync);
            $this->propertyAccessor->setValue($targetUser, $attributeToSync, $value);
        }
    }
}
