#!/bin/bash

##############################################################################
# Redis Installation and Configuration Script
# 
# This script installs, configures, and secures Redis for the spare parts
# management system with enterprise-grade security and performance settings.
##############################################################################

set -euo pipefail

# Configuration variables
REDIS_VERSION=${REDIS_VERSION:-"7.2.3"}
REDIS_USER=${REDIS_USER:-"redis"}
REDIS_PASSWORD=${REDIS_PASSWORD:-"$(openssl rand -base64 32)"}
REDIS_PORT=${REDIS_PORT:-6379}
REDIS_MAX_MEMORY=${REDIS_MAX_MEMORY:-"2gb"}
REDIS_DATA_DIR=${REDIS_DATA_DIR:-"/var/lib/redis"}
REDIS_LOG_DIR=${REDIS_LOG_DIR:-"/var/log/redis"}
REDIS_CONFIG_DIR=${REDIS_CONFIG_DIR:-"/etc/redis"}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

# Check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        error "This script must be run as root (use sudo)"
        exit 1
    fi
}

# Detect operating system
detect_os() {
    if [[ -f /etc/os-release ]]; then
        . /etc/os-release
        OS=$NAME
        OS_VERSION=$VERSION_ID
    else
        error "Cannot detect operating system"
        exit 1
    fi
    
    log "Detected OS: $OS $OS_VERSION"
}

# Install Redis based on OS
install_redis() {
    log "Installing Redis server..."
    
    case "$OS" in
        "Ubuntu"*|"Debian"*)
            apt-get update
            apt-get install -y redis-server redis-tools build-essential tcl
            ;;
        "CentOS"*|"Red Hat"*|"Rocky Linux"*|"AlmaLinux"*)
            yum update -y
            yum install -y epel-release
            yum install -y redis gcc make tcl
            ;;
        "Amazon Linux"*)
            yum update -y
            amazon-linux-extras install redis6 -y
            yum install -y gcc make tcl
            ;;
        *)
            error "Unsupported operating system: $OS"
            exit 1
            ;;
    esac
    
    log "Redis installation completed"
}

# Create Redis user and directories
setup_redis_user() {
    log "Setting up Redis user and directories..."
    
    # Create redis user if it doesn't exist
    if ! id "$REDIS_USER" &>/dev/null; then
        useradd -r -s /bin/false -d "$REDIS_DATA_DIR" "$REDIS_USER"
        log "Created Redis user: $REDIS_USER"
    fi
    
    # Create directories
    mkdir -p "$REDIS_DATA_DIR" "$REDIS_LOG_DIR" "$REDIS_CONFIG_DIR"
    
    # Set permissions
    chown -R "$REDIS_USER:$REDIS_USER" "$REDIS_DATA_DIR" "$REDIS_LOG_DIR"
    chmod 750 "$REDIS_DATA_DIR" "$REDIS_LOG_DIR"
    chmod 755 "$REDIS_CONFIG_DIR"
    
    log "Redis directories configured"
}

