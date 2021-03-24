# Laravel Hub CLI

The Laravel Hub CLI is an un-official tool for scaffolding new Laravel applications. The name **Laravel Hub** is inspired by Docker and Docker Hub where individuals and teams can share their container images.

Whereas in docker you would create your docker image using a `docker-compose` file, in Laravel Hub you would create your Laravel Application using a `laravel-compose` file.

# Compose File API

## `name`

- Required: True
- Default: None

The `name` key is required, the sluggified version of the name will be used to generate the folder name where the application will be installed.

## `env`

The `env` API allows you in insert or update keys in the applications `.env` file.

An example is show below:

```yaml
env:
- APP_NAME: "Laravel"
- DB_NAME: "laravel"
- DB_USER: "laravel"
```

laravel-compose.yaml

```yaml
name: Name
version: master
env:
 APP_NAME: Name
php-packages:
 - laravel/socialite
php-packages-dev:
 - laravel/telescope
npm-packages:
 - "tailwindcss@latest"
```