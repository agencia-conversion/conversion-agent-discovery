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
      return { error: body.message || (config.messages && config.messages.request_failed) || String(response.status), status: response.status };
    }
    return body;
  }

  var tools = (config.tools || []).map(function (tool) {
    return Object.assign({}, tool, {
      execute: function (input) {
        return getJson(config.endpoints[tool.endpoint], input || {});
      },
      annotations: { readOnlyHint: true }
    });
  });

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
