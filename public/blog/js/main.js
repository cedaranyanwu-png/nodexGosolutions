
// Default scripts loader for cedar
// Real-time tracking loader for page_views and click interactions
(function() {
    // Send background page view ping to tracking endpoint
    const trackEvent = function(eventName) {
        // Construct the tracking URL using correctly interpolated subdomain values
        const payloadUrl = '/php/track_metrics.php?subdomain=blog&event=' + eventName;
        // Perform an asynchronous CORS-supported fetch to trigger metric increments
        fetch(payloadUrl, { method: 'POST', mode: 'cors' })
            .then(response => response.json())
            .then(data => console.log('nodexGo Traffic Log:', data))
            .catch(err => console.error('nodexGo Tracking Error:', err));
    };

    // Track standard page load on active visitor landing
    trackEvent('page_view');

    // Track click interaction on clickBtn element specifically
    const btn = document.getElementById('clickBtn');
    // If the interactive button is active on page DOM, append click tracker
    if (btn) {
        btn.addEventListener('click', function(e) {
            // Prevent default browser href routing
            e.preventDefault();
            // Trigger background tracking call for click events
            trackEvent('click');
            // Display friendly prompt alert
            alert('Hello from cedar! Your workspace is completely interactive.');
        });
    }
})();
