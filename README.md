# API

A generic Laravel API and Vue frontend with Docker Compose local environment and Kubernetes production environment.

Access at: [https://wh-vue.ianf.dev/](https://wh-vue.ianf.dev/).

Detailed video explanation: [https://www.youtube.com/watch?v=G7Nug1Mr9VE](https://www.youtube.com/watch?v=G7Nug1Mr9VE).

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) (latest stable version)
- [Docker Compose](https://docs.docker.com/compose/install/) (if not included with Docker)
- Git

## Local Installation

Ensure your user is added to the `docker` group:

```
sudo usermod -aG docker $USER
```

Install:

```
git clone git@github.com:ianflanagan1/api-laravel-postgres-kubernetes
cd api-laravel-postgres-kubernetes
cp backend-laravel/.env.example backend-laravel/.env
make up-detach
make composer-install
make key-generate
make migrate
make seed
npm --prefix frontend-vue install
npm --prefix frontend-vue run dev
```

Access the frontend at `http://localhost:3000` and the API at `http://localhost:8080`.

If the frontend port clashes, modify in `./frontend-vue/vite.config.ts`.

If the backend ports clash, modify in `./docker/compose.yaml` and potentially in `./frontend-vue/vite.config.ts`.

Execute `make down-delete` to stop and remove all containers, named volumes and networks.

## Pre-Commit Script

```
sudo chmod +x scripts/git-hooks/pre-commit
ln -s ../../scripts/git-hooks/pre-commit .git/hooks/pre-commit
git add .
.git/hooks/pre-commit
```

- Vue
  - Prettier
  - ESLint
  - Vue TSC
  - ViTest
- Scripts
  - Shellcheck
- PHP
  - PHPStan
  - Pint
  - PHPUnit
