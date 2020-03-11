<?php

namespace spec\Brille24\SyliusLdapPlugin\User;

use Brille24\SyliusLdapPlugin\Ldap\LdapAttributeFetcherInterface;
use Brille24\SyliusLdapPlugin\User\SymfonyToSyliusUserProviderProxy;
use DateTime;
use Prophecy\Argument;
use Sylius\Component\User\Model\UserInterface as SyliusUserInterface;
use Sylius\Bundle\UserBundle\Provider\UserProviderInterface as SyliusUserProviderInterface;
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\User\Model\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use PhpSpec\ObjectBehavior;

class SymfonyToSyliusUserProviderProxySpec extends ObjectBehavior
{
    function let(
        UserProviderInterface $innerUserProvider,
        ObjectRepository $userRepository,
        LdapAttributeFetcherInterface $attributeFetcher
    ) {
        $userRepository->getClassName()->willReturn(AdminUser::class);
        $this->beConstructedWith($innerUserProvider, $userRepository, $attributeFetcher, [], '');
    }

    function it_is_a_sylius_user_provider()
    {
        $this->shouldHaveType(SymfonyToSyliusUserProviderProxy::class);
        $this->shouldImplement(SyliusUserProviderInterface::class);
    }

    function it_loads_a_user_from_the_sylius_repository(
        ObjectRepository $userRepository,
        UserProviderInterface $innerUserProvider,
        LdapAttributeFetcherInterface $attributeFetcher,
        SyliusUserInterface $syliusUser
    ): void {
        $userRepository->findOneBy(['username' => 'test'])->willReturn($syliusUser);
        $syliusUser->isEnabled()->willReturn(true);

        $innerUserProvider->loadUserByUsername('test')->willThrow(UsernameNotFoundException::class);

        $attributeFetcher->fetchAttributesForUser(Argument::any())->shouldNotBeCalled();

        $this->loadUserByUsername('test')->shouldReturn($syliusUser);
    }

    function throws_an_exception_if_the_user_is_deactivated(
        ObjectRepository $userRepository,
        UserProviderInterface $innerUserProvider,
        LdapAttributeFetcherInterface $attributeFetcher,
        SyliusUserInterface $syliusUser
    ): void {
        $userRepository->findOneBy(['username' => 'test'])->willReturn($syliusUser);
        $syliusUser->isEnabled()->willReturn(false);

        $innerUserProvider->loadUserByUsername('test')->willThrow(UsernameNotFoundException::class);

        $attributeFetcher->fetchAttributesForUser(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(UsernameNotFoundException::class)->during('loadUserByUsername', ['test']);
    }
    function it_loads_the_ldap_user_and_creates_a_sylius_user(
        ObjectRepository $userRepository,
        UserProviderInterface $innerUserProvider,
        LdapAttributeFetcherInterface $attributeFetcher,
        UserInterface $ldapUser
    ) {
        $userRepository->findOneBy(['username' => 'test'])->willReturn(null);

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
        ObjectRepository $userRepository,
        UserProviderInterface $innerUserProvider,
        LdapAttributeFetcherInterface $attributeFetcher,
        SyliusUserInterface $syliusUser,
        UserInterface $ldapUser
    ) {
        $userRepository->findOneBy(['username' => 'test'])->willReturn($syliusUser);
        $syliusUser->isEnabled()->willReturn(true);

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

        $syliusUser->setEmail('sylius@sylius.de')->shouldBeCalled();
        $syliusUser->setEmailCanonical('sylius@sylius.de')->shouldBeCalled();
        $syliusUser->setLocked(false)->shouldBeCalled();
        $syliusUser->setEnabled(true)->shouldBeCalled();
        $syliusUser->setLastLogin(Argument::type(DateTime::class))->shouldBeCalled();
        $syliusUser->setVerifiedAt(Argument::type(DateTime::class))->shouldBeCalled();
        $syliusUser->setCredentialsExpireAt(Argument::type(DateTime::class))->shouldBeCalled();
        $syliusUser->setUsernameCanonical('admin');

        $this->loadUserByUsername('test')->shouldReturn($syliusUser);
    }
}