# Generate Redis configuration
generate_redis_config() {
    log "Generating Redis configuration..."
    
    cat > "$REDIS_CONFIG_DIR/redis.conf" << EOF
# Redis Configuration for Spare Parts Management System
# Generated on $(date)

################################## NETWORK #####################################
bind 127.0.0.1 ::1
port $REDIS_PORT
timeout 300
keepalive 300

# Enable protected mode (require password/bind)
protected-mode yes

################################# TLS/SSL ######################################
# TLS/SSL disabled by default - enable in production with proper certificates
# tls-port 0
# tls-cert-file /path/to/redis.crt
# tls-key-file /path/to/redis.key
# tls-ca-cert-file /path/to/ca.crt

################################# GENERAL #####################################
daemonize yes
supervised systemd
pidfile /var/run/redis/redis-server.pid
loglevel notice
logfile $REDIS_LOG_DIR/redis.log
databases 16

################################ SNAPSHOTTING  ################################
# Save the DB on disk
save 900 1
save 300 10
save 60 10000

stop-writes-on-bgsave-error yes
rdbcompression yes
rdbchecksum yes
dbfilename dump.rdb
dir $REDIS_DATA_DIR

################################# REPLICATION #################################
# Replication settings for master-slave setup (optional)
# masterauth <master-password>
# requirepass <password>

################################## SECURITY ###################################
# Set password authentication
requirepass $REDIS_PASSWORD

# Disable dangerous commands
rename-command FLUSHDB ""
rename-command FLUSHALL ""
rename-command EVAL ""
rename-command DEBUG ""
rename-command CONFIG "CONFIG_$REDIS_PASSWORD"
rename-command SHUTDOWN SHUTDOWN_$REDIS_PASSWORD

################################### CLIENTS ####################################
maxclients 10000

############################## MEMORY MANAGEMENT #############################
maxmemory $REDIS_MAX_MEMORY
maxmemory-policy allkeys-lru
maxmemory-samples 5

############################# LAZY FREEING ####################################
lazyfree-lazy-eviction yes
lazyfree-lazy-expire yes
lazyfree-lazy-server-del yes
replica-lazy-flush yes

############################ KERNEL OOM CONTROL ##############################
oom-score-adj no

#################### KERNEL TRANSPARENT HUGEPAGE CONTROL ##################
disable-thp yes

############################## APPEND ONLY FILE ###############################
appendonly yes
appendfilename "appendonly.aof"
appendfsync everysec
no-appendfsync-on-rewrite no
auto-aof-rewrite-percentage 100
auto-aof-rewrite-min-size 64mb
aof-load-truncated yes
aof-use-rdb-preamble yes

################################ LUA SCRIPTING  ###############################
lua-time-limit 5000

################################## SLOW LOG ###################################
slowlog-log-slower-than 10000
slowlog-max-len 128

################################ LATENCY MONITOR ##############################
latency-monitor-threshold 100

############################# EVENT NOTIFICATION ##############################
notify-keyspace-events ""

############################### ADVANCED CONFIG ###############################
hash-max-ziplist-entries 512
hash-max-ziplist-value 64
list-max-ziplist-size -2
list-compress-depth 0
set-max-intset-entries 512
zset-max-ziplist-entries 128
zset-max-ziplist-value 64
hll-sparse-max-bytes 3000
stream-node-max-bytes 4kb
stream-node-max-entries 100
activerehashing yes
client-output-buffer-limit normal 0 0 0
client-output-buffer-limit replica 256mb 64mb 60
client-output-buffer-limit pubsub 32mb 8mb 60
client-query-buffer-limit 1gb
proto-max-bulk-len 512mb
hz 10
dynamic-hz yes
aof-rewrite-incremental-fsync yes
rdb-save-incremental-fsync yes

# Performance tuning
tcp-keepalive 300
tcp-backlog 511
EOF

    # Set proper permissions on config file
    chown root:$REDIS_USER "$REDIS_CONFIG_DIR/redis.conf"
    chmod 640 "$REDIS_CONFIG_DIR/redis.conf"
    
    log "Redis configuration generated"
}

# Configure system limits
configure_system_limits() {
    log "Configuring system limits for Redis..."
    
    # Add Redis limits to limits.conf
    cat >> /etc/security/limits.conf << EOF

# Redis user limits
$REDIS_USER soft nofile 65535
$REDIS_USER hard nofile 65535
$REDIS_USER soft nproc 4096
$REDIS_USER hard nproc 4096
$REDIS_USER soft memlock unlimited
$REDIS_USER hard memlock unlimited
EOF

    # Configure kernel parameters
    cat > /etc/sysctl.d/redis.conf << EOF
# Redis kernel optimizations
vm.overcommit_memory = 1
net.core.somaxconn = 65535
net.ipv4.tcp_max_syn_backlog = 65535

# Disable transparent huge pages (Redis recommendation)
# Note: This setting requires manual intervention
# echo never > /sys/kernel/mm/transparent_hugepage/enabled
EOF

    sysctl -p /etc/sysctl.d/redis.conf
    
    log "System limits configured"
}

