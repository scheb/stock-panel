scheb/stock-panel
=================

I've written this Symfony application for myself to keep track of my stock portfolio.

It uses my [Yahoo Finance API](https://github.com/scheb/yahoo-finance-api) to fetch current quotes and calculates profit
and loss from it.

![Tabular view](doc/tables.png)

![Charts view](doc/charts.png)

Requirements
------------

- PHP8.1
- [Yarn package manager](https://yarnpkg.com/)

Installation
------------

1) Configure Symfony environment variables, e.g. as an `.env.local` file (example can be found in `.env.dist`)
2) Install Composer dependencies: `composer install`
3) Initialize the database: `bin/console doctrine:schema:create`
4) Install Yarn dependencies: `yarn install`
5) Build production assets: `yarn build`

License
-------
This software is available under the [MIT license](LICENSE).

Support Me
----------

I love to hear from people using my work, it's giving me the motivation to keep working on it.

If you want to let me know you're finding it useful, please consider giving it a star ⭐ on GitHub.

If you love my work and want to say thank you, you can help me out for a beer 🍻️
[via PayPal](https://paypal.me/ChristianScheb).
