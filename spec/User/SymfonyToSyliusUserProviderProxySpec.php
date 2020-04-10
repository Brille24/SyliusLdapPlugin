<?php

namespace spec\Brille24\SyliusLdapPlugin\User;

use Brille24\SyliusLdapPlugin\Ldap\LdapAttributeFetcherInterface;
use Brille24\SyliusLdapPlugin\User\SymfonyToSyliusUserProviderProxy;
use DateTime;
use PhpParser\Node\Arg;
use Prophecy\Argument;
use Sylius\Bundle\UserBundle\Provider\AbstractUserProvider;
use Sylius\Component\User\Model\UserInterface as SyliusUserInterface;
use Sylius\Bundle\UserBundle\Provider\UserProviderInterface as SyliusUserProviderInterface;
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\User\Model\UserInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use PhpSpec\ObjectBehavior;

class SymfonyToSyliusUserProviderProxySpec extends ObjectBehavior
{
    function let(
        UserProviderInterface $innerUserProvider,
        AbstractUserProvider $adminUserProvider,
        PropertyAccessorInterface $propertyAccessor,
        LdapAttributeFetcherInterface $attributeFetcher
    ) {
        $this->beConstructedWith($innerUserProvider, $adminUserProvider, $propertyAccessor, $attributeFetcher, [], '');
    }

    function it_is_a_sylius_user_provider()
    {
        $this->shouldHaveType(SymfonyToSyliusUserProviderProxy::class);
        $this->shouldImplement(SyliusUserProviderInterface::class);
    }

    function it_loads_a_user_from_the_sylius_repository(
        AbstractUserProvider $adminUserProvider,
        UserProviderInterface $innerUserProvider,
        LdapAttributeFetcherInterface $attributeFetcher,
        SyliusUserInterface $syliusUser
    ): void {
        $adminUserProvider->loadUserByUsername('test')->willReturn($syliusUser);
        $innerUserProvider->loadUserByUsername('test')->willThrow(UsernameNotFoundException::class);

        $attributeFetcher->fetchAttributesForUser(Argument::any())->shouldNotBeCalled();

        $this->loadUserByUsername('test')->shouldReturn($syliusUser);
    }

    function it_loads_the_ldap_user_and_creates_a_sylius_user(
        AbstractUserProvider $adminUserProvider,
        UserProviderInterface $innerUserProvider,
        LdapAttributeFetcherInterface $attributeFetcher,
        UserInterface $ldapUser
    ) {
        $adminUserProvider->loadUserByUsername('test')->willThrow(UsernameNotFoundException::class);

        $innerUserProvider->loadUserByUsername('test')->willReturn($ldapUser);

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

        $adminUser = $this->loadUserByUsername('test');
        $adminUser->shouldHaveType(AdminUser::class);
        $adminUser->getEmail()->shouldReturn('sylius@sylius.de');
        $adminUser->getFirstName()->shouldReturn('Adam');
        $adminUser->getLastName()->shouldReturn('Ministrator');
        $adminUser->getUsernameCanonical()->shouldReturn('admin');
    }

    function it_updates_the_sylius_user_with_data_from_ldap(
        AbstractUserProvider $adminUserProvider,
        UserProviderInterface $innerUserProvider,
        PropertyAccessorInterface $propertyAccessor,
        LdapAttributeFetcherInterface $attributeFetcher,
        SyliusUserInterface $syliusUser,
        UserInterface $ldapUser
    ) {
        $adminUserProvider->loadUserByUsername('test')->willReturn($syliusUser);
        $innerUserProvider->loadUserByUsername('test')->willReturn($ldapUser);

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

        // Getting the values
        $date = new DateTime();
        $propertyAccessor->getValue(Argument::type(AdminUser::class), 'email')->willReturn('sylius@sylius.de');
        $propertyAccessor->getValue(Argument::type(AdminUser::class), 'emailCanonical')->willReturn('sylius@sylius.de');
        $propertyAccessor->getValue(Argument::type(AdminUser::class), 'enabled')->willReturn(true);
        $propertyAccessor->getValue(Argument::type(AdminUser::class), 'lastLogin')->willReturn($date);
        $propertyAccessor->getValue(Argument::type(AdminUser::class), 'verifiedAt')->willReturn($date);
        $propertyAccessor->getValue(Argument::type(AdminUser::class), 'expiresAt')->willReturn($date);
        $propertyAccessor->getValue(Argument::type(AdminUser::class), 'credentialsExpireAt')->willReturn($date);
        $propertyAccessor->getValue(Argument::type(AdminUser::class), 'usernameCanonical')->willReturn('admin');
        $propertyAccessor->getValue(Argument::type(AdminUser::class), 'username')->willReturn('admin');

        // Setting the values
        $propertyAccessor->setValue($syliusUser, 'email','sylius@sylius.de')->shouldBeCalled();
        $propertyAccessor->setValue($syliusUser, 'emailCanonical', 'sylius@sylius.de')->shouldBeCalled();
        $propertyAccessor->setValue($syliusUser, 'enabled', true)->shouldBeCalled();
        $propertyAccessor->setValue($syliusUser, 'lastLogin', $date)->shouldBeCalled();
        $propertyAccessor->setValue($syliusUser, 'verifiedAt', $date)->shouldBeCalled();
        $propertyAccessor->setValue($syliusUser, 'expiresAt', $date)->shouldBeCalled();
        $propertyAccessor->setValue($syliusUser, 'credentialsExpireAt', $date)->shouldBeCalled();
        $propertyAccessor->setValue($syliusUser, 'usernameCanonical', 'admin')->shouldBeCalled();
        $propertyAccessor->setValue($syliusUser, 'username', 'admin')->shouldBeCalled();

        $this->loadUserByUsername('test')->shouldReturn($syliusUser);
    }
}
