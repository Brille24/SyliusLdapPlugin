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
    * @param string $memberUid The memberUid property in the LDAP. Usually the username of the user
    * @return array<string>
    */
    public function fetchGroups(string $memberUid, string $prefix = ''): array
    {
        /** @var array<Entry> $entries */
        $entries = $this->ldap
            ->query($this->dn, 'memberUid='.$memberUid)
            ->execute()
            ->toArray()
        ;

        // Extract group names from ldap structure
        return array_reduce(
            $entries,
            function (array $accumulator, Entry $entry) use ($prefix): array {
                Assert::isArray($entry->getAttribute('cn'));
                $groupName = $entry->getAttribute('cn')[0];

                if ($this->stripPrefix && strpos($groupName, $prefix) === 0) {
                    $accumulator[] = substr($groupName, strlen($prefix));
                }

                return $accumulator;
            },
            []
        );
    }
}
