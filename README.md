<h1 align="center">Sylius - LDAP Plugin</h1>

<p align="center">Plugin for integrating Sylius with LDAP.</p>

## Try it before installing

If yo want to see if this plugin works with your LDAP server, you can try it before integrating it into your project.
This sylius-plugin comes with a dummy sylius installation inside the test folder, you just have to fill in the parameters for your LDAP server and start the symfony-http-server.

Checkout the plugin into a local directory using git, then run the following commands from the checkout-root:

    ```bash
    $ composer install
    $ (cd tests/Application && yarn install)
    $ (cd tests/Application && yarn build)
    $ (cd tests/Application && bin/console assets:install public -e test)
    
    $ (cd tests/Application && bin/console doctrine:database:create -e test)
    $ (cd tests/Application && bin/console doctrine:schema:create -e test)
    ```

To be able to setup a plugin's database, remember to configure you database credentials and LDAP configuration (see below) in `tests/Application/.env.local`.

## Installation

Run `composer require sylius/ldap-plugin` in your project-root.

## Configuration

Add this block to the parameters in your env-file and fill out the parameters:

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

### Running plugin tests

  - PHPUnit

    ```bash
    $ vendor/bin/phpunit
    ```

  - PHPSpec

    ```bash
    $ vendor/bin/phpspec run
    ```

  - Behat (non-JS scenarios)

    ```bash
    $ vendor/bin/behat --tags="~@javascript"
    ```

  - Behat (JS scenarios)
 
    1. Download [Chromedriver](https://sites.google.com/a/chromium.org/chromedriver/)
    
    2. Download [Selenium Standalone Server](https://www.seleniumhq.org/download/).
    
    2. Run Selenium server with previously downloaded Chromedriver:
    
        ```bash
        $ java -Dwebdriver.chrome.driver=chromedriver -jar selenium-server-standalone.jar
        ```
        
    3. Run test application's webserver on `localhost:8080`:
    
        ```bash
        $ (cd tests/Application && bin/console server:run localhost:8080 -d public -e test)
        ```
    
    4. Run Behat:
    
        ```bash
        $ vendor/bin/behat --tags="@javascript"
        ```

### Opening Sylius with the plugin

- Using `test` environment:

    ```bash
    $ (cd tests/Application && bin/console sylius:fixtures:load -e test)
    $ (cd tests/Application && bin/console server:run -d public -e test)
    ```
    
- Using `dev` environment:

    ```bash
    $ (cd tests/Application && bin/console sylius:fixtures:load -e dev)
    $ (cd tests/Application && bin/console server:run -d public -e dev)
    ```
