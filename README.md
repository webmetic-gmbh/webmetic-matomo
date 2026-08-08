# Matomo Webmetic Plugin

## Description

**See which companies visit your website — directly in Matomo.**

Webmetic identifies the companies behind your website visitors (B2B visitor
identification) and integrates the results into the Matomo features you already use:

- **Companies report** under *Visitors*, with a dashboard widget
- **Company name on every identified visit** in the Visitor Log and visitor profile
- **Segments**: filter any Matomo report by company (e.g. pages viewed by a specific
  company, referrers that brought a specific company to your site)
- Company data is also available through the Matomo Reporting & Live APIs

### How it works

On the first page view of a visit, the plugin sends a **sha256 hash of the visitor's
IP address** (never the raw IP) to the Webmetic API. If the IP belongs to an
identifiable company network, the company name is stored with the visit. Results are
cached locally, lookups run fail-safe and never delay your tracking.

A [Webmetic](https://webmetic.de) account with a Data Layer API key is required.
Identification quality is focused on the DACH region (Germany, Austria, Switzerland).

### Privacy

- Only a one-way hash of the visitor IP is transmitted — never the raw IP address.
- Company identification is **disabled by default** and must be enabled explicitly.
- If Matomo's IP anonymization is configured to also apply to visit enrichment,
  lookups are skipped entirely.
- Stored data (company name/ID per visit, local lookup cache) is fully covered by
  Matomo's GDPR export, erasure, and retention-purge tooling.
- Details: see your Webmetic data processing agreement (AVV).

## FAQ

__Do I need a Webmetic account?__

Yes. The plugin uses the Webmetic API to resolve visitor IPs to companies. You can
create an account at [webmetic.de](https://webmetic.de); the Data Layer API key is
available in your Webmetic dashboard under *API details*.

__Which data is sent to Webmetic?__

Only the sha256 hash of the visitor IP address, together with your API key. No URLs,
no user agents, no cookies, and never the raw IP address.

__Why do I not see any companies?__

Only visits from identifiable company networks can be resolved — visitors from home
or mobile connections are not identified (that is by design). Also check that:
(1) company identification is enabled in the plugin settings, (2) your API key is
valid (it is verified when you save the settings), and (3) Matomo's privacy setting
"use anonymized IP addresses when enriching visits" is disabled — with masked IPs no
lookup is possible.

__Does the plugin slow down my tracking?__

No. Lookups happen at most once per visit, results (including "no match") are cached
locally, the HTTP call has a strict timeout, and every failure path simply skips
identification instead of delaying the request.

__Can I filter reports by company?__

Yes — the plugin registers two segments (company name and company ID). Any Matomo
report can be filtered or compared by company.

## Settings

| Setting | Meaning |
|---|---|
| Enable company identification | Master switch (default: off) |
| Data Layer API key | Your `wmtc_...` key from the Webmetic dashboard (*API details*); verified live when saving |

The lookup endpoint is fixed. For staging/tests it can be overridden without UI via
`config.ini.php`: `[Webmetic] lookup_url = "https://..."`.

## Support

- Email: [info@webmetic.de](mailto:info@webmetic.de)
- Issues: [GitHub](https://github.com/webmetic/matomo-plugin/issues)
- Documentation: [webmetic.de](https://webmetic.de)

## Changelog

### 0.4.1
- All Webmetic segments are grouped under their own "Webmetic" category in the
  segment editor and prefixed with "Webmetic" so their origin is always clear.
- Example values (tooltips) for every segment, including company ID.

### 0.4.0
- New **Company size** and **Revenue class** dimensions and segments (employee and
  revenue ranges). Together with Industry this completes the target-group matrix:
  filter any report, Users Flow, or heatmap by industry × size × revenue.
- Visitor log shows size and revenue next to company name and industry.

### 0.3.0
- New **Industry** dimension and segment: identified visits are classified into
  19 marketing-ready industry categories (e.g. "IT, Software & Telekommunikation").
  Filter Users Flow, heatmaps, or any Matomo report by industry.
- Visitor log shows the industry next to the company name.

### 0.2.2
- The API key is now verified live against the Webmetic API when saving the
  settings: rejected keys and connectivity problems show a clear error message
  and are not saved. Pasted keys are trimmed automatically.

### 0.2.1
- API-key setting links directly to the Webmetic dashboard page where the key is
  created.

### 0.2.0
- Simplified setup: authentication via the Webmetic Data Layer API key, no domain
  setting needed. Settings reduced to enable toggle + API key; lookup endpoint is
  now fixed (config-file override for staging only).
- Respects Matomo's "use anonymized IP for visit enrichment" privacy setting like
  core geolocation does.

### 0.1.0
- Initial version: company dimensions, Companies report + widget, visitor log
  integration, segments, GDPR hooks, cached hash-only lookups.
