(function () {
  function moveConversionAgentDiscoveryNotices() {
    var lane = document.querySelector('.conversion-agent-discovery-admin-notices');
    var hero = document.querySelector('.conversion-agent-discovery-hero');
    if (!lane || !hero) {
      return;
    }

    hero.querySelectorAll('.notice, .updated, .error, .update-nag').forEach(function (notice) {
      lane.appendChild(notice);
    });
  }

  moveConversionAgentDiscoveryNotices();
  document.addEventListener('DOMContentLoaded', moveConversionAgentDiscoveryNotices);

  if (window.MutationObserver) {
    var hero = document.querySelector('.conversion-agent-discovery-hero');
    if (hero) {
      new MutationObserver(moveConversionAgentDiscoveryNotices).observe(hero, {
        childList: true,
        subtree: true
      });
    }
  }
})();
