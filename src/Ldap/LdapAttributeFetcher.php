<?php

declare(strict_types=1);

namespace Brille24\SyliusLdapPlugin\Ldap;

use DateTime;
use DateTimeInterface;
use Symfony\Component\Ldap\Adapter\QueryInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;
use Webmozart\Assert\Assert;

final class LdapAttributeFetcher implements LdapAttributeFetcherInterface
{
    /** @var LdapInterface */
    private $ldap;

    /** @var array<string, string> */
    private $attributeMapping;

    /** @var string */
    private $dn;

    /**
     * @param LdapInterface         $ldap
     * @param array<string, string> $attributeMapping
     * @param string                $dn
     */
    public function __construct(
        LdapInterface $ldap,
        array $attributeMapping = [],
        string $dn = 'ou=users,dc=example,dc=com'
    ) {
        $this->ldap = $ldap;
        $this->attributeMapping = $attributeMapping;
        $this->dn = $dn;
    }

    public function fetchAttributesForUser(SymfonyUserInterface $user): array
    {
        /** @psalm-suppress DeprecatedMethod */
        $query = sprintf('uid=%s', $user->getUsername());
        $search = $this->ldap->query($this->dn, $query);
        /** @psalm-suppress PossiblyInvalidMethodCall */
        $entries = $search->execute()->toArray();

        /** @var array<string, mixed> $userAttributes */
        $userAttributes = [
            'email' => null,
            'locked' => false,
            'username' => null,
            'expires_at' => null,
            'last_login' => null,
            'verified_at' => null,
            'email_canonical' => null,
            'username_canonical' => null,
            'credentials_expire_at' => null,
            'first_name' => null,
            'last_name' => null,
            'locale_code' => null,
        ];

        if (count($entries) >= 1) {
            $entry = $entries[0];

            foreach ($this->attributeMapping as $userKey => $ldapKey) {
                Assert::keyExists($userAttributes, $userKey, sprintf("Unknown key '%s'!", $userKey));

                if ($entry->hasAttribute($ldapKey)) {
                    /** @var array<string> $value */
                    $value = array_values($entry->getAttribute($ldapKey) ?? []);

                    $userAttributes[$userKey] = $value[0];
                }
            }
        }

        return $userAttributes;
    }

    public function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return $value === '1';
    }

    public function toDateTime($value): ?DateTimeInterface
    {
        if ($value === null) {
            return null;
        }

        /** @psalm-suppress MixedArgument */
        $dateTime = DateTime::createFromFormat(DATE_ATOM, $value);
        if ($dateTime === false) {
            return null;
        }

        return $dateTime;
    }
}
