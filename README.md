![Banner](banner.png)

# Installation

```bash
composer global require laravel-hub-cli/laravel-hub
```

# Usage

You can use the CLI as a near drop-in replacement for the offical Laravel installer. For example:

```bash
laravel-hub new project
```

But the real power of the tool is when you create a `compose` file. The `compose` file is your basic recipe for your application. You should create an `app.yaml` file in the directory where you would like to create your application.

```touch
touch app.yaml
```

When you are done crafting your recipe (see docs below), you should run the `compose` command:

```bash
laravel-hub compose {script=app.yaml}
```

If your `compose` file is named something other than `app.yaml`, pass the name of your file as the first argument.

# Compose File API

- [env](#env)
- [name](#name)
- [touch](#touch)
- [mkdir](#mkdir)
- [artisan](#artisan)
- [console](#console)
- [version](#version)
- [blueprint](#blueprint)
- [php-packages](#php-packages)
- [php-packages-dev](#php-packages-dev)
- [npm-packages](#npm-packages)
- [npm-packages-dev](#npm-packages)

## `env`

The `env` API allows you update or insert (upsert) keys in the applications `.env` file.

An example is show below:

```yaml
env:
 APP_NAME: "Laravel"
 DB_DATABASE: "laravel"
 NEW_ENV_KEY: "value"
```

## `name`

- Required: True

The `name` key is required, the sluggified version of the name will be used to generate the folder name where the application will be installed.

## `touch`

The `touch` API allows you create files in your application. Any required directories will also be created.

An example is show below:

```yaml
touch:
  - "app/Support/helpers.php"
```

## `mkdir`

The `mkdir` API allows you create directories in your application. Any required parent directories will also be created.

An example is show below:

```yaml
mkdir:
  - "resources/svg"
```

## `artisan`

The `artisan` API allows you run Laravel Artisan commands in your application.

An example is show below:

```yaml
artisan:
  - storage:link
  - make:model Post -mfc
```

## `console`

The `console` API allows you create run console commands in your application. 

An example is show below:

```yaml
console:
  - git init
  - code .
```

## `version`

The `version` API allows you to declare what version of Laravel you want to install. You can specify any valid composer version.

An example is show below:

```yaml
version: "7.x"
```

## `blueprint`

The `blueprint` API is an special key. It installs the powerful [Laravel Blueprint](https://blueprint.laravelshift.com/) package as a dev dependency. It then take the value of the key and writes this to a `draft.yaml` file in your project. This allows you to do anything that the [Laravel Blueprint](https://blueprint.laravelshift.com/) does. 

An example is show below:

```yaml
blueprint: 
    models:
        Post:
            title: string:400
            content: longtext
            published_at: nullable timestamp
            author_id: id:user

    controllers:
        Post:
            index:
            query: all
            render: post.index with:posts

            store:
            validate: title, content, author_id
            save: post
            send: ReviewPost to:post.author.email with:post
            dispatch: SyncMedia with:post
            fire: NewPost with:post
            flash: post.title
            redirect: post.index
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

## `npm-packages-dev`

The `npm-packages` API allows you install NPM dev packages into your application.

An example is show below:

```yaml
npm-packages-dev:
  - "alpinejs"
```