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
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\Core\Model\AdminUserInterface;
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
    private $innerUserProvider;

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

    public function __construct(
        SymfonyUserProviderInterface $innerUserProvider,
        AbstractUserProvider $adminUserProvider,
        PropertyAccessorInterface $propertyAccessor,
        LdapAttributeFetcherInterface $attributeFetcher
    ) {
        $this->innerUserProvider = $innerUserProvider;
        $this->adminUserProvider = $adminUserProvider;
        $this->attributeFetcher = $attributeFetcher;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function loadUserByUsername($username)
    {
        try {
            /** @var SyliusUserInterface $syliusUser */
            $syliusUser = $this->adminUserProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $notFoundException) {
            $syliusUser = null;
        }

        /** @var SymfonyUserInterface|null $symfonyLdapUser */
        try {
            $symfonyLdapUser = $this->innerUserProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $notFoundException) {
            if ($syliusUser === null) {
                throw new UsernameNotFoundException('User is not available in LDAP');
            }
            $symfonyLdapUser = null;
        }

        /** @var SyliusUserInterface $syliusLdapUser */
        $syliusLdapUser = null;

        if (is_object($symfonyLdapUser)) {
            $syliusLdapUser = $this->convertSymfonyToSyliusUser($symfonyLdapUser);
        }

        // If Sylius does not have any user, then just take the one converted from LDAP
        if (null === $syliusUser) {
            $syliusUser = $syliusLdapUser;

        // If both systems have the user, synchronise the Sylius user with the LDAP Info
        } elseif (is_object($syliusLdapUser)) {
            $this->synchroniseUsers($syliusLdapUser, $syliusUser);
        }

        return $syliusUser;
    }

    public function refreshUser(SymfonyUserInterface $user): SymfonyUserInterface
    {
        /** @var SymfonyUserInterface|null $symfonyLdapUser */
        $symfonyLdapUser = $this->innerUserProvider->refreshUser($user);

        /** @var SyliusUserInterface|null $syliusLdapUser */
        $syliusLdapUser = null;

        if (is_object($symfonyLdapUser)) {
            $syliusLdapUser = $this->convertSymfonyToSyliusUser($symfonyLdapUser);

            // Non-sylius-users (e.g.: symfony-users) are immutable and cannot be updated / synced.
            Assert::isInstanceOf($user, SyliusUserInterface::class);

            $this->synchroniseUsers($syliusLdapUser, $user);
        }

        return $user;
    }

    public function supportsClass($class): bool
    {
        return $this->innerUserProvider->supportsClass($class);
    }

    private function convertSymfonyToSyliusUser(SymfonyUserInterface $symfonyUser): SyliusUserInterface
    {
        /** @var array<string, string> $ldapAttributes */
        $ldapAttributes = $this->attributeFetcher->fetchAttributesForUser($symfonyUser);

        $locked = $this->attributeFetcher->toBool($ldapAttributes['locked']);
        $syliusUser = new AdminUser();
        $syliusUser->setUsername($symfonyUser->getUsername());
        $syliusUser->setEmail($ldapAttributes['email']);
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
            'locked',
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
