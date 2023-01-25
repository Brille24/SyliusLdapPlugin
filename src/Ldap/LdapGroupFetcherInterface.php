<?php

namespace Brille24\SyliusLdapPlugin\Ldap;

interface LdapGroupFetcherInterface
{
    /**
     * Fetches a list of group names the user is a member of.
     *
     * @return array<string>
     */
    public function fetchGroups(string $memberUid, string $prefix = ''): array;
}
