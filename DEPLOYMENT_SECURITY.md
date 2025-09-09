# Deployment Security Guide

## Environment Configuration Security

### Critical Security Notice
This application requires secure environment configuration to protect sensitive data including database credentials, API keys, and other configuration values.

## Environment Setup

### Development Environment
1. Copy the template: `cp config/.env.example config/.env`
2. Update `config/.env` with your local development settings
3. **Never commit the `.env` file to git** - it's already in `.gitignore`

### Production Environment

#### Recommended Approach: Server Environment Variables
Set environment variables directly on your server:

```bash
export APP_ENV=production
export APP_DEBUG=false
export APP_URL=https://your-production-domain.com
export DB_HOST=your-db-host
export DB_PORT=3306
export DB_NAME=your-database-name
export DB_USER=your-db-user
export DB_PASS=your-secure-password
```

#### Alternative: Secure File Management
If using `.env` files in production:
1. Create `.env` file outside the web root
2. Set restrictive file permissions: `chmod 600 /path/to/.env`
3. Use a secure deployment process that doesn't include the file in version control
4. Regularly rotate credentials

## Security Best Practices

### Database Credentials
- Use strong, unique passwords (minimum 16 characters)
- Create dedicated database users with minimal required privileges
- Rotate credentials regularly (recommended: quarterly)
- Never use default or example passwords

### Application Configuration
- Set `APP_DEBUG=false` in production
- Use HTTPS URLs for `APP_URL`
- Configure proper timezone for your region

### File Permissions
```bash
# Application files
find /path/to/app -type f -exec chmod 644 {} \;
find /path/to/app -type d -exec chmod 755 {} \;

# Configuration files (more restrictive)
chmod 600 /path/to/.env
chmod 600 /path/to/config/*.conf

# Storage directory (writable)
chmod -R 775 /path/to/storage
```

### Environment Variable Loading
The application automatically loads environment variables in this order:
1. Server environment variables (highest priority)
2. `.env` file in `config/` directory
3. Default values in code (fallback only)

## Credential Rotation Procedure

### When to Rotate
- Immediately after any security incident
- When team members leave
- Quarterly as standard practice
- After any credential exposure

### Rotation Steps
1. Generate new secure credentials
2. Update production environment configuration
3. Test database connectivity
4. Update backup and monitoring systems
5. Revoke old credentials
6. Document the change

## Security Monitoring

### Monitor for
- Failed database connection attempts
- Unusual login patterns
- Configuration file access attempts
- Environment variable exposure in logs

### Alerting
Set up monitoring for:
- Database connection failures
- Configuration file modifications
- Suspicious access patterns
- Failed authentication attempts

## Emergency Procedures

### If Credentials Are Compromised
1. **Immediately** rotate all affected credentials
2. Review access logs for unauthorized usage
3. Update all affected systems and environments
4. Investigate the source of the compromise
5. Implement additional security measures as needed

### Contact Information
- Database Administrator: [Your DBA contact]
- Security Team: [Your security contact]
- Infrastructure Team: [Your infra contact]

## Compliance Notes

### Audit Trail
- All credential rotations must be documented
- Access to production configurations must be logged
- Regular security reviews must be conducted

### Data Protection
This configuration may contain personally identifiable information (PII) and must be handled according to:
- GDPR requirements (if applicable)
- Local data protection regulations
- Company security policies

---

**Last Updated**: September 2025
**Review Schedule**: Quarterly
**Next Review**: December 2025