# Create systemd service file
create_systemd_service() {
    log "Creating systemd service file..."
    
    cat > /etc/systemd/system/redis.service << EOF
[Unit]
Description=Redis In-Memory Data Store
After=network.target

[Service]
User=$REDIS_USER
Group=$REDIS_USER
ExecStart=/usr/bin/redis-server $REDIS_CONFIG_DIR/redis.conf
ExecStop=/usr/bin/redis-cli shutdown
TimeoutStopSec=0
Restart=always
RestartSec=5

# Security settings
NoNewPrivileges=true
PrivateTmp=true
PrivateDevices=true
ProtectHome=true
ProtectSystem=strict
ReadWritePaths=$REDIS_DATA_DIR $REDIS_LOG_DIR
CapabilityBoundingSet=CAP_SETGID CAP_SETUID CAP_SYS_RESOURCE
MemoryDenyWriteExecute=true
ProtectKernelModules=true
ProtectKernelTunables=true
ProtectControlGroups=true
RestrictRealtime=true
RestrictNamespaces=true

# Resource limits
LimitNOFILE=65535
LimitNPROC=4096

[Install]
WantedBy=multi-user.target
EOF

    # Reload systemd and enable Redis
    systemctl daemon-reload
    systemctl enable redis
    
    log "Systemd service created and enabled"
}

# Configure firewall
configure_firewall() {
    log "Configuring firewall rules..."
    
    # Check if firewall is active
    if systemctl is-active --quiet ufw; then
        # Ubuntu/Debian UFW
        ufw allow from 127.0.0.1 to any port $REDIS_PORT
        ufw reload
        info "UFW firewall configured"
    elif systemctl is-active --quiet firewalld; then
        # CentOS/RHEL firewalld
        firewall-cmd --permanent --add-rich-rule="rule family='ipv4' source address='127.0.0.1' port protocol='tcp' port='$REDIS_PORT' accept"
        firewall-cmd --reload
        info "firewalld configured"
    elif iptables -L &>/dev/null; then
        # Generic iptables
        iptables -A INPUT -s 127.0.0.1 -p tcp --dport $REDIS_PORT -j ACCEPT
        iptables -A INPUT -p tcp --dport $REDIS_PORT -j DROP
        
        # Save iptables rules (method varies by OS)
        if command -v iptables-save &>/dev/null; then
            iptables-save > /etc/iptables/rules.v4 2>/dev/null || \
            iptables-save > /etc/sysconfig/iptables 2>/dev/null || \
            warning "Could not save iptables rules automatically"
        fi
        info "iptables configured"
    else
        warning "No supported firewall detected - Redis is exposed on all interfaces!"
        warning "Please configure your firewall to restrict access to Redis port $REDIS_PORT"
    fi
}

# Start Redis service
start_redis() {
    log "Starting Redis service..."
    
    systemctl start redis
    
    # Wait for Redis to start
    sleep 2
    
    # Test Redis connection
    if redis-cli -a "$REDIS_PASSWORD" ping | grep -q PONG; then
        log "Redis is running and responding to commands"
    else
        error "Redis failed to start or is not responding"
        exit 1
    fi
}

# Create Redis monitoring script
create_monitoring_script() {
    log "Creating Redis monitoring script..."
    
    cat > /usr/local/bin/redis-monitor.sh << 'EOF'
#!/bin/bash

# Redis Monitoring Script
# Checks Redis health and performance metrics

REDIS_CLI="/usr/bin/redis-cli"
REDIS_PASSWORD="${REDIS_PASSWORD}"
LOG_FILE="/var/log/redis/monitor.log"

# Function to log with timestamp
log_message() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Check Redis connectivity
check_connectivity() {
    if $REDIS_CLI -a "$REDIS_PASSWORD" ping &>/dev/null; then
        return 0
    else
        return 1
    fi
}

# Get Redis info
get_redis_info() {
    $REDIS_CLI -a "$REDIS_PASSWORD" INFO 2>/dev/null
}

# Check memory usage
check_memory_usage() {
    local info=$(get_redis_info)
    local used_memory=$(echo "$info" | grep "used_memory:" | cut -d: -f2 | tr -d '\r')
    local max_memory=$(echo "$info" | grep "maxmemory:" | cut -d: -f2 | tr -d '\r')
    
    if [[ "$max_memory" -gt 0 ]]; then
        local usage_percent=$((used_memory * 100 / max_memory))
        if [[ $usage_percent -gt 90 ]]; then
            log_message "WARNING: High memory usage: ${usage_percent}%"
            return 1
        fi
    fi
    
    return 0
}

# Main health check
main() {
    if ! check_connectivity; then
        log_message "ERROR: Cannot connect to Redis"
        exit 1
    fi
    
    if ! check_memory_usage; then
        log_message "WARNING: Memory usage check failed"
    fi
    
    log_message "INFO: Redis health check passed"
}

main "$@"
EOF

    chmod +x /usr/local/bin/redis-monitor.sh
    
    # Create cron job for monitoring
    cat > /etc/cron.d/redis-monitor << EOF
# Redis monitoring cron job
*/5 * * * * $REDIS_USER /usr/local/bin/redis-monitor.sh
EOF

    log "Redis monitoring script created"
}

