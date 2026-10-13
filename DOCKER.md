# Docker Runtime

Ovaj projekat ne zavisi od lokalnog PHP-a, Composer-a ili MySQL-a. Razvojni runtime je Docker.

## Images

- PHP: `php:8.4-cli`
- MySQL: `mysql:8.4`
- Composer: preuzet iz `composer:2` image-a u PHP container

Ovo nam omogućava da za Symfony aplikaciju koristimo Symfony 8.1, jer runtime kontrolisano ima PHP 8.4.

## Services

`php`

- radni direktorij: `/app`
- mapira root projekta u container
- ima PHP ekstenzije: `intl`, `opcache`, `pdo_mysql`, `zip`
- služi aplikaciju na `http://localhost:8000` kada postoji `public/index.php`
- dok Symfony skeleton nije kreiran, container ostaje upaljen i čeka

`mysql`

- database: `fleet`
- user: `fleet`
- password: `fleet`
- root password: `root`
- port: `3306`

## Database URL

```text
mysql://fleet:fleet@mysql:3306/fleet?serverVersion=8.4.0&charset=utf8mb4
```

## Useful Commands

Build containers:

```bash
docker compose build
```

Start services:

```bash
docker compose up -d
```

Open shell in PHP container:

```bash
docker compose exec php sh
```

Create Symfony skeleton in the project root, after containers are available:

```bash
docker compose run --rm php composer create-project symfony/skeleton:"8.1.*" /tmp/fleet-app
```

The exact skeleton import step should be done carefully because the root already contains `tema/`, Docker files and planning docs. The next implementation step should copy the created Symfony files into the root without deleting existing theme/planning files.
