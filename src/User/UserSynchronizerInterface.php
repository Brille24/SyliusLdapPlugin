<?php
declare(strict_types=1);

namespace Brille24\SyliusLdapPlugin\User;

use Sylius\Component\User\Model\UserInterface as SyliusUserInterface;

interface UserSynchronizerInterface
{
    public function synchroniseUsers(SyliusUserInterface $sourceUser, SyliusUserInterface $targetUser): void;
}
