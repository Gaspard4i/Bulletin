# Bulletin - University Bulletin Board System

## Project Overview
Bulletin is a university grade/bulletin management system for Université de Lille.
4 roles: Student, Teacher, Scolarité, Admin.

## Architecture
- **Backend**: Symfony 7 (PHP 8.3) + API Platform + PostgreSQL
- **Frontend**: React 18 + TypeScript + Vite + shadcn/ui + TanStack Query/Router
- **Auth**: CAS Univ Lille + GitHub OAuth + JWT
- **Infra**: Docker (dev + prod), GitHub Actions CI/CD

## Directory Structure
```
backend/     - Symfony API (PHP 8.3)
frontend/    - React SPA (Vite + TypeScript)
docker/      - Nginx, PHP configs
.github/     - CI/CD workflows
```

## Commands
```bash
make dev          # Start dev environment
make prod         # Start prod environment
make down         # Stop all containers
make test         # Run all tests
make db-migrate   # Run database migrations
make shell-api    # Shell into Symfony container
make shell-front  # Shell into React container
```

## Roles Hierarchy
ROLE_STUDENT < ROLE_TEACHER < ROLE_SCOLARITE < ROLE_ADMIN

## Key Conventions
- API routes: /api/*
- JWT authentication on all API endpoints
- UUID for all entity IDs
- PHP: PSR-12, attributes-based config
- React: functional components, TypeScript strict mode
- CSS: Tailwind utility classes, shadcn/ui components
