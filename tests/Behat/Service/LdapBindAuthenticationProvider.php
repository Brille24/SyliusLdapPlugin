<?php

declare(strict_types=1);

namespace Tests\Brille24\SyliusLdapPlugin\Behat\Service;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

class LdapBindAuthenticationProvider extends \Symfony\Component\Security\Core\Authentication\Provider\LdapBindAuthenticationProvider
{
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
    }
}
