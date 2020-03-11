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

use Sylius\Bundle\UserBundle\Provider\UserProviderInterface as SyliusUserProviderInterface;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface as SymfonyUserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;
use Sylius\Component\User\Model\UserInterface as SyliusUserInterface;
use Sylius\Component\Core\Model\AdminUser;
use Doctrine\Common\Persistence\ObjectRepository;
use Webmozart\Assert\Assert;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Adapter\QueryInterface;
use Sylius\Component\Core\Model\AdminUserInterface;

final class SymfonyToSyliusUserProviderProxy implements SyliusUserProviderInterface
{

    /**
     * @var SymfonyUserProviderInterface
     */
    private $innerUserProvider;

    /**
     * @var array<string, string>
     */
    private $attributeMapping;

    /**
     * @var ObjectRepository<AdminUser>
     */
    private $userRepository;

    /**
     * @var LdapInterface
     */
    private $ldap;

    /**
     * @var string
     */
    private $dn;

    /**
     * @var array<string, string>
     */
    public function __construct(
        SymfonyUserProviderInterface $innerUserProvider,
        ObjectRepository $userRepository,
        LdapInterface $ldap,
        array $attributeMapping = array(),
        string $dn = "ou=users,dc=example,dc=com"
    ) {
        Assert::eq(AdminUser::class, $userRepository->getClassName());

        $this->innerUserProvider = $innerUserProvider;
        $this->attributeMapping = $attributeMapping;
        $this->userRepository = $userRepository;
        $this->ldap = $ldap;
        $this->dn = $dn;
    }

    public function loadUserByUsername($username)
    {
        /** @var SyliusUserInterface|null $syliusUser */
        $syliusUser = $this->userRepository->findOneBy(['username' => $username]);

        /** @var SymfonyUserInterface|null $symfonyLdapUser */
        $symfonyLdapUser = $this->innerUserProvider->loadUserByUsername($username);

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

    public function refreshUser(SymfonyUserInterface $user)
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

    public function supportsClass($class)
    {
        return $this->innerUserProvider->supportsClass($class);
    }

    private function convertSymfonyToSyliusUser(SymfonyUserInterface $symfonyUser): SyliusUserInterface
    {
        /** @var array<string, string> $ldapAttributes */
        $ldapAttributes = $this->fetchLdapAttributesForUser($symfonyUser);

        $syliusUser = new AdminUser();
        $syliusUser->setUsername($symfonyUser->getUsername());
        $syliusUser->setEmail($ldapAttributes['email']);
        $syliusUser->setLocked($ldapAttributes['locked']);
#        $syliusUser->setExpiresAt($ldapAttributes['expires_at']);
        $syliusUser->setEnabled(!$ldapAttributes['locked']);
        $syliusUser->setLastLogin($ldapAttributes['last_login']);
        $syliusUser->setVerifiedAt($ldapAttributes['verified_at']);
        $syliusUser->setEmailCanonical($ldapAttributes['email_canonical']);
        $syliusUser->setUsernameCanonical($ldapAttributes['username_canonical']);
        $syliusUser->setCredentialsExpireAt($ldapAttributes['credentials_expire_at']);

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
    ) {
        $targetUser->setEmail($sourceUser->getEmail());
        $targetUser->setLocked(false);
#        $targetUser->setExpiresAt($sourceUser->getExpiresAt());
        $targetUser->setEnabled($sourceUser->isEnabled());
        $targetUser->setLastLogin($sourceUser->getLastLogin());
        $targetUser->setVerifiedAt($sourceUser->getVerifiedAt());
        $targetUser->setEmailCanonical($sourceUser->getEmailCanonical());
        $targetUser->setUsernameCanonical($sourceUser->getUsernameCanonical());
        $targetUser->setCredentialsExpireAt($sourceUser->getCredentialsExpireAt());

        if ($targetUser instanceof AdminUserInterface && $sourceUser instanceof AdminUserInterface) {
            $targetUser->setLastName($sourceUser->getLastName());
            $targetUser->setFirstName($sourceUser->getFirstName());
            $targetUser->setLocaleCode($sourceUser->getLocaleCode());
        }
    }

    private function fetchLdapAttributesForUser(SymfonyUserInterface $user): array
    {
        /** @var string $query */
        $query = sprintf("uid=%s", $user->getUsername());

        /** @var QueryInterface $search */
        $search = $this->ldap->query($this->dn, $query);

        /** @var iterable<Entry> $entries */
        $entries = $search->execute();

        /** @var array<string, string> $userAttributes */
        $userAttributes = array(
            'email' => null,
            'locked' => false,
            'username' => null,
            'expires_at' => null,
            'last_login' => null,
            'verified_at' => null,
            'email_canonical' => null,
            'username_canonical' => null,
            'credentials_expire_at' => null,
            'last_name' => null,
            'first_name' => null,
            'locale_code' => null,
        );

        if (count($entries) >= 1) {
            /** @var Entry $entry */
            $entry = $entries[0];

            foreach ($this->attributeMapping as $userKey => $ldapKey) {
                Assert::keyExists($userAttributes, $userKey, sprintf("Unknown key '%s'!", $userKey));

                if ($entry->hasAttribute($ldapKey)) {
                    /** @var array<string> $value */
                    $value = array_values($entry->getAttribute($ldapKey));

                    $userAttributes[$userKey] = (string)$value[0];
                }
            }
        }

        return $userAttributes;
    }
}
