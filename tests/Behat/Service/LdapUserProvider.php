<?php

declare(strict_types=1);

namespace Tests\Brille24\SyliusLdapPlugin\Behat\Service;

use Sylius\Bundle\UserBundle\Provider\UserProviderInterface;
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class LdapUserProvider implements UserProviderInterface
{
    private const EMAILS = ['sylius@example.com', 'ted@example.com', 'watermelon@example.com'];

    public function __construct(private UserRepositoryInterface $userRepository)
    {
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $user;
    }

    public function supportsClass($class): bool
    {
        return AdminUser::class === $class;
    }

    public function loadUserByUsername($username): UserInterface
    {
        $user = $this->userRepository->findOneBy(['username' => $username]);

        if (!$user instanceof AdminUserInterface) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $username)
            );
        }

        if (in_array($user->getEmail(), self::EMAILS)) {
            return $user;
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );
    }
}
