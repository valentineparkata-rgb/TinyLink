<?php
include __DIR__ . '/../db.php';

$shortCode = $_GET['code'] ?? '';

if ($shortCode) {
    // increment click_count
    $stmt = $db->prepare("SELECT long_url FROM links WHERE short_code = ?");
    $stmt->execute([$shortCode]);
    $link = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($link) {
        $stmt = $db->prepare("UPDATE links SET click_count = click_count + 1 WHERE short_code = ?");
        $stmt->execute([$shortCode]);

        // Return a small HTML page that uses JSONP to fetch blog post lists and redirect randomly
        $escapedShort = htmlspecialchars($shortCode, ENT_QUOTES, 'UTF-8');

        // list of feeds (you can adjust max-results)
        $feeds = [
            'https://www.pharmalite.in/feeds/posts/summary?alt=json&max-results=100',
        ];
        ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex">
<title>Redirecting…</title>
</head>
<body>
<p>Redirecting…</p>
<script>
(function(){
  const shortCode = <?php echo json_encode($escapedShort); ?>;
  const feeds = [
    "https://www.pharmalite.in/feeds/posts/summary?alt=json&max-results=100&callback=__blogcallback",
    "https://blog.pharmalite.in/feeds/posts/summary?alt=json&max-results=100&callback=__blogcallback"
  ];

  // accumulator for entries
  let allEntries = [];

  // JSONP callback name on window
  window.__blogcallback = function(data){
    try {
      if (data && data.feed && Array.isArray(data.feed.entry)) {
        data.feed.entry.forEach(e => {
          // Blogger entry has 'link' array, find rel='alternate'
          let link = null;
          if (Array.isArray(e.link)) {
            for (let i = 0; i < e.link.length; i++) {
              if (e.link[i].rel === 'alternate' && e.link[i].href) {
                link = e.link[i].href;
                break;
              }
            }
          }
          // fallback: entry.id.$t sometimes contains URL; otherwise skip
          if (!link && e.link && e.link.href) link = e.link.href;
          if (link) allEntries.push(link);
        });
      }
    } catch (err) {
      // ignore parsing errors per feed
      console.error('feed parse err', err);
    }
    // when both feeds loaded, redirect
    // We use a small delay check to ensure both scripts have had a chance to call the callback.
  };

  // load both JSONP feeds as script tags
  let loadedCount = 0;
  feeds.forEach(src => {
    const s = document.createElement('script');
    s.src = src + '&_=' + Date.now(); // cache buster
    s.onload = function(){ loadedCount++; maybeRedirect(); };
    s.onerror = function(){ loadedCount++; maybeRedirect(); };
    document.head.appendChild(s);
  });

  function maybeRedirect(){
    // attempt redirect after both requested (or when some scripts error)
    if (loadedCount >= feeds.length) {
      if (allEntries.length === 0) {
        // fallback: if no entries found, redirect back to home with ref
        redirectTo('https://www.pharmalite.in');
        return;
      }
      // choose random entry
      const idx = Math.floor(Math.random() * allEntries.length);
      let url = allEntries[idx];
      redirectTo(url);
    }
  }

  function redirectTo(url){
    // append ref param safely
    const sep = url.indexOf('?') === -1 ? '?' : '&';
    const finalUrl = url + sep + 'ref=' + encodeURIComponent(shortCode);
    // Navigate
    window.location.replace(finalUrl);
  }

  // safety: if JSONP doesn't load for some reason, fallback after 3s
  setTimeout(function(){
    if (allEntries.length === 0) {
      // fallback: just go to main site with ref
      redirectTo('https://www.pharmalite.in');
    }
  }, 3000);

})();
</script>
</body>
</html>
<?php
        exit;
    }
}

http_response_code(404);
echo "Link not found.";
