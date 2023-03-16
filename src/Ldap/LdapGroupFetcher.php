<?php

declare(strict_types=1);

namespace Brille24\SyliusLdapPlugin\Ldap;

use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\LdapInterface;
use Webmozart\Assert\Assert;

final class LdapGroupFetcher implements LdapGroupFetcherInterface
{
    public function __construct(
        private LdapInterface $ldap,
        private string $dn,
        private bool $stripPrefix = true
    ) {}

    /**
     * @psalm-suppress MixedReturnTypeCoercion
     *
     * @param string $memberUid The memberUid property in the LDAP. Usually the username of the user
     *
     * @return array<string>
    */
    public function fetchGroups(string $memberUid, string $prefix = ''): array
    {
        $entries = $this->ldap
            ->query($this->dn, 'memberUid='.$memberUid)
            ->execute()
            ->toArray()
        ;

        // Extract group names from ldap structure
        return array_reduce(
            $entries,
            function (array $accumulator, Entry $entry) use ($prefix): array {
                $attribute = $entry->getAttribute('cn');
                Assert::isArray($attribute);

                $groupName = $attribute[0];
                Assert::string($groupName);

                if ($this->stripPrefix && str_starts_with($groupName, $prefix)) {
                    $accumulator[] = substr($groupName, strlen($prefix));
                }

                return $accumulator;
            },
            []
        );
    }
}
