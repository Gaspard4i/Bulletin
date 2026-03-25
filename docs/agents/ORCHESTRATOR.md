# Agent Orchestrator - Bulletin Project

## Architecture: 12 Agents + 1 Orchestrateur

### Orchestrateur (1)
Chef d'orchestre qui coordonne tous les agents, gère les sprints, résout les dépendances inter-agents.

---

## Backend Symfony (4 agents)

### 1. Symfony API Agent
- **Scope**: Entités Doctrine, Controllers, Services, logique métier
- **Tech**: Symfony 7, API Platform, PHP 8.3
- **Outputs**: src/Entity/, src/Controller/, src/Service/

### 2. OAuth / Security Agent
- **Scope**: CAS Univ Lille, GitHub OAuth, JWT, hiérarchie des rôles
- **Tech**: lexik/jwt-authentication-bundle, custom authenticators
- **Roles**: ROLE_STUDENT < ROLE_TEACHER < ROLE_SCOLARITE < ROLE_ADMIN
- **Outputs**: src/Security/, config/packages/security.yaml

### 3. Database / Migration Agent
- **Scope**: Schéma PostgreSQL, migrations Doctrine, fixtures
- **Tech**: Doctrine ORM, PostgreSQL 16
- **Outputs**: migrations/, src/DataFixtures/

### 4. Testing Agent
- **Scope**: PHPUnit tests, couverture des 4 rôles
- **Tech**: PHPUnit 10, Symfony test client
- **Outputs**: tests/

---

## Frontend React (3 agents)

### 5. React Architecture Agent
- **Scope**: Routing, state management, layouts par rôle
- **Tech**: TanStack Router, TanStack Query, React Context
- **Outputs**: src/routes.tsx, src/lib/, src/pages/

### 6. UI Components Agent
- **Scope**: Composants shadcn/ui, Tailwind, Lucide icons
- **Tech**: shadcn/ui, Tailwind CSS, Radix UI, CVA
- **Outputs**: src/components/

### 7. Design System Agent
- **Scope**: Tokens Figma, maquettes des 4 panels, assets
- **Tools**: Figma MCP, Canva MCP
- **Outputs**: tailwind.config.ts, src/index.css, design tokens

---

## DevOps (3 agents)

### 8. CI/CD Agent
- **Scope**: GitHub Actions, pipelines, secrets management
- **Tech**: GitHub Actions, GHCR
- **Outputs**: .github/workflows/

### 9. Infrastructure Agent
- **Scope**: Cloud deployment, DNS, CDN, database hosting
- **Tech**: Cloudflare, Supabase, Vercel/Fly.io
- **Outputs**: Infrastructure configs, deployment scripts

### 10. Dockerization Agent
- **Scope**: Conteneurisation complète du projet
- **Tech**: Docker, Docker Compose, multi-stage builds
- **Outputs**: Dockerfiles, docker-compose.*.yml, docker/

---

## Docker Stack

| Service        | Image                | Rôle                          |
|----------------|----------------------|-------------------------------|
| symfony-api    | php:8.3-fpm-alpine   | Backend Symfony (multi-stage) |
| react-app      | node:20 → nginx      | Frontend React (build statique)|
| postgresql     | postgres:16-alpine   | DB avec volume persistant     |
| nginx-proxy    | nginx:alpine         | Reverse proxy, SSL termination|
| redis          | redis:7-alpine       | Cache sessions + rate limiting|
| mailpit        | mailpit              | Dev uniquement, catch SMTP    |

## Compose Files
- `docker-compose.yml` — Base definitions
- `docker-compose.dev.yml` — Hot reload, Xdebug, Mailpit, exposed ports
- `docker-compose.prod.yml` — Multi-stage builds, health checks, restart policies

---

## Sprint Plan

### Sprint 0 — Foundation (Current)
- [x] Project structure setup
- [x] Docker stack (dev + prod)
- [x] CI/CD base pipeline
- [x] Symfony skeleton with entities
- [x] React skeleton with routing
- [ ] OAuth integration (CAS + GitHub)
- [ ] JWT authentication flow

### Sprint 1 — Core Features
- [ ] Grade CRUD (Teacher)
- [ ] Bulletin generation (Scolarité)
- [ ] Student bulletin view
- [ ] Admin user management

### Sprint 2 — Polish
- [ ] Design system (Figma tokens)
- [ ] Email notifications
- [ ] PDF bulletin export
- [ ] Performance optimization

### Sprint 3 — Deploy
- [ ] Production deployment
- [ ] SSL/TLS setup
- [ ] Monitoring & logging
- [ ] Documentation
