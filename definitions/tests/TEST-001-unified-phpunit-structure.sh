#!/bin/bash

# TEST-001: Unified PHPUnit Test Structure Validator
# Usage: ./vendor/team-mate-pro/tmp-standards/definitions/tests/TEST-001-unified-phpunit-structure.sh [path]
#
# Validates that a project has unified PHPUnit test structure with:
# - composer scripts for each phpunit testsuite
# - conditional warmup using tests-bundle
# - Makefile aliases for test commands

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Project root (default: current directory, or first argument)
PROJECT_ROOT="${1:-.}"
COMPOSER_JSON="${PROJECT_ROOT}/composer.json"
PHPUNIT_XML="${PROJECT_ROOT}/phpunit.xml"
PHPUNIT_XML_DIST="${PROJECT_ROOT}/phpunit.xml.dist"
MAKEFILE="${PROJECT_ROOT}/Makefile"

# Counters
ERRORS=0
WARNINGS=0

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  TEST-001: Unified PHPUnit Test Structure Validator        ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Check if composer.json exists
if [[ ! -f "$COMPOSER_JSON" ]]; then
    echo -e "${RED}✗ ERROR: composer.json not found at: ${COMPOSER_JSON}${NC}"
    exit 1
fi

# Determine which phpunit config to use
if [[ -f "$PHPUNIT_XML" ]]; then
    PHPUNIT_CONFIG="$PHPUNIT_XML"
elif [[ -f "$PHPUNIT_XML_DIST" ]]; then
    PHPUNIT_CONFIG="$PHPUNIT_XML_DIST"
else
    echo -e "${RED}✗ ERROR: No phpunit.xml or phpunit.xml.dist found${NC}"
    exit 1
fi

echo -e "${BLUE}Checking:${NC}"
echo -e "  composer.json: ${COMPOSER_JSON}"
echo -e "  phpunit config: ${PHPUNIT_CONFIG}"
echo -e "  Makefile: ${MAKEFILE}"
echo ""

# Extract test suites from phpunit.xml (only from <testsuite name="..."> elements)
get_testsuites() {
    grep -oP '(?<=<testsuite name=")[^"]+' "$PHPUNIT_CONFIG" 2>/dev/null || true
}

# Check if composer script exists
has_composer_script() {
    local script="$1"
    grep -qE "\"${script}\"[[:space:]]*:" "$COMPOSER_JSON"
}

# Check if composer script contains substring
composer_script_contains() {
    local script="$1"
    local substring="$2"
    # Extract the script content and check for substring
    grep -A5 "\"${script}\"" "$COMPOSER_JSON" | grep -q "$substring"
}

# Check if Makefile target exists
has_make_target() {
    local target="$1"
    if [[ -f "$MAKEFILE" ]]; then
        grep -qE "^${target}[[:space:]]*:" "$MAKEFILE"
    else
        return 1
    fi
}

# ============================================
# Check Required Warmup Scripts
# ============================================
echo -e "${BLUE}Warmup Scripts:${NC}"

WARMUP_SCRIPTS=("test:warmup" "test:warmup:migrate" "test:warmup:fixtures")
for script in "${WARMUP_SCRIPTS[@]}"; do
    if has_composer_script "$script"; then
        echo -e "  ${GREEN}✓${NC} ${script}"
    else
        echo -e "  ${RED}✗${NC} ${script} - ${RED}MISSING${NC}"
        ERRORS=$((ERRORS + 1))
    fi
done
echo ""

# Check for conditional execution (run-if-modified.sh)
echo -e "${BLUE}Conditional Warmup (tests-bundle):${NC}"
if grep -q "run-if-modified.sh" "$COMPOSER_JSON"; then
    echo -e "  ${GREEN}✓${NC} Uses run-if-modified.sh for conditional execution"
else
    echo -e "  ${YELLOW}○${NC} Not using conditional execution (run-if-modified.sh)"
    echo -e "    ${YELLOW}Consider using team-mate-pro/tests-bundle for performance${NC}"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

# ============================================
# Check Test Suite Scripts
# ============================================
echo -e "${BLUE}PHPUnit Test Suites:${NC}"

TESTSUITES=$(get_testsuites)
if [[ -z "$TESTSUITES" ]]; then
    echo -e "  ${YELLOW}○${NC} No testsuites found in phpunit config"
    WARNINGS=$((WARNINGS + 1))
