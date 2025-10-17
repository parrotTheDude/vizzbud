/**
 * Shared helper for fetching dive sites via the /api/dive-sites/search endpoint.
 * Handles:
 *  - Local caching for repeated searches
 *  - Optional geolocation
 *  - Simple fuzzy search fallback for instant UX
 */
export default class DiveSiteService {
  constructor() {
    this.apiBase = '/api/dive-sites/search';
    this.cache = {};
    this.country = null;
  }

  /**
   * Fetch dive sites (with caching and graceful error handling)
   * @param {Object} options - { query, lat, lng, worldwide, radius }
   */
  async fetchSites({
    query = '',
    lat = '',
    lng = '',
    worldwide = false,
    radius = 200
  } = {}) {
    const key = `${query}_${lat}_${lng}_${worldwide}_${radius}`;
    if (this.cache[key]) return this.cache[key];

    const params = new URLSearchParams({
      query: query.trim(),
      lat: lat || '',
      lng: lng || '',
      worldwide,
      radius
    });

    try {
      const res = await fetch(`${this.apiBase}?${params.toString()}`);

      if (!res.ok) {
        console.error(`❌ Dive site fetch failed (${res.status})`);
        return { results: [], country: this.country, error: `HTTP ${res.status}` };
      }

      const data = await res.json();

      // Store detected country if backend returns it
      if (data?.country && !this.country) {
        this.country = data.country;
      }

      // Cache result for 2 minutes
      this.cache[key] = data;
      setTimeout(() => delete this.cache[key], 2 * 60 * 1000);

      return data;
    } catch (err) {
      console.error('❌ Dive site fetch failed:', err);
      return { results: [], country: this.country, error: err.message };
    }
  }

  /**
   * Fuzzy match cached results locally (for instant UX)
   */
  static localFilter(query, sites = []) {
    const q = (query || '').toLowerCase().trim();
    if (!q) return sites;

    return sites
      .map((s) => ({
        site: s,
        score: DiveSiteService.scoreMatch(s.name, q)
      }))
      .filter((r) => r.score > 0)
      .sort((a, b) => b.score - a.score)
      .map((r) => r.site)
      .slice(0, 30);
  }

  /**
   * Basic fuzzy scoring (lightweight version of backend SOUNDEX)
   */
  static scoreMatch(name = '', q = '') {
    const n = name.toLowerCase();
    if (n === q) return 100;
    if (n.startsWith(q)) return 80;
    if (n.includes(q)) return 60;

    // lightweight fuzzy: characters in sequence
    let hits = 0, i = 0;
    for (const c of n) {
      if (c === q[i]) {
        hits++;
        i++;
        if (i >= q.length) break;
      }
    }
    return hits >= Math.max(2, Math.ceil(q.length * 0.6)) ? 40 : 0;
  }

}

window.DiveSiteService = DiveSiteService;