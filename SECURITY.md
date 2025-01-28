# Security Policy

## Supported Versions

| Version | Supported                                             | Released      | End Of Life   | Support |
| ------- | ----------------------------------------------------- | ------------- | ------------- | :-----: |
| 3.5.x   | :hourglass:        Pre-release                        | 2025 (est)    | 2028 (est)    | LTS     |
| 3.4.x   | :heavy_check_mark: Active development                 | 2023          | 2025 (est)    |         |
| 3.3.x   | :heavy_check_mark: Active maintenance                 | 2020          | 2026 (est)    | LTS     |
| 3.2.x   | :x: Not supported                                     | 2020          | 2023          |         |
| 3.1.x   | :x: Not supported                                     | 2017          | 2022          |         |
| 3.0.x   | :x: Not supported                                     | 2016          | 2022          |         |
| 2.x     | :x: Not supported                                     | 2005          | 2021          |         |
| 1.x     | :x: Not supported                                     | 2002          | 2005 (approx) |         |

PKP usually supports current major release and the last major release.
Other releases receive bug fixes for about two years. However, that is not guaranteed.

[LTS versions](https://pkp.sfu.ca/2022/02/15/pkp-announces-long-term-support-lts-software-releases/) are an exception to this general rule, that don't include new features but receive security patches and bug fixes for 3-5 years.
At least 12 months before a LTS version reaches EOL, a new LTS version is designated, so that you have one year to perform an upgrade.


## Reporting a Vulnerability

To report a vulnerability, please contact PKP privately using: pkp-security@lists.sfu.ca

You can expect a response via email to acknowledge your report within 2 working days.

PKP will then work to verify the vulnerability and assess the risk. This is typically done within the first week of a report. Once these details are known, PKP will file a Github issue entry with limited details for tracking purposes. This initial report will not include enough information to fully disclose the vulnerability but will serve as a point of reference for development and fixes once they are available.

When a fix is available, PKP will contact its user community privately via mailing list with details of the fix, and leave a window of typically 2 weeks for community members to patch or upgrade before public disclosure.

PKP then discloses the vulnerability publicly by updating the Github issue entry with complete details and adding a notice about the vulnerability to the software download page (e.g. https://pkp.sfu.ca/software/ojs). At this point, a CVE and credit for the discovery may be added to the entry.

Depending on the severity of the issue PKP may back-port fixes to releases that are beyond the formal software end-of-life.

We aim to have a fix available within a week of notification.
