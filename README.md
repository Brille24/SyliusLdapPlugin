<h1 align="center">Sylius - LDAP Plugin</h1>

<p align="center">Plugin for integrating Sylius with LDAP.</p>

## Try it before installing

If yo want to see if this plugin works with your LDAP server, you can try it before integrating it into your project.
This sylius-plugin comes with a dummy sylius installation inside the test folder that you can use to test the integration with your LDAP server.

Run the following commands in a local directory:

```bash
$ git clone https://github.com/Sylius/SyliusLdapPlugin.git SyliusLdapPlugin
$ cd SyliusLdapPlugin
$ composer install
$ (cd tests/Application && yarn install)
$ (cd tests/Application && yarn build)
$ (cd tests/Application && bin/console assets:install public -e test)
```
    
To be able to setup a plugin's database, remember to configure you database credentials and LDAP configuration (see section "Configuration" below) in `tests/Application/.env.local`. After that is done, set up the database:

```bash
$ (cd tests/Application && bin/console doctrine:database:create -e test)
$ (cd tests/Application && bin/console doctrine:schema:create -e test)
$ (cd tests/Application && bin/console sylius:fixtures:load -e dev)
```
When that is done, you can just start the symfony server:
    
```bash
$ (cd tests/Application && bin/console server:run -d public -e dev)
```
    
The server is now running, you can reach it at [http://127.0.0.1:8000/admin/](http://127.0.0.1:8000/admin/) and try to log in using your LDAP credentials.
    
## Installation

Run `composer require sylius/ldap-plugin` in your project-root.

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
###< sylius/ldap-plugin ###
```

## Origin / Other Sources

This plugin is the result of the third sylius hackathon in oldenburg at brille24. It is based on `symfony/ldap`, so any guide for that package may also help you:
* [Ldap component](https://symfony.com/components/Ldap)
* [Authenticating against an LDAP server](https://symfony.com/doc/current/security/ldap.html)