else
    for suite in $TESTSUITES; do
        case "$suite" in
            "unit")
                SCRIPT="tests:unit"
                ;;
            "integration")
                SCRIPT="tests:integration"
                ;;
            "application")
                SCRIPT="tests:application"
                ;;
            "acceptance")
                SCRIPT="tests:acceptance"
                ;;
            *)
                SCRIPT="tests:${suite}"
                ;;
        esac

        if has_composer_script "$SCRIPT"; then
            echo -e "  ${GREEN}✓${NC} ${suite} -> ${SCRIPT}"
        else
            echo -e "  ${RED}✗${NC} ${suite} -> ${SCRIPT} - ${RED}MISSING${NC}"
            ERRORS=$((ERRORS + 1))
        fi
    done
fi
echo ""

# ============================================
# Check Main Tests Script
# ============================================
echo -e "${BLUE}Main Test Script:${NC}"
if has_composer_script "tests"; then
    echo -e "  ${GREEN}✓${NC} tests script exists"

    # Check if it includes warmup
    if composer_script_contains "tests" "warmup"; then
        echo -e "  ${GREEN}✓${NC} tests script includes warmup"
    else
        echo -e "  ${YELLOW}○${NC} tests script may not include warmup"
        WARNINGS=$((WARNINGS + 1))
    fi
else
    echo -e "  ${RED}✗${NC} tests script - ${RED}MISSING${NC}"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# ============================================
# Check Makefile Aliases
# ============================================
echo -e "${BLUE}Makefile Aliases:${NC}"

if [[ ! -f "$MAKEFILE" ]]; then
    echo -e "  ${YELLOW}○${NC} Makefile not found"
    WARNINGS=$((WARNINGS + 1))
else
    MAKE_ALIASES=("tests" "tests_unit" "tests_integration")
    for alias in "${MAKE_ALIASES[@]}"; do
        if has_make_target "$alias"; then
            echo -e "  ${GREEN}✓${NC} make ${alias}"
        else
            echo -e "  ${YELLOW}○${NC} make ${alias} - ${YELLOW}not found${NC}"
            WARNINGS=$((WARNINGS + 1))
        fi
    done

    # Check for short aliases
    SHORT_ALIASES=("t" "tu" "ti")
    SHORT_FOUND=0
    for alias in "${SHORT_ALIASES[@]}"; do
        if has_make_target "$alias"; then
            SHORT_FOUND=$((SHORT_FOUND + 1))
        fi
    done

    if [[ $SHORT_FOUND -gt 0 ]]; then
        echo -e "  ${GREEN}✓${NC} Found ${SHORT_FOUND}/${#SHORT_ALIASES[@]} short aliases (t, tu, ti)"
    else
        echo -e "  ${YELLOW}○${NC} No short aliases found (t, tu, ti)"
        WARNINGS=$((WARNINGS + 1))
    fi
fi
echo ""

# ============================================
# Check Dependencies
# ============================================
echo -e "${BLUE}Dependencies:${NC}"

if grep -q "team-mate-pro/tests-bundle" "$COMPOSER_JSON"; then
    echo -e "  ${GREEN}✓${NC} team-mate-pro/tests-bundle"
else
    echo -e "  ${YELLOW}○${NC} team-mate-pro/tests-bundle - ${YELLOW}not installed${NC}"
    WARNINGS=$((WARNINGS + 1))
fi

if grep -q "phpunit/phpunit" "$COMPOSER_JSON"; then
    echo -e "  ${GREEN}✓${NC} phpunit/phpunit"
else
    echo -e "  ${RED}✗${NC} phpunit/phpunit - ${RED}MISSING${NC}"
    ERRORS=$((ERRORS + 1))
fi

if grep -q "dama/doctrine-test-bundle" "$COMPOSER_JSON"; then
    echo -e "  ${GREEN}✓${NC} dama/doctrine-test-bundle"
else
    echo -e "  ${YELLOW}○${NC} dama/doctrine-test-bundle - ${YELLOW}recommended for DB rollback${NC}"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

# ============================================
# Summary
# ============================================
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
if [[ $ERRORS -eq 0 ]]; then
    if [[ $WARNINGS -eq 0 ]]; then
        echo -e "${GREEN}✓ PASSED${NC} - Project conforms to TEST-001 standard"
    else
        echo -e "${GREEN}✓ PASSED${NC} - Project conforms to TEST-001 (${WARNINGS} suggestions)"
    fi
    exit 0
else
    echo -e "${RED}✗ FAILED${NC} - ${ERRORS} required item(s) missing"
    echo ""
    echo -e "See: ${BLUE}definitions/tests/TEST-001-unified-phpunit-structure.md${NC}"
    exit 1
fi
