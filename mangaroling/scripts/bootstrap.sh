#!/usr/bin/env bash
# One-time: generate Laravel + Vite skeletons, then overlay MANGAROLING files (no-clobber).
set -euo pipefail
cd "$(dirname "$0")/.."

[ -f .env ] || { cp .env.example .env; echo "Edit .env (set DB_PASSWORD) then re-run."; exit 1; }

# 1. Laravel skeleton -> temp, merged without overwriting our files
docker run --rm -v "$PWD":/work -w /work composer:2 \
  composer create-project laravel/laravel .laravel-tmp --prefer-dist --no-interaction
cp -rn .laravel-tmp/. backend/ && rm -rf .laravel-tmp
cp -n backend/.env.example backend/.env
cat backend/.env.docker.example >> backend/.env

# 2. Backend deps (Sanctum API scaffolding) and app key
docker compose build backend
docker compose run --rm backend sh -c "composer require laravel/sanctum --no-interaction && php artisan install:api --no-interaction && php artisan key:generate"

# 3. Frontend skeleton (React + TS + Tailwind)
if [ ! -f frontend/package.json ]; then
  docker run --rm -v "$PWD":/work -w /work node:22-alpine sh -c \
    "npm create vite@latest frontend-tmp -- --template react-ts && cp -rn frontend-tmp/. frontend/ && rm -rf frontend-tmp && cd frontend && npm i && npm i -D tailwindcss @tailwindcss/vite"
fi
echo "Done. Next: docker compose up -d postgres redis && docker compose run --rm backend php artisan migrate --seed"
