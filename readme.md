# Spare Parts Management System (PHP/MySQL) — Enterprise Edition

## Overview
Complete enterprise-grade spare parts management system with role-based access control (RBAC), multi-currency support, professional data export, interactive dashboard, and comprehensive business management features.

## Requirements
- PHP 8.1+ (Plesk Windows, IIS)
- MySQL 5.7+/8.0
- Document root points to `/public`
- Redis (optional, for session storage and caching)

## First Run (Development)
1. Copy `config/.env.example` to `config/.env` and set your local database credentials.
2. **NEVER commit the `.env` file** - it contains sensitive credentials and is already in `.gitignore`.
3. Deploy via Plesk with document root `/public`.
4. Check `/health` → should return JSON status with database and session information.

## Key Features
- **User Management & RBAC**: Complete role-based access control with 6 roles and 20 permissions
- **Tax & Currency Management**: Multi-currency support with comprehensive tax rate management
- **Professional Data Export**: PDF, CSV, XLS, and XLSX export with TCPDF integration
- **Interactive Dashboard**: Real-time charts and activity analysis with Chart.js
- **Notifications System**: Complete notification management with templates
- **Inventory Management**: Products, categories, makes, models, and warehouse management
- **Sales & Purchase Flow**: Quotes, orders, invoices, payments, and returns
- **Bilingual Support**: English and Arabic interface with RTL support

## security
- **Environment Configuration**: See `DEPLOYMENT_SECURITY.md` for secure setup procedures
- **Production Deployment**: Never use `.env` files with real credentials in production
- **Credential Management**: Rotate database credentials regularly

## Project Structure
- `/app/core` - Core classes (router, controller, db, env, helpers)
- `/app/controllers` - Controllers (lowercase filenames)
- `/app/models` - Models with database relationships
- `/app/views` - View templates and layouts
- `/app/libraries` - Third-party libraries (TCPDF)
- `/app/lang` - Translation files (English/Arabic)
- `/config` - Configuration files; `.env` is ignored by git
- `/public` - Public web root (IIS); `web.config` routes everything to index.php
- `/storage` - Logs and uploads; logs ignored by git
- `/export` - Generated export files
- `/scripts` - Database migrations and setup scripts

## Development Workflow
- Create feature branches
- Open a pull request
- Share PR link here for review

## Documentation
- `PROJECT_STATUS.md` - Current project status and recent updates
- `TECHNICAL_REFERENCE.md` - Comprehensive technical documentation
- `PROJECT_MEMORY.md` - Session continuity and achievement tracking
- `DEPLOYMENT_SECURITY.md` - Security and deployment procedures
- `docs/` - Additional technical documentation

## Recent Updates (September 2025)
- ✅ Professional Data Export System (PDF, CSV, XLS, XLSX)
- ✅ Interactive Dashboard with Real-time Charts
- ✅ Complete Notifications System
- ✅ Enterprise User Management & RBAC
- ✅ Tax & Currency Management System
- ✅ High-Performance Database Optimization
- ✅ Redis Session Storage & Caching
