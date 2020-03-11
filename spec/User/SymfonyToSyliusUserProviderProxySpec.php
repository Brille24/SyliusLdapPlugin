<?php

namespace spec\Brille24\SyliusLdapPlugin\User;

use Brille24\SyliusLdapPlugin\User\SymfonyToSyliusUserProviderProxy;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SymfonyToSyliusUserProviderProxySpec extends ObjectBehavior
{
    function let(
        UserProviderInterface $innerUserProvider,
        ObjectRepository $userRepository,
        LdapInterface $ldap
    ) {
        $this->beConstructedWith($innerUserProvider, $userRepository, $ldap, [], '');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(SymfonyToSyliusUserProviderProxy::class);
    }

    public function it_loads_a_user_from_the_sylius_repository(
        ObjectRepository $userRepository
    ): void {
        $userRepository->findOneBy(['username' => 'test'])->willReturn($user);
    }
}
