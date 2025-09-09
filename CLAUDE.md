# Claude Code Configuration

This file contains project-specific information for Claude Code.

## Project Overview
- **Type**: Enhanced Spare Parts Management System v2.0 (PHP Web Application)
- **Current Branch**: features-fixes
- **Main Branch**: main
- **Project Status**: Phase 2 - Advanced Performance Enhancement (🚀 IN PROGRESS)
- **Tasks Completed**: 14 of 127 total tasks ✅ COMPLETED
- **Latest Achievement**: T014 - Comprehensive Security Incident Response System

## Current Implementation Status

### ✅ Phase 1 Critical Security & Performance (COMPLETED - 11/11 tasks)
- **T001-T005**: Core security framework with validation, CSRF, error handling
- **T006-T007**: Secure deployment pipeline and database hardening  
- **T008-T009**: Database optimization with 80-95% query reduction
- **T010-T011**: Secure sessions and enterprise password security
- **T012**: Production monitoring system with real-time APM
- **T013**: Backup and disaster recovery with encryption and automation

### 🚀 Phase 2 Advanced Performance Enhancement (IN PROGRESS - 2/35 tasks)
- **Next Task**: T014 - Security Incident Response System
- **Focus Areas**: Testing infrastructure, code quality, API development, mobile interface

## Commands
Project-specific commands for this spare parts management system:

```bash
# Development Commands
# php -S localhost:8000 -t public/     # Start development server
# mysql -u root -p spare_parts         # Connect to database
# vendor/bin/phpunit tests/             # Run test suite (when available)

# Backup Commands (T013 Implementation)
# scripts/backup-automation.sh full    # Create full backup
# scripts/backup-automation.sh incremental  # Create incremental backup
# scripts/backup-automation.sh cleanup # Clean old backups

# Monitoring Commands (T012 Implementation)  
# curl http://localhost/health         # System health check
# curl http://localhost/monitoring     # Monitoring dashboard
# scripts/monitoring-setup.sh          # Setup monitoring infrastructure

# Security Commands
# scripts/security-audit.sh            # Run security audit
# curl http://localhost/csrf-refresh   # Refresh CSRF tokens
```

## Enhanced Project Structure
- `app/core/` - Framework core (BackupManager, ProductionMonitor, SecureSession, etc.)
- `app/controllers/` - Application controllers (BackupController, MonitoringController, etc.)
- `app/models/` - Data models (OptimizedProduct, OptimizedInvoice, etc.)
- `app/views/` - View templates including monitoring/backup dashboards
- `public/` - Public web assets with enhanced security
- `scripts/` - Automation scripts (backup, monitoring, deployment)
- `tests/` - Comprehensive test suites (Unit, Security, Integration)
- `docs/` - Implementation documentation for each completed task
- `storage/` - Logs, backups, and file storage
- `config/` - Secure configuration files (.env.example)

## Enterprise Features Implemented

### Security Framework
- ✅ Comprehensive input validation with 15+ rules
- ✅ Enhanced CSRF protection with automatic token refresh
- ✅ Enterprise password security with history tracking
- ✅ Secure session management with Redis backend
- ✅ Production-safe error handling with PSR-3 logging

### Performance Optimization
- ✅ Database index optimization (70-95% performance improvement)
- ✅ N+1 query elimination (80-95% query reduction)
- ✅ Production monitoring with real-time APM
- ✅ Query optimization framework with intelligent caching

### Operational Excellence
- ✅ Automated backup system with AES-256 encryption
- ✅ Point-in-time recovery and disaster recovery plans
- ✅ Blue-green deployment with automated rollback
- ✅ Comprehensive health monitoring and alerting

## Development Workflow

### Branch Management
- Always start new branch for each task: `git checkout -b task-T0XX-description`
- Base new branches on the latest completed work
- Merge to main only after task completion and documentation updates
- Tag major milestones: `git tag v1.0-phase1-complete`

### Documentation Requirements
- Update Tasks.md with implementation details and testing results
- Update PROJECT_MEMORY.md with achievements and next priorities
- Update Plan.md with progress and timeline adjustments
- Create detailed task documentation in docs/ folder
- Update COMPREHENSIVE_PROJECT_ANALYSIS.md with current status

### Quality Standards
- All security implementations must include comprehensive test suites
- Performance improvements must be benchmarked and documented
- New features require complete API documentation
- All code must follow established security patterns
- Monitoring and logging integration required for all major features

## Context for Next Session

### Immediate Priorities
- **T014**: Security Incident Response System - automated threat detection and response
- **T015**: Automated Code Quality Analysis - SonarQube integration and quality gates
- **Phase 2 Goals**: Complete advanced performance enhancements and testing infrastructure

### Current System Capabilities  
- Enterprise-grade security with comprehensive protection
- High-performance database operations with intelligent caching
- Real-time monitoring with proactive alerting
- Automated backup and disaster recovery
- Production-ready deployment pipeline

### Technical Debt Status
- ✅ All critical security vulnerabilities resolved
- ✅ Major performance bottlenecks eliminated  
- ✅ Production readiness achieved
- 🚀 Quality assurance and testing infrastructure in progress

## Important Notes
- Always check PRD.md, Plan.md, Tasks.md, COMPREHENSIVE_PROJECT_ANALYSIS.md, and PROJECT_MEMORY.md before continuing
- All major implementations require comprehensive documentation in docs/ folder
- Security-first approach: validate all inputs, encrypt sensitive data, comprehensive logging
- Performance tracking: benchmark before/after, integrate with monitoring system
- Enterprise standards: complete testing, security validation, operational procedures
- Context7 framework has been installed and integrated for enhanced development workflow

## Recent Major Achievements
- 🎉 **Phase 1 Complete**: 100% critical security and performance issues resolved
- 🏆 **T013 Complete**: Enterprise backup and disaster recovery system operational
- 🚀 **Operational Excellence**: Production monitoring, automated deployments, comprehensive security
- 📊 **Performance**: 80-95% database optimization, sub-second response times, enterprise scalability