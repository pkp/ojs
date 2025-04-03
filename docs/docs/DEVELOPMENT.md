# Development Guide

This guide provides detailed instructions for setting up and contributing to the Modern Open Journal Systems (MOJS) project.

## Prerequisites

### System Requirements
- **Operating System**: Linux (Ubuntu 22.04+ recommended), macOS, or Windows with WSL2
- **CPU**: 4+ cores
- **RAM**: 16GB+ recommended
- **Storage**: 50GB+ free space
- **Network**: Stable internet connection

### Required Software
- **Docker**: 24.0+ and Docker Compose
- **Node.js**: 18.0+ (LTS recommended)
- **Rust**: 1.75+ (stable channel)
- **Python**: 3.11+
- **Git**: 2.30+
- **Make**: 4.0+
- **PostgreSQL**: 15+
- **Redis**: 7.0+
- **MinIO**: Latest stable
- **Keycloak**: 22.0+

## Development Environment Setup

### 1. Clone the Repository
```bash
git clone https://github.com/balinesthesia/modern-ojs.git
cd modern-ojs
```

### 2. Environment Configuration
```bash
# Copy environment templates
cp .env.example .env
cp .env.example.frontend .env.frontend
cp .env.example.backend .env.backend
cp .env.example.ai .env.ai

# Edit environment files with your configuration
nano .env
nano .env.frontend
nano .env.backend
nano .env.ai
```

### 3. Docker Setup
```bash
# Build and start all services
make docker-build
make docker-up

# View logs
make docker-logs
```

### 4. Frontend Development
```bash
# Install dependencies
cd frontend
npm install

# Start development server
npm run dev

# Run tests
npm test
```

### 5. Backend Development
```bash
# Install dependencies
cd backend
cargo build

# Start development server
cargo run

# Run tests
cargo test
```

### 6. AI/ML Development
```bash
# Install Poetry (if not already installed)
curl -sSL https://install.python-poetry.org | python3 -

# Initialize Poetry project (if not already initialized)
cd ai
poetry init

# Install dependencies
poetry install

# Activate virtual environment
poetry shell

# Start development server
poetry run uvicorn main:app --reload

# Run tests
poetry run pytest

# Add new dependencies
poetry add package-name

# Add development dependencies
poetry add --group dev package-name

# Export requirements (if needed)
poetry export -f requirements.txt --output requirements.txt --without-hashes
```

## Development Workflow

### 1. Branch Strategy
- `main`: Production-ready code
- `develop`: Integration branch
- `feature/*`: New features
- `bugfix/*`: Bug fixes
- `release/*`: Release preparation

### 2. Code Style
- **Frontend**: ESLint + Prettier
  ```bash
  npm run lint
  npm run format
  ```
- **Backend**: rustfmt
  ```bash
  cargo fmt
  ```
- **AI/ML**: Black + isort
  ```bash
  black .
  isort .
  ```

### 3. Testing
- **Frontend**: Jest + React Testing Library
  ```bash
  npm test
  npm run test:coverage
  ```
- **Backend**: Rust test framework
  ```bash
  cargo test
  cargo test -- --nocapture
  ```
- **AI/ML**: pytest
  ```bash
  pytest
  pytest --cov
  ```

### 4. Documentation
- Update relevant documentation
- Follow JSDoc/rustdoc/Python docstring standards
- Keep README and API docs current

## Common Development Tasks

### Adding a New Feature
1. Create feature branch
   ```bash
   git checkout -b feature/your-feature-name
   ```
2. Implement changes
3. Write tests
4. Update documentation
5. Create pull request

### Fixing a Bug
1. Create bugfix branch
   ```bash
   git checkout -b bugfix/your-bug-fix
   ```
2. Fix the issue
3. Add regression tests
4. Update documentation
5. Create pull request

### Code Review Process
1. Create pull request
2. Request reviews from team members
3. Address feedback
4. Get approvals
5. Merge to develop

## Troubleshooting

### Common Issues
1. **Docker Issues**
   - Clear Docker cache: `docker system prune`
   - Rebuild containers: `make docker-rebuild`

2. **Database Issues**
   - Reset database: `make db-reset`
   - Run migrations: `make db-migrate`

3. **Frontend Issues**
   - Clear node_modules: `rm -rf node_modules`
   - Reinstall dependencies: `npm install`

4. **Backend Issues**
   - Clear target directory: `cargo clean`
   - Update dependencies: `cargo update`

5. **AI/ML Issues**
   - Recreate virtual environment
   - Update dependencies: `pip install -r requirements.txt`

## Performance Optimization

### Frontend
- Use React.memo for expensive components
- Implement code splitting
- Optimize bundle size
- Use proper caching strategies

### Backend
- Implement connection pooling
- Use proper indexing
- Optimize database queries
- Implement caching

### AI/ML
- Optimize model inference
- Implement batch processing
- Use proper hardware acceleration
- Cache model results

## Security Considerations

### Development
- Never commit secrets
- Use environment variables
- Follow security best practices
- Regular security audits

### Testing
- Run security scans
- Test for vulnerabilities
- Check dependencies
- Validate inputs

## Contributing

See [CONTRIBUTING.md](../CONTRIBUTING.md) for detailed contribution guidelines.

## Support

For development support:
- Create an issue
- Join our Discord
- Check documentation
- Contact maintainers 