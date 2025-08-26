# API

A generic Laravel API and Vue frontend with Docker Compose local environment and Kubernetes production environment.

Detailed video explanation: [https://www.youtube.com/watch?v=G7Nug1Mr9VE](https://www.youtube.com/watch?v=G7Nug1Mr9VE)

Access at: [https://wh-vue.ianf.dev/](https://wh-vue.ianf.dev/)

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) (latest stable version)
- [Docker Compose](https://docs.docker.com/compose/install/) (if not included with Docker)
- Git

## Local Installation

```
git clone git@github.com:ianflanagan1/api-laravel-postgres-kubernetes
cd api-laravel-postgres-kubernetes
cp backend-laravel/.env.example backend-laravel/.env
sudo make up-detach
sudo make composer-install
npm --prefix ./frontend-vue install
npm --prefix ./frontend-vue run dev
```
Access the frontend at `http://localhost:3000` and the API at `http://localhost:8080`.

If your user is added to the `docker` group, `sudo` is not required for `make` commands.

If another application is using port 8080, in `./docker/compose.yaml` modify `services.nginx.ports` from `8080:8080` to `X:8080`, where `X` is a free port number, and access `http://localhost:X` instead.

Execute `sudo make down-delete` to stop and remove all containers, named volumes and networks.

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
