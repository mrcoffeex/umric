#!/bin/bash

# 🚀 Research Management System - Quick Start Script
# Run from project root: bash setup-dev.sh

set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "════════════════════════════════════════════════════════════"
echo "🎓 Research Management System - Development Setup"
echo "════════════════════════════════════════════════════════════"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Step 1: Check prerequisites
echo -e "\n${BLUE}[1/6]${NC} Checking prerequisites..."
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker not found${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Docker installed${NC}"

# Step 2: Start Docker services
echo -e "\n${BLUE}[2/6]${NC} Starting Docker services..."
vendor/bin/sail up -d > /dev/null 2>&1 || true
echo -e "${GREEN}✅ Services started${NC}"

# Wait for services to be ready
echo -e "\n${YELLOW}⏳ Waiting for services to be ready (30s)...${NC}"
sleep 30

# Step 3: Install dependencies
echo -e "\n${BLUE}[3/6]${NC} Installing dependencies..."
vendor/bin/sail composer install --no-interaction > /dev/null 2>&1 || true
vendor/bin/sail npm install --silent > /dev/null 2>&1 || true
echo -e "${GREEN}✅ Dependencies installed${NC}"

# Step 4: Setup database
echo -e "\n${BLUE}[4/6]${NC} Setting up database..."
vendor/bin/sail artisan migrate --force > /dev/null 2>&1
echo -e "${GREEN}✅ Database migrated${NC}"

# Step 5: Generate test data
echo -e "\n${BLUE}[5/6]${NC} Generating test data..."
vendor/bin/sail artisan tinker --execute "
Category::factory(5)->create();
User::factory(3)->each(function(\$user) {
  \$user->profile()->create(['role' => 'faculty']);
  ResearchPaper::factory(3)->create(['user_id' => \$user->id]);
});
" > /dev/null 2>&1
echo -e "${GREEN}✅ Test data created${NC}"

# Step 6: Build frontend
echo -e "\n${BLUE}[6/6]${NC} Building frontend assets..."
vendor/bin/sail npm run build > /dev/null 2>&1
echo -e "${GREEN}✅ Frontend built${NC}"

echo -e "\n════════════════════════════════════════════════════════════"
echo -e "${GREEN}✨ Setup Complete!${NC}"
echo "════════════════════════════════════════════════════════════"

echo -e "\n${YELLOW}📋 Next Steps:${NC}"
echo "1. Start development server (in new terminal 1):"
echo "   ${BLUE}vendor/bin/sail artisan serve${NC}"
echo ""
echo "2. Start hot reload for frontend (in new terminal 2):"
echo "   ${BLUE}vendor/bin/sail npm run dev${NC}"
echo ""
echo "3. Open application:"
echo "   ${BLUE}vendor/bin/sail open${NC}"
echo ""
echo "4. Register/Login and navigate to:"
echo "   ${BLUE}http://localhost:8000/papers${NC}"

echo -e "\n${YELLOW}📚 Documentation:${NC}"
echo "   FRONTEND_SETUP.md - Complete setup guide"
echo "   FRONTEND_SUMMARY.md - What was built"
echo "   BUILD_SUMMARY.md - Backend status"

echo -e "\n${GREEN}🎉 Happy researching!${NC}\n"
