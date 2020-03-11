<?php
declare(strict_types=1);

namespace Brille24\SyliusLdapPlugin\Ldap;

use DateTimeInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

interface LdapAttributeFetcherInterface
{
    public function fetchAttributesForUser(SymfonyUserInterface $user): array;

    public function toBool($value): bool;

    public function toDateTime($value): ?DateTimeInterface;
}
