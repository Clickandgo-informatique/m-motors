# Pipeline CI/CD — M-Motors

## Objectif

Cette documentation décrit l’architecture CI/CD mise en place pour le projet M-Motors afin d’automatiser :

- les contrôles qualité,
- les validations applicatives,
- les builds Docker,
- les déploiements staging/production,
- les opérations serveur post-déploiement.

L’objectif est de garantir des déploiements fiables, reproductibles et sécurisés.

---

# Architecture générale

Le workflow CI/CD repose sur :

- GitHub Actions
- Docker / Docker Compose
- Symfony
- VPS OVH
- SSH sécurisé
- Nginx reverse proxy
- HTTPS via certificats SSL

---

# Environnements

## Local

Utilisé pour :
- développement,
- tests,
- création des migrations,
- validation fonctionnelle.

Services :
- PHP
- MySQL
- Nginx
- Node/Vite si nécessaire

---

## Staging

Environnement intermédiaire permettant :
- validation avant production,
- tests d’intégration,
- vérification Docker,
- vérification workflow CI/CD.

---

## Production

Environnement accessible publiquement.

Le déploiement doit :
- minimiser les interruptions,
- préserver les données,
- automatiser les opérations Symfony.

---

# Structure des fichiers DEVOPS

```text
.github/
└── workflows/
    ├── ci.yml
    └── deploy.yml

docker/
docker-compose.yml
docker-compose.prod.yml

deploy/
└── scripts/
```

---

# Pipeline CI

## Déclencheurs

La pipeline CI s’exécute :

- à chaque push,
- à chaque pull request,
- avant merge sur branche principale.

---

## Étapes CI

### 1. Checkout du projet

Récupération du dépôt GitHub.

```yaml
- uses: actions/checkout@v4
```

---

### 2. Installation des dépendances

Installation automatique :

```bash
composer install
```

---

### 3. Vérification Symfony

Validation :

```bash
php bin/console about
php bin/console lint:container
php bin/console lint:yaml config
```

---

### 4. Analyse qualité

Exécution :

```bash
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run
```

---

### 5. Vérification Doctrine

Validation migrations :

```bash
php bin/console doctrine:migrations:status
```

---

### 6. Build Docker

Validation des conteneurs :

```bash
docker compose build
```

---

# Pipeline CD

## Déclenchement

Le déploiement peut être :

- automatique après merge,
- manuel via GitHub Actions,
- limité à certaines branches.

---

# Déploiement serveur

## Connexion SSH

Connexion sécurisée au VPS via clé SSH stockée dans GitHub Secrets.

Secrets nécessaires :

- SSH_HOST
- SSH_USER
- SSH_PRIVATE_KEY

---

## Mise à jour du projet

Le serveur exécute :

```bash
git pull origin master
```

---

## Reconstruction Docker

Rebuild des services :

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

---

## Commandes Symfony post-déploiement

### Migration base de données

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

---

### Nettoyage cache

```bash
php bin/console cache:clear
```

---

### Warmup cache

```bash
php bin/console cache:warmup
```

---

# Gestion des variables sensibles

Aucune donnée sensible ne doit être versionnée.

Les secrets sont stockés dans :

- GitHub Secrets
- fichiers `.env.local`
- variables serveur

Exemples :

```env
APP_SECRET=
DATABASE_URL=
MAILER_DSN=
```

---

# Exemple de workflow GitHub Actions

## ci.yml

```yaml
name: CI

on:
  push:
  pull_request:

jobs:
  symfony:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3

      - name: Install dependencies
        run: composer install --no-interaction

      - name: Symfony checks
        run: |
          php bin/console lint:container
          php bin/console lint:yaml config

      - name: PHPStan
        run: vendor/bin/phpstan analyse

      - name: Docker build
        run: docker compose build
```

---

# Exemple de workflow déploiement

## deploy.yml

```yaml
name: Deploy Production

on:
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: ${{ secrets.SSH_HOST }}
          username: ${{ secrets.SSH_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/m-motors
            git pull origin master
            docker compose -f docker-compose.prod.yml up -d --build
            docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
            docker compose exec php php bin/console cache:clear
            docker compose exec php php bin/console cache:warmup
```

---

# Rollback

En cas d’échec :

## Étapes possibles

### Retour commit précédent

```bash
git reset --hard HEAD~1
```

---

### Redémarrage Docker

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

---

### Vérification état services

```bash
docker ps
docker logs
```

---

# Surveillance et logs

## Vérifications recommandées

- état conteneurs Docker,
- logs Nginx,
- logs Symfony,
- logs GitHub Actions,
- espace disque serveur.

---

# Sécurité

## Bonnes pratiques

- utiliser uniquement des clés SSH,
- désactiver mot de passe root,
- limiter permissions serveur,
- protéger branche principale,
- utiliser HTTPS obligatoire,
- ne jamais versionner `.env.local`.

---

# Améliorations futures

## Possibilités d’évolution

- Blue/Green deployment
- Zero downtime deployment
- Monitoring Prometheus/Grafana
- Notifications Discord/Slack
- Tests automatisés complets
- Déploiement Kubernetes
- Registry Docker privé

---

# Conclusion

La pipeline CI/CD de M-Motors permet :

- d’automatiser les validations,
- de sécuriser les déploiements,
- de réduire les erreurs humaines,
- d’accélérer les livraisons,
- d’améliorer la maintenabilité du projet.

Elle constitue une base DEVOPS solide pour les futures évolutions applicatives.