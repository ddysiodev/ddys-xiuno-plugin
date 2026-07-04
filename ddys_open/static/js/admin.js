(function () {
  function updateGenerator(root) {
    var kind = root.querySelector('[data-ddys-generator-kind]').value;
    var type = root.querySelector('[data-ddys-generator-type]').value;
    var limit = root.querySelector('[data-ddys-generator-limit]').value;
    var identity = root.querySelector('[data-ddys-generator-identity]').value.trim();
    var output = root.querySelector('[data-ddys-generator-output]');
    var detailMap = {
      ddys_movie: 'slug',
      ddys_sources: 'slug',
      ddys_related: 'slug',
      ddys_comments: 'slug',
      ddys_collection: 'slug',
      ddys_share: 'id',
      ddys_user: 'username'
    };
    var pageMap = {
      ddys_movies: 'latest',
      ddys_latest: 'latest',
      ddys_hot: 'hot',
      ddys_search: 'search',
      ddys_calendar: 'calendar',
      ddys_movie: 'movie',
      ddys_sources: 'movie',
      ddys_related: 'movie',
      ddys_comments: 'movie',
      ddys_collections: 'collections',
      ddys_collection: 'collection',
      ddys_requests: 'requests',
      ddys_request_form: 'requests'
    };
    var proxyMap = {
      ddys_movies: 'movies',
      ddys_latest: 'latest',
      ddys_hot: 'hot',
      ddys_search: 'search',
      ddys_suggest: 'suggest',
      ddys_calendar: 'calendar',
      ddys_movie: 'movie',
      ddys_sources: 'sources',
      ddys_related: 'related',
      ddys_comments: 'comments',
      ddys_collections: 'collections',
      ddys_collection: 'collection',
      ddys_shares: 'shares',
      ddys_share: 'share',
      ddys_requests: 'requests',
      ddys_activities: 'activities',
      ddys_user: 'user',
      ddys_types: 'types',
      ddys_genres: 'genres',
      ddys_regions: 'regions'
    };
    if (kind === 'page') {
      var page = pageMap[type] || 'latest';
      var pageUrl = root.getAttribute('data-ddys-page-' + page) || root.getAttribute('data-ddys-page-latest') || '';
      if ((page === 'movie' || page === 'collection') && identity) {
        pageUrl = pageUrl.replace('__identity__', encodeURIComponent(identity));
      } else {
        pageUrl = pageUrl.replace('__identity__', '');
      }
      output.value = pageUrl;
      return;
    }
    if (kind === 'proxy') {
      var route = proxyMap[type] || 'latest';
      var proxyUrl = root.getAttribute('data-ddys-proxy-url') || '';
      var sep = proxyUrl.indexOf('?') === -1 ? '?' : '&';
      var params = ['route=' + encodeURIComponent(route)];
      if (identity && detailMap[type]) params.push(encodeURIComponent(detailMap[type]) + '=' + encodeURIComponent(identity));
      if (!detailMap[type] && limit) params.push('limit=' + encodeURIComponent(limit));
      output.value = proxyUrl + sep + params.join('&');
      return;
    }
    var code = '[' + type;
    if (detailMap[type] && identity) code += ' ' + detailMap[type] + '="' + identity.replace(/"/g, '') + '"';
    if (!detailMap[type] && limit) code += ' limit="' + limit + '"';
    code += ']';
    output.value = code;
  }

  document.addEventListener('DOMContentLoaded', function () {
    var roots = document.querySelectorAll('[data-ddys-xiuno-generator]');
    Array.prototype.forEach.call(roots, function (root) {
      root.addEventListener('input', function () { updateGenerator(root); });
      root.addEventListener('change', function () { updateGenerator(root); });
      updateGenerator(root);
    });
  });
})();
