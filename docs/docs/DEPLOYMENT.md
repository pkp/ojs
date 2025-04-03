# Deployment Guide

This guide provides detailed instructions for deploying the Modern Open Journal Systems (MOJS) in various environments.

## Deployment Prerequisites

### System Requirements
- **Operating System**: Linux (Ubuntu 22.04+ recommended)
- **CPU**: 8+ cores
- **RAM**: 32GB+ recommended
- **Storage**: 100GB+ free space
- **Network**: High-speed, stable connection

### Required Infrastructure
- **Kubernetes Cluster**: 1.28+
- **Load Balancer**: Nginx Ingress Controller
- **Monitoring**: Prometheus + Grafana
- **Logging**: ELK Stack or similar
- **Backup System**: Automated backup solution

## Deployment Environments

### 1. Development Environment
```bash
# Deploy development environment
make deploy-dev

# Access development environment
kubectl port-forward svc/frontend 3000:80
kubectl port-forward svc/backend 8000:80
kubectl port-forward svc/ai 5000:80
```

### 2. Staging Environment
```bash
# Deploy staging environment
make deploy-staging

# Access staging environment
kubectl port-forward svc/frontend-staging 3000:80
kubectl port-forward svc/backend-staging 8000:80
kubectl port-forward svc/ai-staging 5000:80
```

### 3. Production Environment
```bash
# Deploy production environment
make deploy-prod

# Monitor deployment
kubectl get pods -n production
kubectl get svc -n production
```

## Infrastructure Setup

### 1. Kubernetes Cluster
```bash
# Create cluster
kubectl create cluster

# Configure nodes
kubectl label nodes <node-name> <label-key>=<label-value>

# Set up namespaces
kubectl create namespace development
kubectl create namespace staging
kubectl create namespace production
```

### 2. Storage Configuration
```bash
# Set up MinIO
kubectl apply -f k8s/minio/

# Configure PostgreSQL
kubectl apply -f k8s/postgres/

# Set up Redis
kubectl apply -f k8s/redis/
```

### 3. Monitoring Setup
```bash
# Deploy Prometheus
kubectl apply -f k8s/monitoring/prometheus/

# Deploy Grafana
kubectl apply -f k8s/monitoring/grafana/

# Set up alerts
kubectl apply -f k8s/monitoring/alerts/
```

## Application Deployment

### 1. Frontend Deployment
```bash
# Build frontend
cd frontend
npm run build

# Deploy frontend
kubectl apply -f k8s/frontend/
```

### 2. Backend Deployment
```bash
# Build backend
cd backend
cargo build --release

# Deploy backend
kubectl apply -f k8s/backend/
```

### 3. AI/ML Deployment
```bash
# Build AI services
cd ai
docker build -t mojs-ai:latest .

# Deploy AI services
kubectl apply -f k8s/ai/
```

## Configuration Management

### 1. Environment Variables
```bash
# Create config maps
kubectl create configmap frontend-config --from-file=frontend/.env
kubectl create configmap backend-config --from-file=backend/.env
kubectl create configmap ai-config --from-file=ai/.env
```

### 2. Secrets Management
```bash
# Create secrets
kubectl create secret generic db-secrets --from-file=secrets/db/
kubectl create secret generic api-secrets --from-file=secrets/api/
kubectl create secret generic ai-secrets --from-file=secrets/ai/
```

## Scaling and Performance

### 1. Horizontal Pod Autoscaling
```yaml
# Example HPA configuration
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: frontend-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: frontend
  minReplicas: 2
  maxReplicas: 10
  metrics:
  - type: Resource
    resource:
      name: cpu
      target:
        type: Utilization
        averageUtilization: 70
```

### 2. Resource Limits
```yaml
# Example resource limits
resources:
  requests:
    cpu: "500m"
    memory: "512Mi"
  limits:
    cpu: "1000m"
    memory: "1Gi"
```

## Backup and Recovery

### 1. Database Backup
```bash
# Schedule regular backups
kubectl apply -f k8s/backup/postgres-backup.yaml

# Manual backup
kubectl exec -it postgres-0 -- pg_dump -U postgres > backup.sql
```

### 2. File Storage Backup
```bash
# Backup MinIO data
kubectl exec -it minio-0 -- mc mirror minio/backup/
```

### 3. Disaster Recovery
```bash
# Restore database
kubectl exec -it postgres-0 -- psql -U postgres < backup.sql

# Restore files
kubectl exec -it minio-0 -- mc mirror backup/ minio/
```

## Security Considerations

### 1. Network Security
- Configure network policies
- Set up SSL/TLS
- Implement WAF
- Regular security audits

### 2. Access Control
- RBAC configuration
- Service account management
- API key rotation
- Regular access reviews

### 3. Monitoring and Alerts
- Set up security monitoring
- Configure alert rules
- Regular vulnerability scanning
- Incident response plan

## Maintenance

### 1. Regular Updates
```bash
# Update dependencies
make update-dependencies

# Apply security patches
make security-update

# Upgrade Kubernetes
make k8s-upgrade
```

### 2. Monitoring
- Check system health
- Review logs
- Monitor performance
- Track resource usage

### 3. Backup Verification
- Test backup restoration
- Verify data integrity
- Check backup schedules
- Validate recovery procedures

## Troubleshooting

### 1. Common Issues
```bash
# Check pod status
kubectl get pods

# View logs
kubectl logs <pod-name>

# Check events
kubectl get events

# Debug services
kubectl describe pod <pod-name>
```

### 2. Performance Issues
- Check resource usage
- Review logs
- Analyze metrics
- Optimize configurations

### 3. Security Issues
- Review security logs
- Check access patterns
- Verify configurations
- Update security patches

## Support

For deployment support:
- Check documentation
- Review logs
- Contact maintainers
- Create issues 