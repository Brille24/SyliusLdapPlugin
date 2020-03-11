<?php

namespace spec\Brille24\SyliusLdapPlugin\Ldap;

use Brille24\SyliusLdapPlugin\Ldap\LdapAttributeFetcher;
use Brille24\SyliusLdapPlugin\Ldap\LdapAttributeFetcherInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\User\Model\UserInterface;
use Symfony\Component\Ldap\Adapter\QueryInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\LdapInterface;

class LdapAttributeFetcherSpec extends ObjectBehavior
{
    function let(LdapInterface $ldap, UserInterface $user, QueryInterface $query)
    {
        $this->beConstructedWith($ldap, ['username' => 'username', 'first_name' => 'fn', 'last_name' => 'ln']);

        $user->getUsername()->willReturn('testUser');
        $ldap->query('ou=users,dc=example,dc=com', 'uid=testUser')->willReturn($query);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(LdapAttributeFetcher::class);
        $this->shouldImplement(LdapAttributeFetcherInterface::class);
    }

    function it_returns_the_default_values_if_the_ldap_user_is_not_found(
        UserInterface $user,
        QueryInterface $query
    ) {
        $query->execute()->willReturn(new ArrayCollection([]));

        $this->fetchAttributesForUser($user)->shouldReturn([
            'email'                 => null,
            'locked'                => false,
            'username'              => null,
            'expires_at'            => null,
            'last_login'            => null,
            'verified_at'           => null,
            'email_canonical'       => null,
            'username_canonical'    => null,
            'credentials_expire_at' => null,
            'first_name'            => null,
            'last_name'             => null,
            'locale_code'           => null,
        ])
        ;
    }

    function it_returns_the_mapped_data_from_ldap(
        UserInterface $user,
        QueryInterface $query,
        Entry $entry
    ) {
        $query->execute()->willReturn(new ArrayCollection([$entry->getWrappedObject()]));

        $entry->hasAttribute('username')->willReturn(true);
        $entry->getAttribute('username')->willReturn(['value' => 'test']);

        $entry->hasAttribute('fn')->willReturn(true);
        $entry->getAttribute('fn')->willReturn(['value' => 'Max']);

        $entry->hasAttribute('ln')->willReturn(true);
        $entry->getAttribute('ln')->willReturn(['value' => 'Mustermann']);

        $this->fetchAttributesForUser($user)->shouldReturn([
            'email'                 => null,
            'locked'                => false,
            'username'              => 'test',
            'expires_at'            => null,
            'last_login'            => null,
            'verified_at'           => null,
            'email_canonical'       => null,
            'username_canonical'    => null,
            'credentials_expire_at' => null,
            'first_name'            => 'Max',
            'last_name'             => 'Mustermann',
            'locale_code'           => null,
        ])
        ;
    }
}
