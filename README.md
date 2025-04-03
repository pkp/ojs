# Modern Open Journal Systems

<!-- Build Status badge to be added once CI is set up -->
<!-- [![Build Status](https://github.com/balinesthesia/modern-ojs/actions/workflows/main.yml/badge.svg)](https://github.com/balinesthesia/modern-ojs/actions/workflows/main.yml) -->

Modern Open Journal Systems (MOJS) is open-source software derived from Open Journal Systems (OJS), originally developed by the [Public Knowledge Project](https://pkp.sfu.ca/) to manage scholarly journals. This modernized fork, maintained by [Balinesthesia](https://website.anestesiudayana.com/), reimagines OJS with a cutting-edge full-stack architecture, leveraging React with TypeScript and Next.js for a dynamic frontend, Rust for a high-performance backend, and Python for AI-driven features like peer review matchmaking and beyond. [Learn More](https://github.com/balinesthesia/modern-ojs)

## Usage

> *MOJS is currently under active development and not yet ready for production or local deployment. Usage guides (Admin Guide, Getting Started, and Documentation Hub) will be provided upon our first release at [balinesthesia.github.io/docs](https://balinesthesia.github.io/docs). Stay tuned at [github.com/balinesthesia/modern-ojs](https://github.com/balinesthesia/modern-ojs) for updates!*

## Tech Stack Overview

MOJS is built with a full-stack architecture optimized for performance, scalability, and academic publishing needs:

- **Frontend**: React with TypeScript and Next.js for a responsive, SEO-friendly interface.
- **Backend**: Rust with Actix Web for secure, high-performance APIs.
- **AI/ML**: Python with FastAPI for features like peer review matchmaking, citation analysis, and beyond.
- **Database**: PostgreSQL for robust, structured data storage.
- **File Storage**: MinIO for scalable hosting of PDFs and supplementary files.
- **Caching**: Redis for optimized performance on frequent queries.
- **Search**: Meilisearch for fast, full-text article search.
- **Authentication**: Keycloak with OAuth 2.0 and OpenID Connect for secure user access.
- **Deployment**: Docker and Kubernetes for scalable, portable infrastructure.
- **Monitoring**: Prometheus and Grafana for system health and performance tracking.

## Bugs / Feature Requests

> ⚠️ If you have found a security risk or vulnerability, please read our [security policy](./SECURITY.md).

All issues should be filed at the [balinesthesia/modern-ojs](https://github.com/balinesthesia/modern-ojs/issues) repository. Feature requests can be made by opening a discussion at [github.com/balinesthesia/modern-ojs/discussions](https://github.com/balinesthesia/modern-ojs/discussions). Learn more about how to [report a problem](https://github.com/balinesthesia/modern-ojs/blob/main/CONTRIBUTING.md#report-a-problem). For PKP’s original OJS issue tracker, visit [pkp/pkp-lib](https://github.com/pkp/pkp-lib/issues/).

## Community Code of Conduct

This repository is a community space managed by [Balinesthesia](https://website.anestesiudayana.com/). All activities here are governed by the [Balinesthesia Code of Conduct](CODE_OF_CONDUCT.md), inspired by [PKP's Code of Conduct](https://pkp.sfu.ca/code-of-conduct/). Please review the Code and help us create a welcoming environment for all participants.

## Contributions

> *MOJS’s Contributor Guide is under development and will be available upon our first release. It will cover pull request guidelines, code formatting (e.g., Prettier for TypeScript, rustfmt for Rust), and branch/submodule organization for the frontend, backend, and AI components. For now, see [CONTRIBUTING.md](/docs/CONTRIBUTING.md) for basic contribution details.*

## License

**Modern Open Journal Systems (MOJS)**

Copyright (C) 2025 Balinesthesia

Derived from [Open Journal Systems (OJS)](https://pkp.sfu.ca/software/ojs/), Copyright (C) 2001-2025 [Public Knowledge Project](https://pkp.sfu.ca/)

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, version 3. This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the [docs/LICENSE](./docs/LICENSE) and [docs/COPYING](./docs/COPYING) files for details.

Third parties are welcome to modify and redistribute MOJS in entirety or parts according to the terms of this license. [Balinesthesia](https://website.anestesiudayana.com/) also welcomes patches for improvements or bug fixes to the software, whether for the React frontend, Rust backend, or Python AI services—see [github.com/balinesthesia/modern-ojs](https://github.com/balinesthesia/modern-ojs) for contribution details.