# Generate environment variables
generate_env_vars() {
    log "Generating environment variables..."
    
    cat > /tmp/redis-env.txt << EOF
# Redis Configuration Environment Variables
# Add these to your application's .env file

REDIS_HOST=127.0.0.1
REDIS_PORT=$REDIS_PORT
REDIS_PASSWORD=$REDIS_PASSWORD
REDIS_DATABASE=0
REDIS_PREFIX=spare_parts:
REDIS_TIMEOUT=5.0
REDIS_RETRY_INTERVAL=100
REDIS_READ_TIMEOUT=2.0
REDIS_PERSISTENT=true

# Session configuration
REDIS_SESSION_HOST=127.0.0.1
REDIS_SESSION_PORT=$REDIS_PORT  
REDIS_SESSION_PASSWORD=$REDIS_PASSWORD
REDIS_SESSION_DATABASE=1
REDIS_SESSION_PREFIX=sess:

# Queue configuration
REDIS_QUEUE_HOST=127.0.0.1
REDIS_QUEUE_PORT=$REDIS_PORT
REDIS_QUEUE_PASSWORD=$REDIS_PASSWORD
REDIS_QUEUE_DATABASE=2
REDIS_QUEUE_PREFIX=queue:
EOF

    log "Environment variables saved to /tmp/redis-env.txt"
}

# Print installation summary
print_summary() {
    cat << EOF

${GREEN}################################################################${NC}
${GREEN}#              Redis Installation Complete                     #${NC}
${GREEN}################################################################${NC}

${BLUE}Redis Configuration:${NC}
- Host: 127.0.0.1
- Port: $REDIS_PORT
- Password: $REDIS_PASSWORD
- Data Directory: $REDIS_DATA_DIR
- Log Directory: $REDIS_LOG_DIR
- Config File: $REDIS_CONFIG_DIR/redis.conf

${BLUE}Security Features Enabled:${NC}
- Password authentication required
- Dangerous commands disabled
- Firewall rules configured
- System limits optimized
- Monitoring script installed

${BLUE}Next Steps:${NC}
1. Copy environment variables from /tmp/redis-env.txt to your .env file
2. Test Redis connection: redis-cli -a '$REDIS_PASSWORD' ping
3. Check Redis status: systemctl status redis
4. Monitor logs: tail -f $REDIS_LOG_DIR/redis.log
5. Run health check: /usr/local/bin/redis-monitor.sh

${YELLOW}Important Security Notes:${NC}
- Redis password has been set to: $REDIS_PASSWORD
- Store this password securely and add it to your .env file
- Redis is bound to localhost only for security
- Firewall rules restrict external access

${GREEN}Installation completed successfully!${NC}

EOF
}

# Main installation function
main() {
    log "Starting Redis installation and configuration..."
    
    check_root
    detect_os
    install_redis
    setup_redis_user
    generate_redis_config
    configure_system_limits
    create_systemd_service
    configure_firewall
    start_redis
    create_monitoring_script
    generate_env_vars
    print_summary
    
    log "Redis installation and configuration completed successfully!"
}

# Run main function
main "$@"