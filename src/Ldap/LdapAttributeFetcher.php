<?php

declare(strict_types=1);

namespace Brille24\SyliusLdapPlugin\Ldap;

use DateTime;
use DateTimeInterface;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;
use Webmozart\Assert\Assert;

final class LdapAttributeFetcher implements LdapAttributeFetcherInterface
{
    /**
     * @param LdapInterface         $ldap
     * @param array<string, string> $attributeMapping
     * @param string                $dn
     */
    public function __construct(
        private LdapInterface $ldap,
        private array $attributeMapping = [],
        private string $dn = 'ou=users,dc=example,dc=com'
    ) {
    }

    public function fetchAttributesForUser(SymfonyUserInterface $user): array
    {
        $query = sprintf('uid=%s', $user->getUserIdentifier());
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
        Assert::string($value);

        $dateTime = DateTime::createFromFormat(DATE_ATOM, $value);
        if ($dateTime === false) {
            return null;
        }

        return $dateTime;
    }
}
