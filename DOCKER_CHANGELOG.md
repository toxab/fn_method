# Docker Configuration Updates

## New Files

### Main Configurations:
- **compose.yaml** - New main configuration (replaces docker-compose.yaml)
  - Multi-platform support (Linux, macOS Intel, macOS Apple Silicon)
  - Health checks for all services
  - Optimized volumes
  - Added Redis and Adminer

- **compose.override.yaml** - Development overrides (automatic)
  - Xdebug configuration
  - Development-friendly ports
  - Verbose logging

- **compose.macos.yaml** - macOS-specific optimizations
  - Delegated/cached mount options for better I/O
  - Named volumes for vendor/var
  - Platform-specific settings for Apple Silicon

- **compose.prod.yaml** - Production configuration
  - Development tools disabled
  - Performance optimized
  - Enhanced security

### Configuration Files:
- **.env.docker** - Template for environment variables
- **docker/mysql/my.cnf** - MySQL optimizations
- **DOCKER.md** - Complete documentation
- **Makefile** - Convenient management commands

## Key Improvements

### 1. Platform Support
```yaml
# Automatic platform detection
platform: ${MYSQL_PLATFORM:-linux/amd64}

# Mount options for different OS
volumes:
  - .:/var/www/html${MOUNT_OPTIONS:-}
```

### 2. Health Checks
All services now have health checks:
- PHP-FPM
- Nginx
- MySQL
- Redis
- Mailpit

### 3. New Services
- **Redis** - for caching and sessions
- **Adminer** - GUI for database management
- **Mailpit** - email testing (updated)

### 4. Performance Optimizations

#### macOS:
- Named volumes for vendor and var
- Delegated/cached mount options
- Platform-specific MySQL images

#### General:
- Optimized MySQL settings
- OPcache in PHP
- Redis for fast caching

### 5. Developer Experience

#### Makefile Commands:
```bash
make setup          # Complete setup
make setup-macos    # Setup for macOS
make up             # Start containers
make down           # Stop containers
make logs           # View logs
make bash           # Enter PHP container
make migrate        # Run migrations
make test           # Run tests
make es-test        # Test Event Sourcing
```

#### Automatic Platform Detection:
The Makefile automatically detects your platform and includes the appropriate compose files.

## How to Use

### Linux / WSL2:
```bash
make setup
```

### macOS Intel:
```bash
make setup-macos
# Or manually:
docker compose -f compose.yaml -f compose.macos.yaml up -d
```

### macOS Apple Silicon (M1/M2/M3):
```bash
make setup-macos
# Or manually:
docker compose -f compose.yaml -f compose.macos.yaml up -d
```

### Production:
```bash
make prod-up
# Or manually:
docker compose -f compose.yaml -f compose.prod.yaml up -d
```

## Migration from Old Configuration

### If you have the old docker-compose.yaml running:

1. **Stop old containers:**
   ```bash
   docker compose down
   ```

2. **Backup data (optional):**
   ```bash
   docker compose exec mysql mysqldump -u root -proot fintech_db > backup.sql
   ```

3. **Use new configuration:**
   ```bash
   # For macOS:
   make setup-macos

   # For Linux:
   make setup
   ```

4. **Restore data (if needed):**
   ```bash
   make db-restore
   ```

### Volumes are Preserved!
Named volumes (mysql_data) remain intact, so no data is lost.

## Environment Variables

Create `.env.docker.local` with your settings:

```bash
# For macOS Intel:
MYSQL_PLATFORM=linux/amd64
MOUNT_OPTIONS=:cached

# For macOS Apple Silicon:
MYSQL_PLATFORM=linux/arm64
MOUNT_OPTIONS=:delegated

# For Linux:
MYSQL_PLATFORM=linux/amd64
MOUNT_OPTIONS=

# Development:
XDEBUG_MODE=develop,debug

# Production:
XDEBUG_MODE=off
```

## Troubleshooting

### "Port already in use"
Change ports in `.env.docker.local`:
```bash
NGINX_PORT=8029
MYSQL_PORT=3328
```

### Slow Performance on macOS
```bash
# Make sure you're using macOS compose file:
docker compose -f compose.yaml -f compose.macos.yaml up -d

# Or via Makefile (automatic):
make up
```

### Permission Errors
```bash
docker compose exec php chown -R www-data:www-data /var/www/html/var
```

## What's Next?

1. Read [DOCKER.md](DOCKER.md) for complete documentation
2. Use `make help` for list of all commands
3. Configure Xdebug in your IDE (instructions in DOCKER.md)

## Questions?

See:
- [DOCKER.md](DOCKER.md) - complete documentation
- `make help` - list of commands
- `make info` - platform information
