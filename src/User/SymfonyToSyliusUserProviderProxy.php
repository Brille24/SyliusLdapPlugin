<?php
/**
 * Copyright (C) 2019 Brille24 GmbH.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit.addiks@brille24.de>
 */

namespace Brille24\SyliusLdapPlugin\User;

use Brille24\SyliusLdapPlugin\Ldap\LdapAttributeFetcherInterface;
use Sylius\Bundle\UserBundle\Provider\UserProviderInterface as SyliusUserProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface as SymfonyUserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;
use Sylius\Component\User\Model\UserInterface as SyliusUserInterface;
use Sylius\Component\Core\Model\AdminUser;
use Doctrine\Common\Persistence\ObjectRepository;
use Webmozart\Assert\Assert;
use Sylius\Component\Core\Model\AdminUserInterface;

final class SymfonyToSyliusUserProviderProxy implements SyliusUserProviderInterface
{

    /**
     * @var SymfonyUserProviderInterface
     */
    private $innerUserProvider;

    /**
     * @var ObjectRepository<AdminUser>
     */
    private $userRepository;

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
        ObjectRepository $userRepository,
        PropertyAccessorInterface $propertyAccessor,
        LdapAttributeFetcherInterface $attributeFetcher
    ) {
        Assert::eq(AdminUser::class, $userRepository->getClassName());

        $this->innerUserProvider = $innerUserProvider;
        $this->userRepository = $userRepository;
        $this->attributeFetcher = $attributeFetcher;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function loadUserByUsername($username)
    {
        /** @var SyliusUserInterface|null $syliusUser */
        $syliusUser = $this->userRepository->findOneBy(['username' => $username]);

        /** @var SymfonyUserInterface|null $symfonyLdapUser */
        try {
            $symfonyLdapUser = $this->innerUserProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $notFoundException) {
            $symfonyLdapUser = null;
        }

        /** @var SyliusUserInterface $syliusLdapUser */
        $syliusLdapUser = null;

        if (is_object($symfonyLdapUser)) {
            $syliusLdapUser = $this->convertSymfonyToSyliusUser($symfonyLdapUser);
        }

        if (is_null($syliusUser)) {
            $syliusUser = $syliusLdapUser;

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

            # Non-sylius-users (e.g.: symfony-users) are immutable and cannot be updated / synced.
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
#        $syliusUser->setExpiresAt($ldapAttributes['expires_at']);
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
            'verifiedAt',
            'emailCanonical',
            'username',
            'usernameCanonical',
            'credentialsExpireAt'
        ];

        if ($targetUser instanceof AdminUserInterface && $sourceUser instanceof AdminUserInterface) {
            $attributeToSync[] = 'lastName';
            $attributeToSync[] = 'firstName';
            $attributeToSync[] = 'localeCode';
        }

        foreach($attributesToSync as $attributeToSync) {
            $value = $this->propertyAccessor->getValue($sourceUser, $attributeToSync);
            $this->propertyAccessor->setValue($targetUser, $attributeToSync, $value);
        }
    }
}
