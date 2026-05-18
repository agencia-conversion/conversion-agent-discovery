(function () {
  var modelContext = navigator.modelContext;
  if (!modelContext) {
    return;
  }

  var config = window.ConversionAgentDiscoveryWebMCP || {};
  if (!config.endpoints) {
    return;
  }

  function withParams(url, params) {
    var next = new URL(url, window.location.origin);
    Object.keys(params || {}).forEach(function (key) {
      var value = params[key];
      if (value !== undefined && value !== null && value !== '') {
        next.searchParams.set(key, String(value));
      }
    });
    return next.toString();
  }

  async function getJson(url, params) {
    var response = await fetch(withParams(url, params || {}), {
      method: 'GET',
      credentials: 'same-origin',
      headers: { Accept: 'application/json' }
    });
    var body = await response.json().catch(function () {
      return {};
    });
    if (!response.ok) {
      return { error: body.message || 'Request failed', status: response.status };
    }
    return body;
  }

  var tools = [
    {
      name: 'search_posts',
      description: 'Search public posts and pages on this WordPress site.',
      inputSchema: {
        type: 'object',
        properties: {
          query: { type: 'string', description: 'Search query.' },
          per_page: { type: 'integer', minimum: 1, maximum: 20, description: 'Maximum results to return.' }
        },
        required: ['query']
      },
      execute: function (input) {
        return getJson(config.endpoints.search, input || {});
      },
      annotations: { readOnlyHint: true }
    },
    {
      name: 'read_post',
      description: 'Read a public post or page by id, URL, or slug.',
      inputSchema: {
        type: 'object',
        properties: {
          id: { type: 'integer', description: 'WordPress post ID.' },
          url: { type: 'string', format: 'uri', description: 'Canonical public URL.' },
          slug: { type: 'string', description: 'Post or page slug.' },
          type: { type: 'string', description: 'Optional post type, usually post or page.' }
        }
      },
      execute: function (input) {
        return getJson(config.endpoints.content, input || {});
      },
      annotations: { readOnlyHint: true }
    },
    {
      name: 'list_recent_posts',
      description: 'List recent public posts and pages from this WordPress site.',
      inputSchema: {
        type: 'object',
        properties: {
          per_page: { type: 'integer', minimum: 1, maximum: 20, description: 'Maximum results to return.' }
        }
      },
      execute: function (input) {
        return getJson(config.endpoints.recent, input || {});
      },
      annotations: { readOnlyHint: true }
    },
    {
      name: 'get_site_context',
      description: 'Get public Conversion Agent Discovery context, discovery URLs, and content policy for this site.',
      inputSchema: { type: 'object', properties: {} },
      execute: function () {
        return getJson(config.endpoints.context, {});
      },
      annotations: { readOnlyHint: true }
    },
    {
      name: 'contact_conversion',
      description: 'Get the public contact URL. This tool does not submit forms.',
      inputSchema: { type: 'object', properties: {} },
      execute: function () {
        return getJson(config.endpoints.contact, {});
      },
      annotations: { readOnlyHint: true }
    }
  ];

  if (typeof modelContext.registerTool === 'function') {
    tools.forEach(function (tool) {
      try {
        modelContext.registerTool(tool);
      } catch (error) {}
    });
    return;
  }

  if (typeof modelContext.provideContext === 'function') {
    try {
      modelContext.provideContext({ tools: tools });
    } catch (error) {}
  }
})();
