=== Conversion Agent Discovery ===
Contributors: agenciaconversion
Tags: ai, agents, markdown, discovery, llms-txt
Requires at least: 5.9
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.1.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Read-only agent discovery surfaces for WordPress: Markdown negotiation, llms.txt, API catalog, agent skills, WebMCP, and content signals.

== Description ==

Conversion Agent Discovery helps WordPress sites expose public, read-only discovery surfaces for AI agents without pretending to support capabilities that are not implemented.

It is designed to improve measurable agent accessibility in tools such as [Is It Agent Ready](https://isitagentready.com/) and [Agent Crawl](https://agenticseo.sh/tools/agent-crawl), a Conversion tool for measuring how much of a site can be read by agents.

Features include:

* Markdown negotiation for public content with `Accept: text/markdown`.
* Content Signals in `robots.txt`.
* Generated `llms.txt` and `/.well-known/llms.txt`.
* Generated `/.well-known/api-catalog`.
* Generated `/.well-known/agent-skills/index.json` and virtual `SKILL.md` files.
* Read-only WebMCP tool registration for compatible browsers.
* Public read-only REST endpoints for site search, recent content, single content reads, site context, and contact handoff.
* Admin settings page with a global kill switch.

Conversion Agent Discovery does not publish fake OAuth, MCP Server Card, A2A, or commerce metadata.

== External Services ==

This plugin does not send requests to external services automatically and does not transmit visitor or site data to third parties.

The plugin contains optional external links that administrators or users may open manually:

* `https://schemas.agentskills.io/` is referenced as the public JSON schema identifier in the generated Agent Skills discovery document. The plugin does not call this URL automatically.
* `https://isitagentready.com/` is linked as a manual measurement tool for agent discovery checks.
* `https://agenticseo.sh/tools/agent-crawl` is linked as a manual Conversion measurement tool for checking how much of a site can be read by agents.
* `https://conversion.ag/` is linked for Conversion attribution and plugin author information.
* `https://github.com/agencia-conversion/conversion-agent-discovery` is linked as the public development repository and release source.
* `https://wordpress.org/plugins/wp-graphql/` is linked from the admin screen when WPGraphQL is not detected, so administrators can optionally install it.

Conversion's privacy policy is available at https://www.conversion.com.br/politica-de-privacidade/.

== Privacy ==

Conversion Agent Discovery does not track users and does not send site data to external services automatically. The measurement links in the admin screen and readme are plain outbound links; no scanner is called unless an administrator opens those tools manually.

== Development ==

Development happens at https://github.com/agencia-conversion/conversion-agent-discovery. The distributed plugin contains readable PHP, JavaScript, CSS, and SVG source files; no build step is required to run the plugin.

== Installation ==

1. Upload the plugin ZIP in Plugins > Add New > Upload Plugin.
2. Activate Conversion Agent Discovery.
3. Open Settings > Conversion Agent Discovery.
4. Review metadata and enabled modules.
5. Purge site cache after activation.

== Frequently Asked Questions ==

= Does this change the HTML site? =

No. Normal browser and crawler requests continue to receive HTML. Markdown is returned only when the request explicitly sends `Accept: text/markdown`.

= Does this create an MCP server? =

No. Conversion Agent Discovery publishes only real read-only resources. It does not publish an MCP Server Card unless a real MCP server exists.

== Changelog ==

= 0.1.8 =
* Rename the plugin package to Conversion Agent Discovery with slug and text domain `conversion-agent-discovery`.
* Move admin CSS, admin notices JavaScript, and WebMCP JavaScript to local enqueued assets.
* Replace legacy WP Agentic identifiers with Conversion Agent Discovery prefixes.
* Use `rest_url()` for REST API discovery URLs.
* Add WordPress.org external services documentation.

= 0.1.6 =
* Prepare WordPress.org submission metadata, privacy notes, development source link, and package exclusions.

= 0.1.5 =
* Schedule rewrite flushes after plugin upgrades so third-party sitemap rules remain registered.

= 0.1.4 =
* Keep third-party WordPress admin notices out of the custom dashboard hero.
* Document measurement support for Is It Agent Ready and Agent Crawl by Conversion.

= 0.1.3 =
* Rename the public plugin name and distribution slug to Conversion Agent Discovery for WordPress.org directory compatibility.
* Add Conversion branding assets to the admin header and footer.
* Update plugin credit links to conversion.ag.
* Keep existing settings keys for smoother upgrades from earlier builds.

= 0.1.2 =
* Redesign the admin settings page as a richer dashboard with module explanations, scanner impact, diagnostics, and version footer.
* Add WPGraphQL status detection and installation guidance when WPGraphQL is not detected.
* Document why WebMCP is different from an MCP Server Card and keep fake OAuth, MCP Server Card, A2A, and commerce metadata unpublished.

= 0.1.1 =
* Add read-only WebMCP tools.
* Add public read-only Conversion Agent Discovery REST endpoints.
* Update Agent Skills discovery to v0.2 with virtual SKILL.md artifacts and SHA-256 digests.
* Add Markdown frontmatter and Content-Signal response headers.
* Add explicit Link headers for agent resources.

= 0.1.0 =
* Initial release.
