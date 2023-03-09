# Laravel Userfile
[![Latest Version on Packagist](https://img.shields.io/packagist/v/randohinn/userfile.svg?style=flat-square)](https://packagist.org/packages/randohinn/userfile)

# UNMAINTAINED AND OF UNKNOWN WORKING STATUS 

Userfile is a Laravel package that allows storing user data in yaml files. It also supports Bearer type API authentication.

## Usage

### Installation
Generate Laravel's default auth scafffolding. `php artisan make:auth`.

You can install the package by running

```
composer require randohinn/userfile
```

Laravel will auto-detect and register the neccessary provider and auth configuration.

Next, run

```
php artisan vendor:publish
```

This will create a `config/userfile.php` file in your app.

### Setup

Userfile uses the default Laravel user model. Therefore, it is necessary to make these changes to the model.

Add `public $incrementing = false;` to the model. This enables the use of string-based uuids, that Userfile generates for your users.

Add `id` and `api_token` to the `$fillable` array of the model. This allows the package to populate those fields on your model instances. It is in this array where you will later define any custom fields you would like to store in the user file.

### Configuration

By default, Userfile expects to find a `userfile` folder in your storage folder. You can change this, by creating a custom filesystem disk in the filyesystem config, and editing `config/userfile.php` to state the name of the new disk, instead of `userfile`. In this file, you can also define a subfolder or your user files, if that is what you want.

The last part, is to set your `config/auth.php` to contain `'guard' => 'userfile',` instead of the default `'guard' => 'web'`. You may the delete unnecessary auth controllers.

## Creating users

Userfile expects to find files in your configured disk's configured subfolder. Filenames go by the standard of `user-email.yaml`. For example `rando@randohinn.com.yaml`.

At the bare minimum, when creating a new user file, it needs to contin the user's email, name and password (in plaintext). The password **will be encrypted on first log-in!**

For example:

```yaml
name: 'Rando Hinn'
email: rando@randohinn.com
password: qwerty
```

After you log in with the first user, for the first time, several things will happen:

- The user will be generated a uuid and an api key
- The user's password will be encryped and the encrypted hash stored instead of plaintext
- An `id_mapping.yaml` and `api_mapping.yaml` file will be created next to the user file. These files hold the mapping of id's and API tokens back to user e-mails, to facilitate faster lookups, and should **not be deleted**.

As a result, your user file may look something like this:

```yaml
name: 'Rando Hinn'
email: rando@randohinn.com
password: $2y$10$RHOsWnmZjAQ2o1fceqZ.Nu.Isa6XBIdFAXmEGlb7SZEWfU/cfTScq
id: fb4ac678-0802-473b-be64-dcba94b40e8a
api_token: UmikrAAteG8KWneIREBnp4sQRCbA0HDFjWAUsrQ59m5e5b6RWtS72uQzdiQT
```

Should you want to add any fields, just add them to this file, and define them in the model's `$fillable` array, like you normally would.

## License

The MIT License. See [License.md](LICENSE.md) for more information.

## Contributing

All contributions welcome. Before you start working on something, please create an issue for it.

If you find a security-related bug, please write directly to rando@randohinn.com . **Do not create a GitHub issue!**
