# Laravel Hub CLI

The Laravel Hub CLI is an un-official tool for scaffolding new Laravel applications. The name **Laravel Hub** is inspired by Docker and Docker Hub where individuals and teams can share their container images.

Whereas in docker you would create your docker image using a `docker-compose` file, in Laravel Hub you would create your Laravel Application using a `laravel-compose` file.

# Compose File API

- [name](#name)
- [env](#env)
- [php-packages](#php-packages)
- [php-packages-dev](#php-packages-dev)
- [npm-packages](#npm-packages)

## `name`

- Required: True
- Default: None

The `name` key is required, the sluggified version of the name will be used to generate the folder name where the application will be installed.

## `env`

The `env` API allows you insert or update (upsert) keys in the applications `.env` file.

An example is show below:

```yaml
env:
 APP_NAME: "Laravel"
 DB_DATABASE: "laravel"
 NEW_ENV_KEY: "value"
```

## `php-packages`

The `php-packages` API allows you require composer packages into your application.

An example is show below:

```yaml
php-packages:
  - laravel/telescope
  - laravel/socialite
```

## `php-packages-dev`

The `php-packages-dev` API allows you require dev only composer packages into your application.

An example is show below:

```yaml
php-packages-dev:
  - brianium/paratest
```

## `npm-packages`

The `npm-packages` API allows you install NPM packages into your application.

An example is show below:

```yaml
npm-packages:
  - "tailwindcss/@latest"
```