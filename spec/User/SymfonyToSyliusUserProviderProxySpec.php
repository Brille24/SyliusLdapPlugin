<?php

namespace spec\Brille24\SyliusLdapPlugin\User;

use Brille24\SyliusLdapPlugin\Ldap\LdapAttributeFetcherInterface;
use Brille24\SyliusLdapPlugin\User\SymfonyToSyliusUserProviderProxy;
use Brille24\SyliusLdapPlugin\User\UserSynchronizerInterface;
use DateTime;
use Prophecy\Argument;
use Sylius\Bundle\UserBundle\Provider\AbstractUserProvider;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\User\Model\UserInterface as SyliusUserInterface;
use Sylius\Bundle\UserBundle\Provider\UserProviderInterface as SyliusUserProviderInterface;
use Sylius\Component\User\Model\UserInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use PhpSpec\ObjectBehavior;

class SymfonyToSyliusUserProviderProxySpec extends ObjectBehavior
{
    function let(
        UserProviderInterface $innerUserProvider,
        AbstractUserProvider $adminUserProvider,
        LdapAttributeFetcherInterface $attributeFetcher,
        FactoryInterface $adminUserFactory,
        UserSynchronizerInterface $userSynchronizer
    ) {
        $this->beConstructedWith($innerUserProvider, $adminUserProvider, $attributeFetcher, $adminUserFactory, $userSynchronizer);
    }

    function it_is_a_sylius_user_provider()
    {
        $this->shouldHaveType(SymfonyToSyliusUserProviderProxy::class);
        $this->shouldImplement(SyliusUserProviderInterface::class);
    }

    function it_loads_the_ldap_user_and_creates_a_sylius_user(
        AbstractUserProvider $adminUserProvider,
        UserProviderInterface $innerUserProvider,
        LdapAttributeFetcherInterface $attributeFetcher,
        FactoryInterface $adminUserFactory,
        AdminUserInterface $adminUser,
        UserInterface $ldapUser
    ) {
        $adminUserProvider->loadUserByUsername('test')->willThrow(UserNotFoundException::class);

        $innerUserProvider->loadUserByIdentifier('test')->willReturn($ldapUser);
        $adminUserFactory->createNew()->shouldBeCalled()->willReturn($adminUser);

        $ldapUser->getUserIdentifier()->willReturn('test');

        $attributeFetcher->fetchAttributesForUser($ldapUser)->willReturn([
            'email'                 => 'sylius@sylius.de',
            'locked'                => false,
            'username'              => 'admin',
            'expires_at'            => null,
            'last_login'            => null,
            'verified_at'           => null,
            'email_canonical'       => 'sylius@sylius.de',
            'username_canonical'    => 'admin',
            'credentials_expire_at' => null,
            'first_name'            => 'Adam',
            'last_name'             => 'Ministrator',
            'locale_code'           => null,
        ])
        ;
        $attributeFetcher->toBool(Argument::any())->willReturn(false);
        $attributeFetcher->toDateTime(Argument::any())->willReturn(new DateTime());

        $this->loadUserByUsername('test')->shouldReturn($adminUser);
//        $adminUser->shouldHaveType(AdminUser::class);
//        $adminUser->getEmail()->shouldReturn('sylius@sylius.de');
//        $adminUser->getFirstName()->shouldReturn('Adam');
//        $adminUser->getLastName()->shouldReturn('Ministrator');
//        $adminUser->getUsernameCanonical()->shouldReturn('admin');
    }

    function it_updates_the_sylius_user_with_data_from_ldap(
        AbstractUserProvider $adminUserProvider,
        UserProviderInterface $innerUserProvider,
        LdapAttributeFetcherInterface $attributeFetcher,
        FactoryInterface $adminUserFactory,
        AdminUserInterface $adminUser,
        SyliusUserInterface $syliusUser,
        UserInterface $ldapUser,
        UserSynchronizerInterface $userSynchronizer
    ) {
        $adminUserProvider->loadUserByUsername('test')->willReturn($syliusUser);
        $innerUserProvider->loadUserByIdentifier('test')->willReturn($ldapUser);
        $adminUserFactory->createNew()->shouldBeCalled()->willReturn($adminUser);

        $ldapUser->getUserIdentifier()->willReturn('test');

        $attributeFetcher->fetchAttributesForUser($ldapUser)->willReturn([
            'email'                 => 'sylius@sylius.de',
            'locked'                => false,
            'username'              => 'admin',
            'expires_at'            => null,
            'last_login'            => null,
            'verified_at'           => null,
            'email_canonical'       => 'sylius@sylius.de',
            'username_canonical'    => 'admin',
            'credentials_expire_at' => null,
            'first_name'            => 'Adam',
            'last_name'             => 'Ministrator',
            'locale_code'           => null,
        ])
        ;
        $attributeFetcher->toBool(Argument::any())->willReturn(false);
        $attributeFetcher->toDateTime(Argument::any())->willReturn(new DateTime());

        $userSynchronizer->synchroniseUsers($adminUser, $syliusUser)->shouldBeCalled();

        $this->loadUserByUsername('test')->shouldReturn($syliusUser);
    }
}
