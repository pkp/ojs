# Open Journal Systems

[![Build Status](https://github.com/pkp/ojs/actions/workflows/main.yml/badge.svg)](https://github.com/pkp/ojs/actions/workflows/main.yml)

Open Journal Systems (OJS) is open-source software originally developed by the [Public Knowledge Project](https://pkp.sfu.ca/) to manage scholarly journals. This modernized version, maintained by [Balinesthesia](https://website.anestesiudayana.com/), reimagines OJS with a cutting-edge full-stack architecture, leveraging React with TypeScript and Next.js for a dynamic frontend, Rust for a high-performance backend, and Python for AI-driven features like peer review matchmaking and beyond. [Learn More](https://github.com/balinesthesia/modern-ojs)

## Usage

Read one of these guides to get started using OJS:

- Read the [Admin Guide](https://docs.pkp.sfu.ca/admin-guide/) to learn how to install and configure the application from an official release package. Use this guide to deploy to production.
- Read the [Getting Started](https://docs.pkp.sfu.ca/dev/documentation/en/getting-started) guide to learn how to install the application from this source repository. Use this guide for local development.

Visit our [Documentation Hub](https://docs.pkp.sfu.ca/) for user guides, tutorials, and technical documentation.

## Tech Stack Overview

This modernized OJS is built with a full-stack architecture optimized for performance, scalability, and academic publishing needs:

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

> ⚠️ If you have found a security risk or vulnerability, please read our [security policy](SECURITY.md).

All issues should be filed at the [balinesthesia/modern-ojs](https://github.com/balinesthesia/modern-ojs) repository. Feature requests can be made at our Community Forum. Learn more about how to report a problem. For PKP’s original issue tracker, visit pkp/pkp-lib.

All issues should be filed at the [pkp/pkp-lib](https://github.com/pkp/pkp-lib/issues/) repository. Feature requests can be made at our [Community Forum](https://forum.pkp.sfu.ca/). Learn more about how to [report a problem](https://docs.pkp.sfu.ca/dev/contributors/#report-a-problem).

## Community Code of Conduct

This repository is a community space managed by [Balinesthesia](https://website.anestesiudayana.com/). All activities here are governed by [Balinesthesia Code of Conduct](), inspired by [PKP's Code of Conduct](https://pkp.sfu.ca/code-of-conduct/) governance. Please review the Code and help us create a welcoming environment for all participants.

## Contributions

Read the [Contributor's Guide](https://docs.pkp.sfu.ca/dev/contributors/) to learn how to make a pull request. This document describes our code formatting guidelines (e.g., Prettier for TypeScript, rustfmt for Rust), as well as information about how we organize stable branches and submodules across the frontend, backend, and AI components.

## License

This software is released under the the GNU General Public License. See the file `docs/COPYING` included with this distribution for the terms of this license.

Third parties are welcome to modify and redistribute this modernized OJS in entirety or parts according to the terms of this license. [Balinesthesia](https://website.anestesiudayana.com/) also welcomes patches for improvements or bug fixes to the software, whether for the React frontend, Rust backend, or Python AI services. This project is a derivative of [Open Journal Systems](https://pkp.sfu.ca/software/ojs/) by the [Public Knowledge Project](https://pkp.sfu.ca/).