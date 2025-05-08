#!/bin/bash
set -e

# Enable error handling and logging
trap 'echo "Error on line $LINENO"' ERR
trap 'rm -rf ./svn' EXIT

# Parse command line arguments
DRY_RUN=false
while [[ "$#" -gt 0 ]]; do
    case $1 in
        --dry-run) DRY_RUN=true ;;
        *) echo "Unknown parameter: $1"; exit 1 ;;
    esac
    shift
done

# Setup logging
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Dry run wrapper for SVN commits
dry_run_commit() {
    if [ "$DRY_RUN" = true ]; then
        log "[DRY RUN] Would commit to SVN: $*"
    else
        "$@"
    fi
}

# Validate environment
validate_env() {
    if [ -z "$SVN_URL" ]; then
        log "Error: SVN_URL is not set"
        exit 1
    fi
    
    if [ -z "$WORDPRESS_USER" ] || [ -z "$WORDPRESS_PW" ]; then
        log "Error: WordPress credentials not set"
        exit 1
    fi
    
    # Validate SVN URL format
    if ! [[ $SVN_URL =~ ^https?:// ]]; then
        log "Error: Invalid SVN URL format"
        exit 1
    fi
}

# Check required commands
check_requirements() {
    for cmd in svn rsync git php; do
        if ! command -v $cmd &> /dev/null; then
            log "Error: $cmd is required but not installed"
            exit 1
        fi
    done
}

# Main script
DIR="$(cd "$(dirname "$0")"; pwd)"
cd "$DIR/.."

# Load environment variables
if [ -f .env ]; then
    source .env
else
    log "Warning: .env file not found"
fi

# Validate environment and requirements
validate_env
check_requirements

if [ "$DRY_RUN" = true ]; then
    log "Running in DRY RUN mode - SVN commits will be simulated"
fi

log "Starting deployment to $SVN_URL"

# Get plugin version
version_statement=$(grep "define('WC_EASYCREDIT_VERSION" src/wc-easycredit/wc-easycredit.php)
PLUGINVERSION=$(php -r "$version_statement echo WC_EASYCREDIT_VERSION;")

# Verify git tag exists
if ! git show-ref --tags --quiet --verify -- "refs/tags/$PLUGINVERSION"; then
    log "Error: Git tag $PLUGINVERSION does not exist"
    exit 1
fi

log "Found valid git tag: $PLUGINVERSION"

# Clean and create SVN directory
[ -d ./svn ] && rm -rf ./svn
mkdir -p ./svn

# Checkout SVN repository
log "Checking out SVN repository..."
svn checkout "$SVN_URL" svn --no-auth-cache

# Sync files to trunk
log "Syncing files to trunk..."
rsync -rv --delete \
    --exclude '*.pot~' \
    --exclude '*.po~' \
    --exclude 'composer.json' \
    --exclude 'composer.lock' \
    --exclude 'nbproject' \
    --exclude '.git' \
    --exclude '.gitignore' \
    --exclude '.vscode' \
    --exclude 'node_modules' \
    src/wc-easycredit/ svn/trunk/

# Sync assets
log "Syncing assets..."
rsync -rv --delete assets/* svn/assets/

# Handle SVN changes
cd svn
log "Processing SVN changes..."
cd trunk
svn st | grep '^\?' | sed 's/^\? *//' | xargs -I% svn add %
svn st | grep ^! | awk '{print " --force "$2}' | xargs svn rm
cd ..

# Commit trunk changes
log "Committing trunk changes..."
dry_run_commit svn commit --non-interactive --username "$WORDPRESS_USER" --password "$WORDPRESS_PW" --no-auth-cache -m "Version ${PLUGINVERSION}"

# Create and commit tag
log "Creating tag ${PLUGINVERSION}..."
svn mkdir tags/${PLUGINVERSION}
svn cp trunk/* tags/${PLUGINVERSION}/
dry_run_commit svn commit --non-interactive --username "$WORDPRESS_USER" --password "$WORDPRESS_PW" --no-auth-cache -m "Tag ${PLUGINVERSION}"

if [ "$DRY_RUN" = true ]; then
    log "Dry run completed - SVN commits were simulated"
else
    log "Deployment completed successfully!"
fi
