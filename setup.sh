#!/bin/bash
# MarketFlow Setup Script
# Automates Docker container setup for price/product alert platform

set -e
RED='\033[0;31m'
GREEN='\033[1;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}=== MarketFlow Setup Script ===${NC}"

#!/bin/bash
# MarketFlow Setup Script
# Automates Docker container setup for price/product alert platform

set -e
RED='\033[0;31m'
GREEN='\033[1;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}=== MarketFlow Setup Script ===${NC}"

# 1. Fix backend Dockerfile (add libsqlite3-dev)
if ! grep -q "libsqlite3-dev" backend/Dockerfile 2>/dev/null; then
    echo -e "${RED}Fixing backend Dockerfile...${NC}"
    sed -i '/apt-get update && apt-get install -y \\\\/libsqlite3-dev' backend/Dockerfile
    if [ $? -ne 0 ]; then
        sed -i '/apt-get update && apt-get install -y \\\\/docker-php-ext-install/i \    libsqlite3-dev \\\\/' backend/Dockerfile
    fi
fi

# 2. Rebuild images
echo -e "${GREEN}Rebuilding Docker images...${NC}"
docker-compose build

# 3. Start containers
echo -e "${GREEN}Starting services...${NC}"
docker-compose up -d

# 4. Verify health
echo -e "${GREEN}Services status:${NC}"
docker-compose ps | grep -E "(app|frontend|db) \\(healthy\\)"

echo -e "\n${GREEN}Setup complete!${NC}"
echo "Access: http://localhost:8000 (backend) & http://localhost:3000 (frontend)"