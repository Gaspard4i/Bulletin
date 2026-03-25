# Deployment Guide - Bulletin

## Architecture de déploiement

```
[Cloudflare Pages] → Frontend React (*.pages.dev)
        ↓ API calls
[VPS / Fly.io] → Backend Symfony (Docker)
        ↓ SQL
[Supabase] → PostgreSQL 17 (EU Central)
```

## 1. Supabase (Base de données)

**Projet**: Bulletin
**Région**: eu-central-1
**Host**: `db.omoesegewtfwfvznpwoz.supabase.co`
**URL**: `https://omoesegewtfwfvznpwoz.supabase.co`

### Configuration Doctrine
```env
DATABASE_URL="postgresql://postgres:[PASSWORD]@db.omoesegewtfwfvznpwoz.supabase.co:5432/postgres?sslmode=require&charset=utf8"
```

### Migrations
```bash
# Depuis le container Symfony (ou en local)
php bin/console doctrine:migrations:migrate --no-interaction
```

## 2. Cloudflare Pages (Frontend)

**Account**: `0acc43c7e752bae4b4bae269c8bdda83`
**URL**: `https://bulletin.pages.dev`

### Build Settings
- **Framework**: React (Vite)
- **Build command**: `npm run build`
- **Output directory**: `dist`
- **Root directory**: `frontend`

### Variables d'environnement
```
VITE_API_URL=https://[votre-backend-url]/api
```

### Déploiement
```bash
# Via Wrangler CLI
cd frontend
npm run build
npx wrangler pages deploy dist --project-name=bulletin
```

## 3. Backend Symfony (Docker)

### Option A : VPS avec Docker
```bash
# Sur le VPS
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

Le backend n'a PAS besoin du service `postgresql` dans Docker en prod — il se connecte directement à Supabase.

### Option B : Fly.io
```bash
fly launch --name bulletin-api
fly secrets set DATABASE_URL="postgresql://..." APP_SECRET="..." JWT_PASSPHRASE="..."
fly deploy
```

## 4. Variables d'environnement requises

| Variable | Où | Description |
|----------|-----|-------------|
| `DATABASE_URL` | Backend | Connection string Supabase |
| `APP_SECRET` | Backend | Symfony secret |
| `JWT_PASSPHRASE` | Backend | JWT key passphrase |
| `CORS_ALLOW_ORIGIN` | Backend | URL Cloudflare Pages |
| `VITE_API_URL` | Frontend (build) | URL de l'API backend |
| `GITHUB_CLIENT_ID` | Backend | GitHub OAuth app |
| `GITHUB_CLIENT_SECRET` | Backend | GitHub OAuth secret |

## 5. Checklist de déploiement

- [ ] Mot de passe Supabase configuré
- [ ] Migrations exécutées sur Supabase
- [ ] Clés JWT générées (`php bin/console lexik:jwt:generate-keypair`)
- [ ] Backend déployé et accessible
- [ ] `VITE_API_URL` pointe vers le backend
- [ ] Frontend buildé et déployé sur Cloudflare Pages
- [ ] CORS configuré avec le domaine Cloudflare Pages
- [ ] GitHub OAuth app configurée avec les bonnes callback URLs
