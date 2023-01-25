<h1 align="center">Sylius - LDAP Plugin</h1>

<p align="center">Plugin for integrating Sylius with LDAP.</p>

**When using instead of the Sylius form login, you will not be able to log in with existing users into Sylius.**

## Installation
Run `composer require brille24/sylius-ldap-plugin` in your project-root.

## Configuration

Add this block to the parameters in your env-file and fill out the parameters.
Your env file will most probably be `.env.local`. If that file does not exist, copy it from `.env` to `.env.local` and fill in the block of parameters below.

```
###> sylius/ldap-plugin ###
LDAP_HOST='ldap.example.com'
LDAP_POST=389
LDAP_ENCRYPTION='ssl' # 'ssl', 'tls' or 'none'
LDAP_PASSWORD='[YOUR PASSWORD FOR READ-ONLY / LOOKUP-USER HERE]'
LDAP_QUERY_PARAMETER='uid'
LDAP_QUERY_STRING='uid={username}'
LDAP_LOOKUP_DN='cn=lookup-user,dc=example,dc=com'
LDAP_USER_QUERY_DN='dc=example,dc=com'
LDAP_GROUP_QUERY_DN='ou=groups,dc=example,dc=com'
###< sylius/ldap-plugin ###
```

To use this new method of authorizing users change the `form_login` in `config/packages/security.yaml` to `form_login_ldap`. For reference the plugin version of this configuration file is located under `tests/Application/config/packages/security.yaml`.

## Origin / Other Sources

This plugin is the result of the third sylius hackathon in oldenburg at brille24. It is based on `symfony/ldap`, so any guide for that package may also help you:
* [Ldap component](https://symfony.com/components/Ldap)
* [Authenticating against an LDAP server](https://symfony.com/doc/current/security/ldap.html)
