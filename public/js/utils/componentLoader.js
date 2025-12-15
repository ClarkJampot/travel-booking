/**
 * Component loading utilities
 */
const _componentCache = new Map();

/**
 * Load a component HTML file into a target element
 * @param {string} targetId - Target element ID
 * @param {string} componentPath - Primary component path
 * @param {string} fallbackPath - Fallback component path (optional)
 */
async function loadComponent(targetId, componentPath, fallbackPath) {
  const el = document.getElementById(targetId);
  if (!el) return;
  const tryFetch = async (path) => {
    if (_componentCache.has(path)) {
      return _componentCache.get(path);
    }
    const res = await fetch(path);
    if (!res.ok) throw new Error(`Failed to load ${path} (${res.status})`);
    const html = await res.text();
    _componentCache.set(path, html);
    return html;
  };
  try {
    const html = await tryFetch(componentPath);
    el.innerHTML = html;
  } catch (e) {
    if (fallbackPath) {
      try {
        const html = await tryFetch(fallbackPath);
        el.innerHTML = html;
        return;
      } catch (fallbackErr) {
        console.error('Component fallback failed:', fallbackErr);
      }
    }
    console.error('Component load failed:', e);
  }
}

/**
 * Load header and footer components
 */
async function loadLayout() {
  await Promise.all([
    loadComponent('header', 'components/header.html', 'includes/header.html'),
    loadComponent('footer', 'components/footer.html', 'includes/footer.html')
  ]);
  updateNavigation();
}
