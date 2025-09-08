# spare parts app (php/mysql) — bootstrap

## requirements
- php 8.1+ (plesk windows, iis)
- mysql 5.7+/8.0
- document root points to `/public`

## first run (dev)
1. copy `config/.env.example` to `config/.env` and set your local database credentials.
2. **NEVER commit the `.env` file** - it contains sensitive credentials and is already in `.gitignore`.
3. deploy via plesk with document root `/public`.
4. check `/health` → should print `OK`.

## security
- **Environment Configuration**: See `DEPLOYMENT_SECURITY.md` for secure setup procedures
- **Production Deployment**: Never use `.env` files with real credentials in production
- **Credential Management**: Rotate database credentials regularly

## structure
- /app/core        core classes (router, controller, db, env)
- /app/controllers controllers (lowercase filenames)
- /app/models      models (later)
- /app/views       view templates, layouts
- /config          config files; `.env` is ignored by git
- /public          public web root (i i s); `web.config` routes everything to index.php
- /storage         logs/uploads; logs ignored by git

## dev flow
- create feature branches
- open a pull request
- share pr link here for review    
