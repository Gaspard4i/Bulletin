#!/bin/bash
# =============================================================================
# Bulletin - Setup complet depuis un WSL vide
# =============================================================================
# Ce script installe TOUT : Docker, Git, curl, openssl, clone le repo,
# configure Traefik.me avec HTTPS, et lance le projet.
#
# Usage:
#   curl -sL https://raw.githubusercontent.com/Gaspard4i/Bulletin/main/setup-local.sh | bash
#   ou:
#   chmod +x setup-local.sh && ./setup-local.sh
# =============================================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

log()   { echo -e "${GREEN}[✓]${NC} $1"; }
info()  { echo -e "${BLUE}[i]${NC} $1"; }
warn()  { echo -e "${YELLOW}[!]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; exit 1; }
step()  { echo -e "\n${CYAN}━━━ $1 ━━━${NC}"; }

echo ""
echo -e "${CYAN}╔═══════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║   Bulletin - Installation complete WSL        ║${NC}"
echo -e "${CYAN}╚═══════════════════════════════════════════════╝${NC}"
echo ""

# =============================================================================
# STEP 1: Install system packages
# =============================================================================
step "1/8 - Installation des paquets systeme"

sudo apt-get update -qq

PACKAGES="curl git openssl ca-certificates gnupg lsb-release"
for pkg in $PACKAGES; do
    if ! dpkg -s "$pkg" &>/dev/null; then
        info "Installation de $pkg..."
        sudo apt-get install -y -qq "$pkg" > /dev/null
        log "$pkg installe"
    else
        log "$pkg deja installe"
    fi
done

# =============================================================================
# STEP 2: Install Docker
# =============================================================================
step "2/8 - Installation de Docker"

if command -v docker &>/dev/null; then
    log "Docker deja installe ($(docker --version | cut -d' ' -f3 | tr -d ','))"
else
    info "Installation de Docker..."

    # Add Docker GPG key
    sudo install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg 2>/dev/null
    sudo chmod a+r /etc/apt/keyrings/docker.gpg

    # Add Docker repo
    echo \
      "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
      $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
      sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

    sudo apt-get update -qq
    sudo apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin > /dev/null

    # Add current user to docker group
    sudo usermod -aG docker "$USER"

    log "Docker installe"
    warn "Tu devras peut-etre relancer ton terminal pour que Docker fonctionne sans sudo"
fi

# Start Docker daemon if not running (WSL specific)
if ! sudo service docker status &>/dev/null 2>&1; then
    info "Demarrage du daemon Docker..."
    sudo service docker start
    sleep 2
fi

# Test Docker (with sudo fallback for fresh install)
if ! docker info &>/dev/null 2>&1; then
    if ! sudo docker info &>/dev/null 2>&1; then
        error "Docker ne demarre pas. Essaie: sudo service docker start"
    fi
    warn "Docker necessite sudo (relance ton terminal apres l'install pour fix)"
    DOCKER_CMD="sudo docker"
    COMPOSE_CMD="sudo docker compose"
else
    DOCKER_CMD="docker"
    COMPOSE_CMD="docker compose"
fi

log "Docker fonctionne"

# =============================================================================
# STEP 3: Clone the repository
# =============================================================================
step "3/8 - Verification du repository"

# Le script s'execute depuis le dossier du projet (deja clone)
if [ ! -f "docker-compose.local.yml" ]; then
    error "Fichier docker-compose.local.yml introuvable.\n  Assure-toi d'etre dans le dossier du projet Bulletin.\n  cd /chemin/vers/Bulletin && bash setup-local.sh"
fi

log "Repository detecte dans $(pwd)"

# =============================================================================
# STEP 4: Download traefik.me SSL certificates
# =============================================================================
step "4/8 - Certificats SSL traefik.me"

CERT_DIR="./docker/traefik/certs"
mkdir -p "$CERT_DIR"

if [ ! -f "$CERT_DIR/cert.pem" ]; then
    info "Telechargement des certificats wildcard traefik.me..."
    curl -sL https://traefik.me/cert.pem -o "$CERT_DIR/cert.pem"
    curl -sL https://traefik.me/privkey.pem -o "$CERT_DIR/privkey.pem"
    log "Certificats SSL telecharges"
else
    log "Certificats SSL deja presents"
fi

# Create Traefik dynamic TLS config
cat > ./docker/traefik/dynamic.yml << 'EOFTRAEFIK'
tls:
  certificates:
    - certFile: /etc/traefik/certs/cert.pem
      keyFile: /etc/traefik/certs/privkey.pem
  stores:
    default:
      defaultCertificate:
        certFile: /etc/traefik/certs/cert.pem
        keyFile: /etc/traefik/certs/privkey.pem
EOFTRAEFIK

log "Configuration TLS Traefik creee"

# =============================================================================
# STEP 5: Generate JWT keys
# =============================================================================
step "5/8 - Generation des cles JWT"

JWT_DIR="./backend/config/jwt"
mkdir -p "$JWT_DIR"

if [ ! -f "$JWT_DIR/private.pem" ]; then
    info "Generation des cles RSA pour JWT..."
    openssl genpkey -out "$JWT_DIR/private.pem" -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:bulletin-jwt-local-dev 2>/dev/null
    openssl pkey -in "$JWT_DIR/private.pem" -out "$JWT_DIR/public.pem" -pubout -passin pass:bulletin-jwt-local-dev 2>/dev/null
    chmod 644 "$JWT_DIR/private.pem" "$JWT_DIR/public.pem"
    log "Cles JWT generees"
else
    log "Cles JWT deja presentes"
fi

# =============================================================================
# STEP 6: Ensure docker-compose.local.yml has TLS dynamic config
# =============================================================================
step "6/8 - Configuration Docker Compose"

# Ensure dynamic.yml is mounted in traefik volumes
if ! grep -q "dynamic.yml" docker-compose.local.yml 2>/dev/null; then
    sed -i 's|./docker/traefik/certs:/etc/traefik/certs:ro|./docker/traefik/certs:/etc/traefik/certs:ro\n      - ./docker/traefik/dynamic.yml:/etc/traefik/dynamic.yml:ro|' docker-compose.local.yml
fi

# Ensure file provider is in traefik command
if ! grep -q "providers.file" docker-compose.local.yml 2>/dev/null; then
    sed -i 's|"--providers.docker.exposedbydefault=false"|"--providers.docker.exposedbydefault=false"\n      - "--providers.file.filename=/etc/traefik/dynamic.yml"|' docker-compose.local.yml
fi

log "Docker Compose configure"

# =============================================================================
# STEP 7: Build and start
# =============================================================================
step "7/8 - Build et demarrage des containers"

info "Build des images Docker (premiere fois = quelques minutes)..."
$COMPOSE_CMD -f docker-compose.local.yml build

info "Demarrage de tous les services..."
$COMPOSE_CMD -f docker-compose.local.yml up -d

# Wait for PostgreSQL
info "Attente de PostgreSQL..."
for i in $(seq 1 30); do
    if $COMPOSE_CMD -f docker-compose.local.yml exec -T postgresql pg_isready -U bulletin &>/dev/null; then
        log "PostgreSQL pret"
        break
    fi
    if [ "$i" -eq 30 ]; then
        error "PostgreSQL n'a pas demarre en 30 secondes"
    fi
    sleep 1
done

# Wait for PHP-FPM
info "Attente de Symfony..."
sleep 5

# =============================================================================
# STEP 8: Setup database
# =============================================================================
step "8/8 - Configuration de la base de donnees"

info "Installation des dependances Composer..."
$COMPOSE_CMD -f docker-compose.local.yml exec -T symfony-api composer install --no-interaction 2>/dev/null || true

info "Creation du schema de base de donnees..."
$COMPOSE_CMD -f docker-compose.local.yml exec -T symfony-api php bin/console doctrine:schema:update --force 2>/dev/null || \
$COMPOSE_CMD -f docker-compose.local.yml exec -T symfony-api php bin/console doctrine:migrations:migrate --no-interaction 2>/dev/null || \
warn "Schema deja a jour ou pas de migrations"

info "Chargement des fixtures (donnees de test)..."
$COMPOSE_CMD -f docker-compose.local.yml exec -T symfony-api php bin/console doctrine:fixtures:load --no-interaction 2>/dev/null || \
warn "Fixtures non chargees (pas grave si la DB est deja remplie)"

log "Base de donnees configuree"

# =============================================================================
# DONE
# =============================================================================
echo ""
echo -e "${GREEN}╔═══════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   Bulletin est lance !                        ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${CYAN}URLs :${NC}"
echo -e "    Frontend  : ${BLUE}https://bulletin.traefik.me${NC}"
echo -e "    API       : ${BLUE}https://api.bulletin.traefik.me${NC}"
echo -e "    API Docs  : ${BLUE}https://api.bulletin.traefik.me/api/docs${NC}"
echo -e "    Traefik   : ${BLUE}http://localhost:8080${NC}"
echo -e "    Mailpit   : ${BLUE}http://localhost:8025${NC}"
echo -e "    PostgreSQL: ${BLUE}localhost:5432${NC} (bulletin / bulletin_secret)"
echo ""
echo -e "  ${YELLOW}Comptes de test :${NC}"
echo -e "    Admin     : admin@univ-lille.fr      / GaspardAdmin2005!"
echo -e "    Scolarite : scolarite@univ-lille.fr  / GaspardAdmin2005!"
echo -e "    Prof      : teacher1@univ-lille.fr   / GaspardAdmin2005!"
echo -e "    Etudiant  : student1@univ-lille.fr   / GaspardAdmin2005!"
echo ""
echo -e "  ${YELLOW}Commandes utiles :${NC}"
echo -e "    Arreter   : docker compose -f docker-compose.local.yml down"
echo -e "    Logs      : docker compose -f docker-compose.local.yml logs -f"
echo -e "    Relancer  : docker compose -f docker-compose.local.yml up -d"
echo -e "    Reset DB  : docker compose -f docker-compose.local.yml exec symfony-api php bin/console doctrine:fixtures:load --no-interaction"
echo ""
