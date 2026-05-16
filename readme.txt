=== WP Agentic ===
Contributors: conversion
Tags: ai, agents, markdown, llms.txt, robots.txt
Requires at least: 5.9
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Agent readiness for WordPress: Markdown negotiation, llms.txt, API catalog, agent skills, and AI content signals.

== Description ==

WP Agentic helps WordPress sites expose public, read-only discovery surfaces for AI agents without pretending to support capabilities that are not implemented.

Features:

* Markdown negotiation for public content with `Accept: text/markdown`.
* Content Signals in `robots.txt`.
* Generated `llms.txt` and `/.well-known/llms.txt`.
* Generated `/.well-known/api-catalog`.
* Generated `/.well-known/agent-skills/index.json`.
* Admin settings page with a global kill switch.

WP Agentic does not publish fake OAuth, MCP, A2A, or commerce metadata.

== Installation ==

1. Upload the plugin ZIP in Plugins > Add New > Upload Plugin.
2. Activate WP Agentic.
3. Open Settings > WP Agentic.
4. Review metadata and enabled modules.
5. Purge site cache after activation.

== Frequently Asked Questions ==

= Does this change the HTML site? =

No. Normal browser and crawler requests continue to receive HTML. Markdown is returned only when the request explicitly sends `Accept: text/markdown`.

= Does this create an MCP server? =

No. Version 0.1.0 publishes only real read-only resources.

== Changelog ==

= 0.1.0 =
* Initial release.
