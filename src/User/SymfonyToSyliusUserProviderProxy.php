<?php

declare(strict_types=1);
/**
 * Copyright (C) 2019 Brille24 GmbH.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit.addiks@brille24.de>
 */

namespace Brille24\SyliusLdapPlugin\User;

use Brille24\SyliusLdapPlugin\Ldap\LdapAttributeFetcherInterface;
use Sylius\Bundle\UserBundle\Provider\AbstractUserProvider;
use Sylius\Bundle\UserBundle\Provider\UserProviderInterface as SyliusUserProviderInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\User\Model\UserInterface as SyliusUserInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface as SymfonyUserProviderInterface;
use Webmozart\Assert\Assert;

final class SymfonyToSyliusUserProviderProxy implements SyliusUserProviderInterface
{
    /**
     * @var SymfonyUserProviderInterface
     */
    private $ldapUserProvider;

    /**
     * @var AbstractUserProvider
     */
    private $adminUserProvider;

    /**
     * @var LdapAttributeFetcherInterface
     */
    private $attributeFetcher;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var FactoryInterface
     */
    private $adminUserFactory;

    public function __construct(
        SymfonyUserProviderInterface $ldapUserProvider,
        AbstractUserProvider $adminUserProvider,
        PropertyAccessorInterface $propertyAccessor,
        LdapAttributeFetcherInterface $attributeFetcher,
        FactoryInterface $adminUserFactory
    ) {
        $this->ldapUserProvider = $ldapUserProvider;
        $this->adminUserProvider = $adminUserProvider;
        $this->attributeFetcher = $attributeFetcher;
        $this->propertyAccessor = $propertyAccessor;
        $this->adminUserFactory = $adminUserFactory;
    }

    public function loadUserByUsername($username): SymfonyUserInterface
    {
        /** @var SymfonyUserInterface $symfonyLdapUser */
        $symfonyLdapUser = $this->ldapUserProvider->loadUserByUsername($username);
        $syliusLdapUser = $this->convertSymfonyToSyliusUser($symfonyLdapUser);

        try {
            /** @var SyliusUserInterface $syliusUser */
            $syliusUser = $this->adminUserProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $notFoundException) {
            return $syliusLdapUser;
        }

        $this->synchroniseUsers($syliusLdapUser, $syliusUser);

        return $syliusUser;
    }

    public function refreshUser(SymfonyUserInterface $user): SymfonyUserInterface
    {
        /** @var SymfonyUserInterface $symfonyLdapUser */
        $symfonyLdapUser = $this->ldapUserProvider->refreshUser($user);

        /** @var SyliusUserInterface $syliusLdapUser */
        $syliusLdapUser = $this->convertSymfonyToSyliusUser($symfonyLdapUser);

        // Non-sylius-users (e.g.: symfony-users) are immutable and cannot be updated / synced.
        Assert::isInstanceOf($user, SyliusUserInterface::class);

        $this->synchroniseUsers($syliusLdapUser, $user);

        return $user;
    }

    public function supportsClass($class): bool
    {
        return $this->ldapUserProvider->supportsClass($class);
    }

    private function convertSymfonyToSyliusUser(SymfonyUserInterface $symfonyUser): SyliusUserInterface
    {
        /** @var array<string, string> $ldapAttributes */
        $ldapAttributes = $this->attributeFetcher->fetchAttributesForUser($symfonyUser);

        $locked = $this->attributeFetcher->toBool($ldapAttributes['locked']);
        /** @var AdminUserInterface $syliusUser */
        $syliusUser = $this->adminUserFactory->createNew();
        $syliusUser->setUsername($symfonyUser->getUsername());
        $syliusUser->setEmail($ldapAttributes['email']);
        $syliusUser->setPassword('');
        $syliusUser->setLocked($locked);
        $syliusUser->setEnabled(!$locked);
//        $syliusUser->setExpiresAt($ldapAttributes['expires_at']);
        $syliusUser->setLastLogin($this->attributeFetcher->toDateTime($ldapAttributes['last_login']));
        $syliusUser->setVerifiedAt($this->attributeFetcher->toDateTime($ldapAttributes['verified_at']));
        $syliusUser->setEmailCanonical($ldapAttributes['email_canonical']);
        $syliusUser->setUsernameCanonical($ldapAttributes['username_canonical']);
        $syliusUser->setCredentialsExpireAt($this->attributeFetcher->toDateTime($ldapAttributes['credentials_expire_at']));

        if ($syliusUser instanceof AdminUserInterface) {
            $syliusUser->setLastName($ldapAttributes['last_name']);
            $syliusUser->setFirstName($ldapAttributes['first_name']);
            $syliusUser->setLocaleCode($ldapAttributes['locale_code'] ?? 'en_US');
        }

        return $syliusUser;
    }

    private function synchroniseUsers(
        SyliusUserInterface $sourceUser,
        SyliusUserInterface $targetUser
    ): void {
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
            $attributesToSync[] = 'localeCode';
        }

        foreach ($attributesToSync as $attributeToSync) {
            $value = $this->propertyAccessor->getValue($sourceUser, $attributeToSync);
            $this->propertyAccessor->setValue($targetUser, $attributeToSync, $value);
        }
    }
}
