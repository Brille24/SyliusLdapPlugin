<?php

declare(strict_types=1);

namespace Brille24\SyliusLdapPlugin\Ldap;

use DateTimeInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

interface LdapAttributeFetcherInterface
{
    public function fetchAttributesForUser(SymfonyUserInterface $user): array;

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function toBool($value): bool;

    /**
     * @param mixed $value
     *
     * @return DateTimeInterface|null
     */
    public function toDateTime($value): ?DateTimeInterface;
}
