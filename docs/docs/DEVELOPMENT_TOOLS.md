# Development Tools Guide

This document outlines the development tools and practices for MOJS.

## IDE Configuration

### 1. VS Code Setup
```json
// Example .vscode/settings.json
{
  "editor.formatOnSave": true,
  "editor.codeActionsOnSave": {
    "source.fixAll.eslint": true
  },
  "typescript.tsdk": "node_modules/typescript/lib",
  "rust-analyzer.checkOnSave.command": "clippy"
}
```

### 2. Recommended Extensions
- **Frontend**
  - ESLint
  - Prettier
  - TypeScript
  - React Developer Tools

- **Backend**
  - Rust Analyzer
  - Better TOML
  - Docker
  - GitLens

- **AI/ML**
  - Python
  - Jupyter
  - Pylance
  - Black Formatter

## Development Environment

### 1. Docker Setup
```yaml
# Example docker-compose.yml
version: '3.8'
services:
  frontend:
    build: ./frontend
    ports:
      - "3000:3000"
    volumes:
      - ./frontend:/app
      - /app/node_modules

  backend:
    build: ./backend
    ports:
      - "8080:8080"
    volumes:
      - ./backend:/app

  ai:
    build: ./ai
    ports:
      - "8000:8000"
    volumes:
      - ./ai:/app
```

### 2. Local Development
```bash
# Development commands
npm run dev        # Frontend
cargo run          # Backend
poetry run dev     # AI/ML
```

## Code Quality Tools

### 1. Linting
```json
// Example .eslintrc.json
{
  "extends": [
    "eslint:recommended",
    "plugin:@typescript-eslint/recommended",
    "plugin:react/recommended"
  ],
  "rules": {
    "react/prop-types": "off"
  }
}
```

### 2. Formatting
```json
// Example .prettierrc
{
  "semi": true,
  "singleQuote": true,
  "tabWidth": 2,
  "trailingComma": "es5"
}
```

## Version Control

### 1. Git Configuration
```gitconfig
# Example .gitconfig
[user]
  name = Your Name
  email = your.email@example.com

[core]
  editor = code --wait

[alias]
  st = status
  co = checkout
  br = branch
  ci = commit
```

### 2. Git Hooks
```bash
# Example pre-commit hook
#!/bin/sh
npm run lint
npm run test
cargo test
poetry run pytest
```

## Debugging Tools

### 1. Frontend Debugging
```typescript
// Example debug configuration
{
  "type": "chrome",
  "request": "launch",
  "name": "Debug Frontend",
  "url": "http://localhost:3000",
  "webRoot": "${workspaceFolder}/frontend"
}
```

### 2. Backend Debugging
```json
// Example launch.json
{
  "type": "lldb",
  "request": "launch",
  "name": "Debug Backend",
  "program": "${workspaceFolder}/target/debug/backend",
  "args": [],
  "cwd": "${workspaceFolder}"
}
```

## Testing Tools

### 1. Test Runners
```json
// Example jest.config.js
module.exports = {
  preset: 'ts-jest',
  testEnvironment: 'node',
  setupFilesAfterEnv: ['<rootDir>/jest.setup.js']
};
```

### 2. Coverage Tools
```yaml
# Example codecov.yml
coverage:
  status:
    project:
      default:
        target: 80%
        threshold: 1%
```

## Documentation Tools

### 1. API Documentation
```typescript
// Example Swagger configuration
import { SwaggerModule, DocumentBuilder } from '@nestjs/swagger';

const config = new DocumentBuilder()
  .setTitle('MOJS API')
  .setDescription('Modern Open Journal Systems API')
  .setVersion('1.0')
  .build();

const document = SwaggerModule.createDocument(app, config);
SwaggerModule.setup('api', app, document);
```

### 2. Code Documentation
```rust
/// Example Rust documentation
/// 
/// # Examples
/// 
/// ```
/// let result = add(2, 2);
/// assert_eq!(result, 4);
/// ```
pub fn add(a: i32, b: i32) -> i32 {
    a + b
}
```

## Performance Tools

### 1. Profiling
```bash
# Performance profiling commands
npm run profile        # Frontend
cargo flamegraph       # Backend
poetry run profile     # AI/ML
```

### 2. Monitoring
```typescript
// Example performance monitoring
import { performance } from 'perf_hooks';

const start = performance.now();
// Code to measure
const end = performance.now();
console.log(`Execution time: ${end - start}ms`);
```

## Security Tools

### 1. Vulnerability Scanning
```bash
# Security scanning commands
npm audit             # Frontend
cargo audit          # Backend
poetry check         # AI/ML
```

### 2. Code Analysis
```yaml
# Example CodeQL configuration
name: "CodeQL"
on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  analyze:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: github/codeql-action/init@v2
      - uses: github/codeql-action/analyze@v2
```

## Best Practices

### 1. Tool Configuration
- Consistent settings
- Team standards
- Version control
- Documentation

### 2. Development Workflow
- Code review
- Testing
- Documentation
- Deployment

## Troubleshooting

### 1. Common Issues
- Build failures
- Test failures
- Performance issues
- Dependency problems

### 2. Resolution Steps
- Log analysis
- Debugging
- Tool configuration
- Environment setup 