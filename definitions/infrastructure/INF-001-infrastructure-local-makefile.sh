#!/bin/bash

# INF-001: Local Development Makefile Standard Validator
# Usage: ./vendor/team-mate-pro/tmp-standards/definitions/infrastructure/INF-001-infrastructure-local-makefile.sh [path]
#
# Validates that a project's Makefile conforms to INF-001 standard.
# If no path is provided, uses current working directory.

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Project root (default: current directory, or first argument)
PROJECT_ROOT="${1:-.}"
MAKEFILE="${PROJECT_ROOT}/Makefile"

# Required targets per INF-001
REQUIRED_TARGETS=("start" "stop" "fast" "check")

# Recommended targets
RECOMMENDED_TARGETS=("help" "check_fast" "fix" "tests")

# Required variables
REQUIRED_VARS=("docker-compose" "main-container-name" "vendor-dir")

# Counters
ERRORS=0
WARNINGS=0

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  INF-001: Local Development Makefile Standard Validator    ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Check if Makefile exists
if [[ ! -f "$MAKEFILE" ]]; then
    echo -e "${RED}✗ ERROR: Makefile not found at: ${MAKEFILE}${NC}"
    echo -e "  INF-001 requires a Makefile in the project root."
    exit 1
fi

echo -e "${BLUE}Checking:${NC} ${MAKEFILE}"
echo ""

# Function to check if a target exists in Makefile
check_target() {
    local target="$1"
    # Match target definition (target: or target :)
    if grep -qE "^${target}[[:space:]]*:" "$MAKEFILE"; then
        return 0
    fi
    return 1
}

# Function to check if a variable is defined
check_variable() {
    local var="$1"
    if grep -qE "^${var}[[:space:]]*=" "$MAKEFILE"; then
        return 0
    fi
    return 1
}

# Function to check if includes use optional syntax
check_optional_includes() {
    # Find all include statements (not -include)
    local bad_includes
    bad_includes=$(grep -E "^include[[:space:]]+" "$MAKEFILE" 2>/dev/null | grep -v "^-include" || true)
    if [[ -n "$bad_includes" ]]; then
        return 1
    fi
    return 0
}

# Check required targets
echo -e "${BLUE}Required Targets:${NC}"
for target in "${REQUIRED_TARGETS[@]}"; do
    if check_target "$target"; then
        echo -e "  ${GREEN}✓${NC} make ${target}"
    else
        echo -e "  ${RED}✗${NC} make ${target} - ${RED}MISSING${NC}"
        ERRORS=$((ERRORS + 1))
    fi
done
echo ""

# Check recommended targets
echo -e "${BLUE}Recommended Targets:${NC}"
for target in "${RECOMMENDED_TARGETS[@]}"; do
    if check_target "$target"; then
        echo -e "  ${GREEN}✓${NC} make ${target}"
    else
        echo -e "  ${YELLOW}○${NC} make ${target} - ${YELLOW}not found (optional)${NC}"
        WARNINGS=$((WARNINGS + 1))
    fi
done
echo ""

# Check required variables
echo -e "${BLUE}Required Variables:${NC}"
for var in "${REQUIRED_VARS[@]}"; do
    if check_variable "$var"; then
        echo -e "  ${GREEN}✓${NC} ${var}"
    else
        echo -e "  ${YELLOW}○${NC} ${var} - ${YELLOW}not found (recommended)${NC}"
        WARNINGS=$((WARNINGS + 1))
    fi
done
echo ""

# Check optional includes
echo -e "${BLUE}Include Syntax:${NC}"
if check_optional_includes; then
    echo -e "  ${GREEN}✓${NC} All includes use optional syntax (-include)"
else
    echo -e "  ${YELLOW}○${NC} Some includes don't use optional syntax (-include)"
    echo -e "    ${YELLOW}Consider using -include to prevent failures on first run${NC}"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

# Check for help target with ### format
echo -e "${BLUE}Self-Documentation:${NC}"
if grep -qE "^[a-zA-Z_-]+:.*###" "$MAKEFILE"; then
    echo -e "  ${GREEN}✓${NC} Uses ### comment format for help"
else
    echo -e "  ${YELLOW}○${NC} Consider using ### comments for make help"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

# Check for common aliases
echo -e "${BLUE}Common Aliases:${NC}"
ALIASES=("c" "cf" "f" "t")
ALIAS_FOUND=0
for alias in "${ALIASES[@]}"; do
    if check_target "$alias"; then
        ALIAS_FOUND=$((ALIAS_FOUND + 1))
    fi
done
if [[ $ALIAS_FOUND -gt 0 ]]; then
    echo -e "  ${GREEN}✓${NC} Found ${ALIAS_FOUND}/${#ALIASES[@]} common aliases (c, cf, f, t)"
else
    echo -e "  ${YELLOW}○${NC} No common aliases found (c, cf, f, t)"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

# Summary
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
if [[ $ERRORS -eq 0 ]]; then
    if [[ $WARNINGS -eq 0 ]]; then
        echo -e "${GREEN}✓ PASSED${NC} - Makefile conforms to INF-001 standard"
    else
        echo -e "${GREEN}✓ PASSED${NC} - Makefile conforms to INF-001 (${WARNINGS} suggestions)"
    fi
    exit 0
else
    echo -e "${RED}✗ FAILED${NC} - ${ERRORS} required item(s) missing"
    echo ""
    echo -e "See: ${BLUE}definitions/infrastructure/INF-001-infrastructure-local-makefile.md${NC}"
    exit 1
fi
