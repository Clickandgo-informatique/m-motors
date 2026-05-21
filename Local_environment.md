# Environnement local — M-Motors

## Objectif

Ce document décrit la configuration de l’environnement local du projet M-Motors.

L’environnement local a pour but de fournir une stack complète et reproductible permettant de développer et tester l’application dans des conditions proches de la production.

Il repose sur Docker et inclut les services suivants :

- Symfony (PHP-FPM)
- Nginx
- PostgreSQL
- Redis
- Mailhog
- Node.js (Vite / assets front)
- (optionnel) PHPMyAdmin / Adminer

---

# Architecture globale

L’environnement local est entièrement conteneurisé via Docker Compose.

```text
[ Nginx ] → [ PHP-FPM Symfony ] → [ PostgreSQL ]
                     ↓
                 [ Redis ]
                     ↓
               [ Mailhog ]
```

---

# Structure du projet Docker

```text
docker/
├── nginx/
│   └── default.conf
├── php/
│   ├── Dockerfile
│   └── php.ini
├── redis/
└── mailhog/

docker-compose.yml
```

---

# Docker Compose — Services

## Application Symfony (PHP-FPM)

Service principal exécutant Symfony.

Fonctions :
- exécution PHP 8.3+
- exécution Symfony CLI
- exécution migrations Doctrine
- gestion cache

---

## Nginx

Serveur web reverse proxy.

Rôle :
- exposition HTTP (port 80)
- routage vers PHP-FPM
- gestion des assets statiques
- configuration des rewrites Symfony

Exemple de config :

```nginx
server {
    listen 80;
    server_name localhost;

    root /var/www/public;

    index index.php;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass php:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

---

## PostgreSQL

Base de données principale du projet.

Configuration :
- port : 5432
- database : m_motors
- charset : UTF-8 (par défaut PostgreSQL)

Variables :

```env
POSTGRES_DB=m_motors
POSTGRES_USER=app
POSTGRES_PASSWORD=app
```

---

## Redis

Utilisé pour :

- cache Symfony
- sessions
- files de messages (optionnel)

Configuration :
- port : 6379
- aucune authentification en local

---

## Mailhog

Service de test email.

Rôle :
- capture des emails envoyés par Symfony
- interface web pour consultation

Ports :
- SMTP : 1025
- UI : 8025

Configuration Symfony :

```env
MAILER_DSN=smtp://mailhog:1025
```

Interface :
http://localhost:8025

---

## Node.js (Vite)

Utilisé pour assets front-end :

- SCSS compilation
- JS modules
- hot reload

Commandes :

```bash
npm install
npm run dev
```

---

## PHPMyAdmin / Adminer (optionnel)

Avec PostgreSQL, préférence pour Adminer :

- simple
- léger
- compatible PostgreSQL

Accès :
- http://localhost:8080

---

# Fichier docker-compose.yml (extrait)

```yaml
services:
  php:
    build: ./docker/php
    volumes:
      - ./:/var/www

  nginx:
    image: nginx:latest
    ports:
      - "80:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf

  postgres:
    image: postgres:16
    environment:
      POSTGRES_DB: m_motors
      POSTGRES_USER: app
      POSTGRES_PASSWORD: app
    ports:
      - "5432:5432"
    volumes:
      - db_data:/var/lib/postgresql/data

  redis:
    image: redis:7
    ports:
      - "6379:6379"

  mailhog:
    image: mailhog/mailhog
    ports:
      - "1025:1025"
      - "8025:8025"

volumes:
  db_data:
```

---

# Variables d’environnement (.env.local)

```env
APP_ENV=dev
APP_DEBUG=1

DATABASE_URL="postgresql://app:app@postgres:5432/m_motors"

REDIS_URL=redis://redis:6379

MAILER_DSN=smtp://mailhog:1025
```

---

# Démarrage du projet

## Initialisation

```bash
docker compose up -d --build
```

---

## Installation Symfony

```bash
docker compose exec php composer install
```

---

## Base de données

```bash
docker compose exec php php bin/console doctrine:database:create
docker compose exec php php bin/console doctrine:migrations:migrate
```

---

## Assets front

```bash
npm install
npm run dev
```

---

# Accès aux services

| Service     | URL / Port                |
|------------|---------------------------|
| Symfony     | http://localhost          |
| Mailhog     | http://localhost:8025     |
| PostgreSQL  | localhost:5432            |
| Redis       | localhost:6379           |

---

# Debug & logs

## Logs Docker

```bash
docker compose logs -f
```

## Logs Symfony

```bash
docker compose exec php tail -f var/log/dev.log
```

---

# Bonnes pratiques

- ne jamais modifier les containers en production directement
- toujours reconstruire via Dockerfile
- garder les volumes persistants pour PostgreSQL
- utiliser Mailhog uniquement en dev
- séparer `.env`, `.env.local`, `.env.prod`

---

# Problèmes fréquents

## Port déjà utilisé

```bash
docker compose down
```

---

## Cache Symfony corrompu

```bash
docker compose exec php php bin/console cache:clear
```

---

## Permissions Linux

```bash
sudo chown -R $USER:$USER .
```

---

# Évolutions futures

- ajout de RabbitMQ
- segmentation dev/staging identique prod
- profiling Symfony (Blackfire)
- intégration CI locale
- hot reload Docker optimisé Node

---

# Conclusion

L’environnement local M-Motors fournit une stack complète et isolée permettant un développement cohérent avec la production.

Il garantit :
- reproductibilité,
- isolation des services,
- rapidité de développement,
- compatibilité CI/CD.
```