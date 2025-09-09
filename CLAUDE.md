# Claude Code Configuration

This file contains project-specific information for Claude Code.

## Project Overview
- **Type**: PHP Web Application - Spare Parts Management System
- **Current Branch**: task/T008-critical-database-indexing
- **Main Branch**: main
- **Status**: ✅ Phase 1 + T007 Complete - Enterprise Security Foundation Established

## Commands
Frequently used commands for this project:

```bash
# Security Operations
./scripts/database_security_setup.sql     # Database security hardening
./scripts/database_firewall_rules.sh      # Configure database firewall
/usr/local/bin/mysql-firewall-monitor.sh  # Monitor security events

# Database Operations
mysql -u spare_parts_app -p spare_parts_db  # Application database access
mysql -u spare_parts_monitor -p            # Security monitoring access
./scripts/secure_database_backup.sh        # Encrypted database backup

# Security Monitoring
tail -f /var/log/mysql-security/blocked.log     # Monitor blocked connections
tail -f storage/logs/$(date +%Y-%m-%d).log      # Application security logs
./scripts/security_monitoring_check.sh          # Run security anomaly check

# Development
phpunit tests/                          # Run test suite
php -S localhost:8000 -t public/       # Development server
```

## Project Structure
- `app/controllers/` - Application controllers (26 files)
- `app/core/` - Framework core with security enhancements
- `app/models/` - Data models (13 files)
- `public/` - Public web assets and entry point
- `config/` - Configuration files (secure .env management)
- `docs/` - Comprehensive security and technical documentation
- `scripts/` - Database security and operational scripts
- `tests/` - Comprehensive test suites (Security, Unit, Integration)
- `storage/logs/` - Application and security logs

## Current Implementation Status (September 2025)

### ✅ COMPLETED - Phase 1: Critical Security Fixes (100% Complete)
- **T001 ✅**: Environment security - database credentials secured
- **T002 ✅**: Variable pollution vulnerability eliminated
- **T003 ✅**: Comprehensive input validation framework (15+ rules)
- **T004 ✅**: Enhanced CSRF protection with AJAX support
- **T005 ✅**: Enterprise error handling and logging (PSR-3 compliant)

### ✅ COMPLETED - T007: Database Security Audit and Hardening
- **Database User Security**: Role-based users with minimal privileges, SSL required
- **Connection Encryption**: Full SSL/TLS implementation with certificate validation
- **Comprehensive Audit Logging**: Database operations + security events with retention
- **Database Firewall**: Multi-layer iptables protection with DDoS prevention
- **Backup Encryption**: Military-grade AES-256 encrypted backups with key rotation
- **Security Monitoring**: Real-time anomaly detection with automated alerting
- **Operational Procedures**: Complete security procedures and incident response

### 🎯 Security Transformation Achieved
- **Before**: Basic application with standard security measures
- **After**: Enterprise-grade security with comprehensive protection layers
- **Database Security**: Military-grade with SSL encryption, audit logging, firewall
- **Backup Security**: AES-256 encrypted with secure key management
- **Monitoring**: Real-time threat detection with automated response
- **Compliance Ready**: GDPR, SOX, PCI-DSS, ISO 27001 aligned

## Important Files to Reference
- `PRD.md` - Product requirements and specifications
- `Tasks.md` - Detailed task breakdown and progress
- `PROJECT_MEMORY.md` - Session continuity and achievement tracking
- `COMPREHENSIVE_PROJECT_ANALYSIS.md` - Complete system analysis
- `docs/DATABASE_SECURITY_AUDIT.md` - Database security implementation
- `docs/DATABASE_SECURITY_PROCEDURES.md` - Operational procedures

## Branch Strategy
- Always start new branches for each major task (T###)
- Current branch: `task/T007-database-security-hardening`
- Main branch for production releases: `main`